<?php
defined('ABSPATH') || exit;

use MichaThemeV5\Plugin\Sanitizer;

/**
 * Liest und schreibt die Theme-Einstellungen. Gleicher Speicher wie das Theme (theme_mods, Schlüssel mt-*),
 * keine Datenkopie. Kein Schlüssel wird umbenannt oder gelöscht.
 */
final class MT_Store
{
    private static ?array $schema = null;

    /** @return array<string,mixed> */
    public static function schema(): array
    {
        if (self::$schema === null) {
            $raw = (string) file_get_contents(MT_PLUGIN_DIR . 'includes/generated/mt-admin-schema.json');
            $d = json_decode($raw, true);
            self::$schema = is_array($d) && ($d['schemaVersion'] ?? 0) === 1 ? $d : ['schemaVersion' => 1, 'groups' => [], 'options' => []];
        }

        return self::$schema;
    }

    /** @return array<string,array<string,mixed>> id => Option */
    public static function options(): array
    {
        $out = [];
        foreach (self::schema()['options'] as $o) {
            $out[$o['id']] = $o;
        }

        return $out;
    }

    /** Ist ein MichaThemeV5-Theme (oder ein Kind davon) aktiv? */
    public static function theme_active(): bool
    {
        return function_exists('mt_catalog') && defined('MT_VERSION');
    }

    public static function page_exists(int $id): bool
    {
        return get_post_type($id) === 'page';
    }

    /** @return array<string,mixed> id => aktueller (geprüfter) Wert */
    public static function values(): array
    {
        $v = [];
        foreach (self::options() as $id => $o) {
            $v[$id] = Sanitizer::value($o, get_theme_mod($o['key'], $o['default']), [self::class, 'page_exists']);
        }

        return $v;
    }

    /** Darf diese Option jetzt geschrieben werden? Code-Felder nur mit Recht für Code. */
    private static function writable(array $o): bool
    {
        return $o['type'] !== 'code' || current_user_can('unfiltered_html');
    }

    /**
     * Speichert geänderte Werte. Es werden nur Schlüssel geschrieben, deren Wert sich wirklich ändert.
     *
     * @param array<string,mixed> $input id => roher Wert (aus $_POST['mt'], ausgepackt)
     * @return array{changed:list<string>,adjusted:list<string>}
     */
    public static function save(array $input): array
    {
        $opts = self::options();
        $res = Sanitizer::form($opts, $input, [self::class, 'page_exists']);
        $current = self::values();
        $changed = [];
        $adjusted = [];
        $backedUp = false;
        foreach ($res['values'] as $id => $new) {
            $o = $opts[$id];
            if (!self::writable($o)) {
                continue;
            }
            // Wurde der Wert vom Prüfer verändert (z. B. ungültige Farbe)? Dann dem Nutzer sagen.
            $raw = $input[$id];
            if (is_string($raw) && trim($raw) !== '' && in_array($o['type'], ['color', 'media'], true) && $new === '') {
                $adjusted[] = $id;
            }
            if ($new === $current[$id]) {
                continue;
            }
            if (!$backedUp) {
                self::backup();
                $backedUp = true;
            }
            set_theme_mod($o['key'], $new);
            $changed[] = $id;
        }

        return ['changed' => $changed, 'adjusted' => $adjusted];
    }

    /** @return list<array<string,mixed>> eingebaute Presets (aus dem Schema) */
    public static function presets(): array
    {
        return self::schema()['presets'] ?? [];
    }

    /** Preset anwenden: setzt nur Optionswerte, vorher Sicherung. Gibt die Zahl der geänderten Werte zurück, -1 = unbekanntes Preset. */
    public static function apply_preset(string $id): int
    {
        foreach (self::presets() as $p) {
            if ($p['id'] === $id) {
                return count(self::save(\MichaThemeV5\Plugin\Presets::values($p, self::options()))['changed']);
            }
        }

        return -1;
    }

    /** Sicherung aller mt-* Einstellungen des aktiven Themes (Option, nicht automatisch geladen). */
    public static function backup(): void
    {
        $mods = [];
        foreach (self::options() as $o) {
            $v = get_theme_mod($o['key'], null);
            if ($v !== null) {
                $mods[$o['key']] = $v;
            }
        }
        update_option('michatheme_v5_settings_backup', ['at' => time(), 'stylesheet' => get_stylesheet(), 'mods' => $mods], false);
    }

    public static function backup_info(): ?array
    {
        $b = get_option('michatheme_v5_settings_backup', null);

        return is_array($b) && is_array($b['mods'] ?? null) ? $b : null;
    }

    /** Stellt die Sicherung wieder her (jeder Wert läuft erneut durch die Prüfung). Gibt die Zahl der gesetzten Werte zurück. */
    public static function restore(): int
    {
        $b = self::backup_info();
        if ($b === null) {
            return 0;
        }
        $n = 0;
        foreach (self::options() as $o) {
            if (!array_key_exists($o['key'], $b['mods']) || !self::writable($o)) {
                continue;
            }
            set_theme_mod($o['key'], Sanitizer::value($o, $b['mods'][$o['key']], [self::class, 'page_exists']));
            $n++;
        }

        return $n;
    }

    /** Palette (höchstens 12 Farben #rrggbb), gilt für den ganzen Shop. */
    public static function palette(): array
    {
        return self::clean_palette(json_decode((string) get_option('michatheme_v5_palette', '[]'), true));
    }

    public static function clean_palette(mixed $list): array
    {
        $out = [];
        foreach (is_array($list) ? $list : [] as $c) {
            if (is_string($c) && preg_match('/^#[0-9a-fA-F]{6}$/', $c) === 1 && !in_array(strtolower($c), $out, true) && count($out) < 12) {
                $out[] = strtolower($c);
            }
        }

        return $out;
    }

    public static function save_palette(mixed $raw): void
    {
        $list = is_string($raw) ? json_decode($raw, true) : null;
        update_option('michatheme_v5_palette', wp_json_encode(self::clean_palette($list)), false);
    }
}

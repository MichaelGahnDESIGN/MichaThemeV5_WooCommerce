<?php
defined('ABSPATH') || exit;

/**
 * Theme bereitstellen. Nie still und nie automatisch:
 *  1. Nur per Knopf auf der Info-Seite, mit Rückfrage-Seite und Häkchen "Ich habe ein Backup".
 *  2. Quelle ist nur das MITGELIEFERTE Theme im Plugin (kein Download), Prüfsumme gegen build-integrity.json vor und nach dem Kopieren.
 *  3. Ein vorhandener Theme-Ordner wird zuerst nach uploads/michatheme-v5-backup/<datum>/ gesichert, bei Fehler zurückgespielt.
 *  4. Aktivieren ist ein eigener zweiter Schritt, auch mit Rückfrage. Kind-Themes werden nie angefasst.
 * Pfade kommen nur aus Konstanten, nie aus Eingaben.
 */
final class MT_Theme_Installer
{
    public const DEFAULT_SLUG = 'michatheme-v5';

    public static function bundle_dir(): string
    {
        return MT_PLUGIN_DIR . 'theme/' . self::DEFAULT_SLUG . '/';
    }

    /** @return array{version:string,files:array<string,string>}|null */
    public static function manifest(): ?array
    {
        $f = self::bundle_dir() . 'build-integrity.json';
        if (!is_file($f)) {
            return null;
        }
        $d = json_decode((string) file_get_contents($f), true);

        return is_array($d) && is_string($d['version'] ?? null) && is_array($d['files'] ?? null) ? $d : null;
    }

    /** Ordnername eines schon installierten MichaThemeV5 (Hauptthema, kein Kind), sonst der Standardname. */
    public static function target_slug(): string
    {
        foreach (wp_get_themes() as $slug => $t) {
            if ($t->get('TextDomain') === 'michathemev5' && !$t->parent() && $t->get_template() === (string) $slug) {
                return (string) $slug;
            }
        }

        return self::DEFAULT_SLUG;
    }

    public static function installed_version(): ?string
    {
        $t = wp_get_theme(self::target_slug());

        return $t->exists() ? (string) $t->get('Version') : null;
    }

    /** Ist das (Eltern-)Theme der aktuell aktiven Seite ein MichaThemeV5 in dem Ordner, den wir ersetzen würden? */
    public static function is_active(): bool
    {
        return get_template() === self::target_slug() && MT_Store::theme_active();
    }

    /** Prüft jede Datei des Bündels gegen die Prüfsummen. Null = in Ordnung, sonst eine Meldung. */
    public static function verify_bundle(): ?string
    {
        $m = self::manifest();
        if ($m === null) {
            return 'Das Plugin enthält kein Theme-Paket (build-integrity.json fehlt). Bitte das komplette Plugin-ZIP von der Download-Seite verwenden.';
        }

        return self::verify_dir(self::bundle_dir(), $m['files']);
    }

    /** @param array<string,string> $files relativer Pfad => sha256 */
    public static function verify_dir(string $dir, array $files): ?string
    {
        $base = realpath($dir);
        if ($base === false) {
            return 'Ordner nicht gefunden.';
        }
        $seen = [];
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS));
        foreach ($it as $f) {
            if ($f->isLink()) {
                return 'Unerwarteter Verweis (Symlink) im Theme-Paket.';
            }
            if (!$f->isFile()) {
                continue;
            }
            $rel = str_replace('\\', '/', substr($f->getPathname(), strlen($base) + 1));
            if ($rel === 'build-integrity.json') {
                continue;
            }
            if (!isset($files[$rel])) {
                return 'Unerwartete Datei im Theme-Paket: ' . $rel;
            }
            if (!hash_equals($files[$rel], hash_file('sha256', $f->getPathname()))) {
                return 'Prüfsumme stimmt nicht: ' . $rel;
            }
            $seen[$rel] = true;
        }
        foreach ($files as $rel => $_) {
            if (!isset($seen[$rel])) {
                return 'Datei fehlt im Theme-Paket: ' . $rel;
            }
        }

        return null;
    }

    /**
     * Kopiert das Bündel in den Themes-Ordner. Rückgabe: null = ok, sonst Meldung. Aufrufer hat Rechte, Nonce und Rückfrage schon geprüft.
     */
    public static function copy(): ?string
    {
        if (($err = self::verify_bundle()) !== null) {
            return $err;
        }
        global $wp_filesystem;
        require_once ABSPATH . 'wp-admin/includes/file.php';
        if (!$wp_filesystem && !WP_Filesystem(true)) {
            return 'Kein Schreibzugriff auf den Themes-Ordner (Dateisystem-Zugangsdaten fehlen).';
        }
        $slug = self::target_slug();
        $themes = trailingslashit($wp_filesystem->wp_themes_dir());
        $target = $themes . $slug;
        $bundle = $wp_filesystem->find_folder(self::bundle_dir()) ?: self::bundle_dir();
        $backup = null;

        if ($wp_filesystem->is_dir($target)) {
            $uploads = wp_upload_dir(null, false);
            $root = trailingslashit($wp_filesystem->find_folder($uploads['basedir']) ?: $uploads['basedir']) . 'michatheme-v5-backup/';
            $backup = $root . gmdate('Ymd-His') . '/' . $slug;
            if (!wp_mkdir_p(dirname($backup)) && !$wp_filesystem->mkdir(dirname($backup))) {
                return 'Sicherungsordner konnte nicht angelegt werden. Es wurde nichts verändert.';
            }
            $wp_filesystem->put_contents($root . 'index.php', "<?php\n// Stille ist Gold.\n");
            $res = copy_dir($target, $backup);
            if (is_wp_error($res)) {
                return 'Sicherung des bisherigen Themes ist fehlgeschlagen. Es wurde nichts verändert.';
            }
            if (!$wp_filesystem->delete($target, true)) {
                return 'Das bisherige Theme konnte nicht ersetzt werden. Es wurde nichts verändert.';
            }
        }
        $res = copy_dir($bundle, $target);
        $manifest = self::manifest();
        $bad = is_wp_error($res) ? 'Kopieren fehlgeschlagen.' : self::verify_dir($target, $manifest['files'] ?? []);
        if ($bad !== null) {
            $wp_filesystem->delete($target, true);
            if ($backup !== null) {
                copy_dir($backup, $target); // Rücksicherung
            }

            return $bad . ' Das bisherige Theme wurde ' . ($backup !== null ? 'zurückgespielt.' : 'nicht verändert.');
        }
        wp_clean_themes_cache();

        return null;
    }

    /** Aktivieren (zweiter Schritt). Null = ok. */
    public static function activate(): ?string
    {
        $slug = self::target_slug();
        $t = wp_get_theme($slug);
        if (!$t->exists() || $t->get('TextDomain') !== 'michathemev5') {
            return 'Das Theme ist noch nicht installiert. Bitte zuerst "Theme kopieren".';
        }
        $cur = wp_get_theme();
        if ($cur->parent() && $cur->get_template() === $slug) {
            return 'Ein Kind-Theme von MichaThemeV5 ist aktiv. Es wird nicht verändert.';
        }
        switch_theme($slug);

        return null;
    }
}

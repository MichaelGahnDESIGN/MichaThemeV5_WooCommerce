<?php
defined('ABSPATH') || exit;

use MichaThemeV5\Plugin\{BridgeInterface, Sanitizer};

/**
 * MT_Bridge: Laufzeit-Schnittstelle Plugin und Theme (docs/PLUGIN-BRIDGE.md im Pro-Repo).
 * Das Theme ruft sie nur auf, wenn die Klasse da ist (class_exists), sonst gilt sein alter Weg.
 */
final class MT_Bridge implements BridgeInterface
{
    private static ?self $inst = null;

    public static function instance(): self
    {
        return self::$inst ??= new self();
    }

    public static function boot(): void
    {
        self::instance();
    }

    public function option(string $id): mixed
    {
        $o = MT_Store::options()[$id] ?? null;
        if ($o === null) {
            return null; // unbekannt: Theme nimmt seinen eigenen Weg
        }

        return Sanitizer::value($o, get_theme_mod($o['key'], $o['default']), [MT_Store::class, 'page_exists']);
    }

    public function licenseState(): array
    {
        return MT_License::state();
    }

    public function licenseAllows(string $module): bool
    {
        return MT_License::allows($module);
    }

    public function pluginVersion(): string
    {
        return MT_PLUGIN_VERSION;
    }

    public function notice(string $key): ?string
    {
        return match ($key) {
            'theme_outdated' => defined('MT_VERSION') && version_compare(MT_VERSION, MT_PLUGIN_THEME_MIN, '<')
                ? 'Das Theme ist älter als vom Plugin erwartet. Bitte unter MichaThemeV5 › Info aktualisieren.' : null,
            default => null,
        };
    }
}

<?php
defined('ABSPATH') || exit;

use MichaThemeV5\Plugin\Install\Platform\WordPress;

// Install-Client Free-Lizenz, Code aus Pro > bin/build-install-client.php > includes/install
// meldet nur Produkt, Version, Shopsystem, Domain + zufällige ID. Nur im Admin, max 1x am Tag. Fehler nie nach außen
final class MT_Install
{
    public const PRODUCT = 'michatheme-v5-woocommerce';
    public const PLATFORM = 'woocommerce';
    public const OPTION = 'mgd_install_michatheme_v5_woocommerce';
    public const NOTICE = 'mgd_install_notice_michatheme_v5_woocommerce';
    private const NS = 'MichaThemeV5\\Plugin\\Install\\';

    // kleiner Autoloader nur für unseren Namensraum
    public static function autoload(string $class): void
    {
        if (!str_starts_with($class, self::NS)) {
            return;
        }
        $rel = str_replace('\\', '/', substr($class, strlen(self::NS)));
        if (preg_match('#^[A-Za-z]+(/[A-Za-z]+)?$#D', $rel) !== 1) {
            return;
        }
        $file = __DIR__ . '/install/' . $rel . '.php';
        if (is_file($file)) {
            require_once $file;
        }
    }

    /** Schalter + Hinweis für die Einstellungsseite, leer bei Fehler */
    public static function settings_html(): string
    {
        try {
            return WordPress::settingsHtml(self::PRODUCT, self::PLATFORM, MT_PLUGIN_VERSION);
        } catch (\Throwable $e) {
            return '';
        }
    }

    /**
     * Update-JSON über die zählende Adresse. Nur im Admin und nur wenn die Tagesmeldung sowieso dran ist,
     * sonst null > MT_Updater nimmt wie gehabt die feste Datei
     */
    public static function feed(): ?string
    {
        try {
            if (!function_exists('is_admin') || !is_admin() || !self::due()) {
                return null;
            }
            $body = WordPress::client(self::PRODUCT, self::PLATFORM, MT_PLUGIN_VERSION)?->fetchUpdates();

            return is_string($body) && $body !== '' ? $body : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    // gleiche Regel wie InstallClient::ping(): Schalter an, letzte Meldung > 1 Tag her, keine Pause nach Ausfall
    public static function due(): bool
    {
        $raw = get_option(self::OPTION, '');
        $st = is_string($raw) ? json_decode($raw, true) : null;
        $st = is_array($st) ? $st : [];
        $now = time();

        return ($st['report'] ?? true) !== false
            && (int) ($st['last_ping'] ?? 0) <= $now - 86400
            && (int) ($st['next_try'] ?? 0) <= $now;
    }
}

spl_autoload_register([MT_Install::class, 'autoload']);

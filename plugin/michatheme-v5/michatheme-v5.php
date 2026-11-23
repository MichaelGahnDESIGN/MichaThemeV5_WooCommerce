<?php
/**
 * Plugin Name: MichaThemeV5
 * Plugin URI: https://theme.michael-gahn.de
 * Description: Übersichtliche Einstellungsseite, Lizenzverwaltung und Plugin-Empfehlungen für das Theme MichaThemeV5. Das Theme funktioniert auch ohne dieses Plugin. Meldet sich höchstens einmal am Tag bei theme.michael-gahn.de (Plugin, Version, Shopsystem, Domain, zufällige ID). Abschaltbar unter Updates und Weitere Plugins.
 * Version: 1.6.0
 * Requires at least: 6.4
 * Requires PHP: 8.1
 * Author: Michael Gahn DESIGN
 * Author URI: https://michael-gahn.de
 * License: GPL-2.0-or-later
 * Update URI: https://theme.michael-gahn.de/plugin-updates/michatheme-v5-woocommerce.json
 * Text Domain: michatheme-v5
 */

defined('ABSPATH') || exit;

define('MT_PLUGIN_VERSION', '1.6.0');
// Theme-Version, die im Plugin-ZIP mitgeliefert wird, und die kleinste Version, mit der das Plugin zusammenarbeitet
define('MT_PLUGIN_THEME_BUNDLED', '1.6.0');
define('MT_PLUGIN_THEME_MIN', '0.5.0');
define('MT_PLUGIN_FILE', __FILE__);
define('MT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MT_PLUGIN_URL', plugin_dir_url(__FILE__));
// Kauf und Verwaltung der Lizenzen
if (!defined('MT_SHOP_URL')) {
    define('MT_SHOP_URL', 'https://theme.michael-gahn.de');
}

// Kernklassen. Sanitizer und ColorOverrides kann auch das Theme schon geladen haben (dann nicht doppelt laden)
foreach (['BridgeInterface', 'Sanitizer', 'FeedNormalizer', 'CatalogFeed', 'LicenseTexts', 'ModuleLoader', 'FieldRenderer', 'PageRenderer', 'ColorContrast', 'ColorOverrides', 'Presets'] as $mt_core) {
    if (!class_exists('MichaThemeV5\\Plugin\\' . $mt_core, false) && !interface_exists('MichaThemeV5\\Plugin\\' . $mt_core, false)) {
        require_once MT_PLUGIN_DIR . 'includes/generated/' . $mt_core . '.php';
    }
}
// Eigener Namespace (Plugin), damit ein älteres Theme mit eigenem Lizenzclient nicht kollidiert
if (!class_exists('MichaThemeV5\\Plugin\\LicenseClient', false)) {
    require_once MT_PLUGIN_DIR . 'includes/generated/LicenseClient.php';
}
require_once MT_PLUGIN_DIR . 'includes/class-mt-store.php';
require_once MT_PLUGIN_DIR . 'includes/class-mt-license.php';
require_once MT_PLUGIN_DIR . 'includes/class-mt-catalog.php';
require_once MT_PLUGIN_DIR . 'includes/class-mt-bridge.php';
require_once MT_PLUGIN_DIR . 'includes/class-mt-theme-installer.php';
require_once MT_PLUGIN_DIR . 'includes/class-mt-admin.php';
require_once MT_PLUGIN_DIR . 'includes/class-mt-updater.php';
require_once MT_PLUGIN_DIR . 'includes/class-mt-install.php';
require_once MT_PLUGIN_DIR . 'includes/class-mt-legacy.php';
// Baukasten (Kit) laden
require_once MT_PLUGIN_DIR . 'includes/kit-autoload.php';

register_activation_hook(__FILE__, ['MT_Plugin', 'activate']);

// Alter Install-Client (includes/install, MT_Install) ist abgeschaltet, den Install-Melder startet jetzt Setup::updater() vom Baukasten

/** Start: Admin-Teile nur im Admin. Im Shop (Frontend) macht das Plugin nichts außer der Bridge und den Update-Filtern. */
final class MT_Plugin
{
    // Updater vom Baukasten, null wenn er nicht starten konnte
    public static ?\Mgd\MichaThemeV5\Kit\Platform\WordPress\Updater $updater = null;

    public static function boot(): void
    {
        MT_Bridge::boot();
        // alter MT_Updater bleibt als Klasse da (Tests), wird aber nicht mehr angemeldet
        if (is_admin()) {
            MT_Admin::boot();
            add_action('admin_init', ['MT_License', 'migrate']);
        }
    }

    /** Beim Aktivieren wird nichts kopiert. Nur Sicherung, Lizenz-Übernahme und Weiterleitung zur Info-Seite. */
    public static function activate(): void
    {
        try {
            MT_Store::backup();
            MT_License::migrate();
        } catch (\Throwable $e) {
            // Aktivierung darf nie scheitern
        }
        $state = get_option('michatheme_v5_state', []);
        $state = is_array($state) ? $state : [];
        $state['redirect'] = true;
        update_option('michatheme_v5_state', $state, false);
    }
}

MT_Plugin::boot();

// Baukasten: Updater + Hinweis + Install-Melder in einem Aufruf. Dashboard-Seite = Menüpunkt "Updates und Weitere Plugins" > MT_Admin
try {
    MT_Plugin::$updater = \Mgd\MichaThemeV5\Kit\Platform\WordPress\Setup::updater(__FILE__, 'michatheme-v5-woocommerce', 'woocommerce', MT_PLUGIN_VERSION, 'MichaThemeV5', admin_url('admin.php?page=michatheme-v5-updates'));
    // alte Zwischenspeicher weg, 1x pro Version. Kit nimmt nur mgd_ Namen, die michatheme_v5_ Reste räumt MT_Legacy::clean() selbst
    \Mgd\MichaThemeV5\Kit\Platform\WordPress\Setup::cleanupLegacy('michatheme-v5-woocommerce', MT_PLUGIN_VERSION, MT_Legacy::NAMES);
    add_action('admin_init', ['MT_Legacy', 'clean']);
} catch (\Throwable $e) {
    // Baukasten kaputt > Plugin läuft trotzdem voll
}

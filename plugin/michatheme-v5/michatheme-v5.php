<?php
/**
 * Plugin Name: MichaThemeV5
 * Plugin URI: https://theme.michael-gahn.de
 * Description: Übersichtliche Einstellungsseite, Lizenzverwaltung und Plugin-Empfehlungen für das Theme MichaThemeV5. Das Theme funktioniert auch ohne dieses Plugin.
 * Version: 0.5.0
 * Requires at least: 6.4
 * Requires PHP: 8.1
 * Author: Michael Gahn DESIGN
 * Author URI: https://michael-gahn.de
 * License: GPL-2.0-or-later
 * Update URI: https://github.com/MichaelGahnDESIGN/MichaThemeV5_WooCommerce
 * Text Domain: michatheme-v5
 */

defined('ABSPATH') || exit;

define('MT_PLUGIN_VERSION', '0.5.0');
// Theme-Version, die im Plugin-ZIP mitgeliefert wird, und die kleinste Version, mit der das Plugin zusammenarbeitet
define('MT_PLUGIN_THEME_BUNDLED', '0.5.0');
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
if (!class_exists('MichaThemeV5\\Client\\LicenseClient', false)) {
    require_once MT_PLUGIN_DIR . 'includes/generated/LicenseClient.php';
}
require_once MT_PLUGIN_DIR . 'includes/class-mt-store.php';
require_once MT_PLUGIN_DIR . 'includes/class-mt-license.php';
require_once MT_PLUGIN_DIR . 'includes/class-mt-catalog.php';
require_once MT_PLUGIN_DIR . 'includes/class-mt-bridge.php';
require_once MT_PLUGIN_DIR . 'includes/class-mt-theme-installer.php';
require_once MT_PLUGIN_DIR . 'includes/class-mt-admin.php';
require_once MT_PLUGIN_DIR . 'includes/class-mt-updater.php';

register_activation_hook(__FILE__, ['MT_Plugin', 'activate']);

/** Start: nur im Admin etwas anhängen. Im Shop (Frontend) macht das Plugin nichts außer der Bridge. */
final class MT_Plugin
{
    public static function boot(): void
    {
        MT_Bridge::boot();
        if (is_admin()) {
            MT_Admin::boot();
            MT_Updater::boot();
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

<?php
// Autoloader Baukasten > includes/Kit (aus MichaThemeV5-Pro, bin/build-plugin-kit.php). Kommt auch in uninstall.php dran
if (!defined('ABSPATH') && !defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// nur 1x anmelden, auch wenn Hauptdatei und uninstall.php beide laden
if (!isset($GLOBALS['mt_kit_autoload'])) {
    $GLOBALS['mt_kit_autoload'] = true;
    spl_autoload_register(static function (string $class): void {
        $prefix = 'Mgd\\MichaThemeV5\\Kit\\';
        if (!str_starts_with($class, $prefix)) {
            return;
        }
        $file = __DIR__ . '/Kit/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (is_file($file)) {
            require_once $file;
        }
    });
}

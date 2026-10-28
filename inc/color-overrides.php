<?php
defined('ABSPATH') || exit;

/**
 * Eigene Farben aus den Einstellungen (Plugin oder Customizer, gleiche Schlüssel) als CSS-Variablen im <head>.
 * Leere Felder ändern nichts. Kein !important: die Selektoren sind spezifischer als die Theme-Tokens (siehe inc/mt-core/ColorOverrides.php).
 */
foreach (['Sanitizer', 'ColorOverrides'] as $mt_core) {
    // Das Plugin MichaThemeV5 bringt dieselben Klassen mit; wer zuerst da ist, gewinnt
    if (!class_exists('MichaThemeV5\\Plugin\\' . $mt_core, false)) {
        require_once __DIR__ . '/mt-core/' . $mt_core . '.php';
    }
}

/** @return string CSS-Block (nur geprüfte Farbwerte) */
function mt_color_overrides_css(): string
{
    $entries = [];
    foreach (mt_catalog()['options'] as $id => $o) {
        if (isset($o['color'])) {
            $v = mt_opt($id);
            if (is_string($v) && $v !== '') {
                $entries[] = ['token' => $o['color']['token'], 'mode' => $o['color']['mode'], 'value' => $v];
            }
        }
    }

    return \MichaThemeV5\Plugin\ColorOverrides::css($entries, mt_opt('accent_custom'));
}

add_action('wp_head', function (): void {
    $css = mt_color_overrides_css();
    if ($css !== '') {
        echo '<style id="mt-color-overrides">' . wp_strip_all_tags($css) . "</style>\n";
    }
}, 20);

<?php
defined('ABSPATH') || exit;

/** Optionskatalog (erzeugt aus themes/core/options.json). */
function mt_catalog(): array
{
    static $c = null;
    if ($c === null) {
        $c = require __DIR__ . '/mt-catalog.php';
    }

    return $c;
}

/** Aktueller Wert einer Option (id ohne Präfix, z. B. "accent"). */
function mt_opt(string $id)
{
    $o = mt_catalog()['options'][$id] ?? null;
    if ($o === null) {
        return null;
    }
    // Plugin MichaThemeV5 vorhanden? Dann fragt das Theme zuerst die Bridge (class_exists-Schutz, sonst alter Weg)
    if (class_exists('MT_Bridge', false)) {
        try {
            $b = MT_Bridge::instance()->option($id);
            if ($b !== null) {
                return $b;
            }
        } catch (\Throwable $e) {
            // Fehler im Plugin darf das Theme nie stören
        }
    }
    $v = get_theme_mod($o['key'], $o['default']);
    return mt_sanitize_value($o, $v);
}

/** Prüft und bereinigt einen Wert anhand der Optionsdefinition. */
function mt_sanitize_value(array $o, $v)
{
    switch ($o['type']) {
        case 'select':
        case 'font':
            return array_key_exists((string) $v, $o['choices']) ? (string) $v : $o['default'];
        case 'toggle':
            return (bool) $v;
        case 'color':
            // #rgb, #rrggbb oder #rrggbbaa, klein geschrieben (gleiche Regel wie das Plugin)
            if (!is_string($v) || !preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/', $v)) {
                return '';
            }
            $h = strtolower(substr($v, 1));

            return '#' . (strlen($h) === 3 ? $h[0] . $h[0] . $h[1] . $h[1] . $h[2] . $h[2] : $h);
        case 'range':
            return max($o['min'], min($o['max'], (int) $v));
        case 'media':
            return esc_url_raw((string) $v);
        default:
            return sanitize_text_field((string) $v);
    }
}

/** Attribute und CSS-Variablen für <meta name="mt-config">. */
function mt_config(): array
{
    $attrs = [];
    $vars = [];
    foreach (mt_catalog()['options'] as $id => $o) {
        if (isset($o['attr'])) {
            $v = mt_opt($id);
            $attrs[$o['attr']] = $o['type'] === 'toggle' ? ($v ? '1' : '0') : (string) $v;
        }
        if (isset($o['css'])) {
            $v = mt_opt($id);
            $vars[$o['css']] = ($v === '' || $v === null) ? '' : $v . ($o['unit'] ?? '');
        }
    }

    return ['attrs' => $attrs, 'vars' => $vars];
}

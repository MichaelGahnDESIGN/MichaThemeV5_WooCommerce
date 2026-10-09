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
    $v = get_theme_mod($o['key'], $o['default']);
    if ($o['type'] === 'code') {
        return is_string($v) ? $v : ''; // Beim Speichern bereinigt, Ausgabe nur mit Pro-Lizenz (inc/license.php).
    }

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
            return is_string($v) && preg_match('/^#[0-9a-fA-F]{6}$/', $v) ? $v : '';
        case 'range':
            return max($o['min'], min($o['max'], (int) $v));
        case 'media':
            return esc_url_raw((string) $v);
        case 'code':
            // Nur Konten mit Recht für ungefiltertes HTML dürfen Code speichern.
            return is_string($v) && function_exists('current_user_can') && current_user_can('unfiltered_html') ? $v : '';
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

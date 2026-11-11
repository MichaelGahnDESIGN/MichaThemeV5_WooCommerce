<?php
// Beim Löschen des Plugins: nur eigene Daten entfernen. Theme bleibt, nur der Lizenzcode wandert zurück in die Theme-Einstellung.
defined('WP_UNINSTALL_PLUGIN') || exit;

// Lizenzcode zurück ins Theme, sonst ist er nach dem Löschen weg (Theme ohne Plugin liest die Theme-Einstellung)
// gezielt ins MichaTheme schreiben (Ordnername egal, erkannt an der Text Domain), nicht ins gerade aktive Theme
$mt_code = get_option('michatheme_v5_license', '');
if (is_string($mt_code) && $mt_code !== '') {
    foreach (wp_get_themes() as $mt_slug => $mt_theme) {
        if ($mt_theme->get('TextDomain') !== 'michatheme-v5') {
            continue;
        }
        $mt_mods = get_option('theme_mods_' . $mt_slug, []);
        $mt_mods = is_array($mt_mods) ? $mt_mods : [];
        if ((string) ($mt_mods['mt-license-token'] ?? '') === '') {
            $mt_mods['mt-license-token'] = $mt_code;
            update_option('theme_mods_' . $mt_slug, $mt_mods);
        }
    }
}
foreach (['michatheme_v5_license', 'michatheme_v5_palette', 'michatheme_v5_state', 'michatheme_v5_settings_backup', 'michatheme_v5_release'] as $opt) {
    delete_option($opt);
}
foreach (['cat_fresh', 'cat_good', 'cat_pause', 'cat_last_refresh', 'recheck'] as $t) {
    delete_transient('michatheme_v5_' . $t);
}
delete_site_transient('michatheme_v5_release');

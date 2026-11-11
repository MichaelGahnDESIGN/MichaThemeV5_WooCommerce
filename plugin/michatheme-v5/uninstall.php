<?php
// Beim Löschen des Plugins: nur eigene Daten entfernen. Theme bleibt, nur der Lizenzcode wandert zurück in die Theme-Einstellung.
defined('WP_UNINSTALL_PLUGIN') || exit;

// Lizenzcode zurück ins Theme, sonst ist er nach dem Löschen weg (Theme ohne Plugin liest die Theme-Einstellung)
$mt_code = get_option('michatheme_v5_license', '');
if (is_string($mt_code) && $mt_code !== '' && (string) get_theme_mod('mt-license-token', '') === '') {
    set_theme_mod('mt-license-token', $mt_code);
}
foreach (['michatheme_v5_license', 'michatheme_v5_palette', 'michatheme_v5_state', 'michatheme_v5_settings_backup', 'michatheme_v5_release'] as $opt) {
    delete_option($opt);
}
foreach (['cat_fresh', 'cat_good', 'cat_pause', 'cat_last_refresh', 'recheck'] as $t) {
    delete_transient('michatheme_v5_' . $t);
}
delete_site_transient('michatheme_v5_release');

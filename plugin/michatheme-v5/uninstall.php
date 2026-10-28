<?php
// Beim Löschen des Plugins: nur eigene Daten entfernen. Theme-Einstellungen (theme_mods) und das Theme bleiben unangetastet.
defined('WP_UNINSTALL_PLUGIN') || exit;

foreach (['michatheme_v5_license', 'michatheme_v5_palette', 'michatheme_v5_state', 'michatheme_v5_settings_backup', 'michatheme_v5_release'] as $opt) {
    delete_option($opt);
}
foreach (['cat_fresh', 'cat_good', 'cat_pause', 'cat_last_refresh', 'recheck'] as $t) {
    delete_transient('michatheme_v5_' . $t);
}
delete_site_transient('michatheme_v5_release');

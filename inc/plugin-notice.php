<?php
defined('ABSPATH') || exit;

/**
 * Freundlicher Hinweis im Admin, wenn das Plugin MichaThemeV5 fehlt. Nur für Verwalter, nur Dashboard und Themes,
 * schließbar (30 Tage je Benutzer). Nichts im Shop (Frontend), keine Fehler. Das Theme läuft auch ohne Plugin.
 */
const MT_NOTICE_DAYS = 30;

add_action('admin_notices', function (): void {
    if (class_exists('MT_Bridge', false) || !current_user_can('manage_options')) {
        return;
    }
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen || !in_array($screen->id, ['dashboard', 'themes'], true)) {
        return;
    }
    if ((int) get_user_meta(get_current_user_id(), 'mt_plugin_notice_until', true) > time()) {
        return;
    }
    $url = wp_nonce_url(admin_url('admin-post.php?action=mt_dismiss_plugin_notice'), 'mt_dismiss_plugin_notice');
    echo '<div class="notice notice-info"><p><strong>MichaThemeV5:</strong> Mit dem Plugin MichaThemeV5 bekommen Sie eine übersichtliche Einstellungsseite, '
        . 'die Lizenzverwaltung und Plugin-Empfehlungen. Das Theme funktioniert auch ohne das Plugin. '
        . 'Das Plugin installieren Sie unter Plugins › Installieren › Plugin hochladen.</p>'
        . '<p><a href="' . esc_url($url) . '">Hinweis für ' . (int) MT_NOTICE_DAYS . ' Tage ausblenden</a></p></div>';
});

add_action('admin_post_mt_dismiss_plugin_notice', function (): void {
    if (!current_user_can('manage_options')) {
        wp_die('Keine Berechtigung.', '', ['response' => 403]);
    }
    check_admin_referer('mt_dismiss_plugin_notice');
    update_user_meta(get_current_user_id(), 'mt_plugin_notice_until', time() + MT_NOTICE_DAYS * DAY_IN_SECONDS);
    wp_safe_redirect(wp_get_referer() ?: admin_url());
    exit;
});

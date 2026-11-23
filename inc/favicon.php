<?php
/**
 * MichaTheme-Favicon als Rückfall.
 * Eigenes Icon: Design > Customizer > Website-Identität > Website-Icon (WordPress-Funktion).
 * Ohne Website-Icon zeigt der Shop wieder das Michael-Gahn-DESIGN-Logo.
 */

defined('ABSPATH') || exit;

// Pfad zum Logo, ein Filter falls jemand was anderes will
function mt_default_favicon_url(): string
{
    return (string) apply_filters('mt_default_favicon_url', get_template_directory_uri() . '/assets/mt/mt-favicon.png');
}

// <head>: nur wenn kein eigenes Website-Icon da ist
add_action('wp_head', static function (): void {
    if (has_site_icon()) {
        return;
    }
    $url = esc_url(mt_default_favicon_url());
    echo '<link rel="icon" href="' . $url . '" type="image/png" sizes="512x512">' . "\n";
    echo '<link rel="apple-touch-icon" href="' . $url . '">' . "\n";
}, 99);

// /favicon.ico: WP zeigt sonst das W-Logo
add_action('do_faviconico', static function (): void {
    if (has_site_icon()) {
        return;
    }
    wp_safe_redirect(mt_default_favicon_url(), 302, 'MichaThemeV5');
    exit;
}, 5);

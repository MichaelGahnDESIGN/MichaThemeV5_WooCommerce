<?php
/**
 * Bindet das separate MGD Notification Center ein.
 *
 * Kundendaten und REST-Routen gehören absichtlich nicht ins Theme. Das Theme
 * rendert ausschließlich den Shortcode des aktivierten Plugins.
 *
 * @package MichaThemeV5
 */

defined('ABSPATH') || exit;

function mt_notification_center_available(): bool {
    return shortcode_exists('mgd_notification_center');
}

function mt_notification_center_placement(): string {
    $allowed = ['menu', 'footer', 'floating-top-left', 'floating-top-right', 'floating-bottom-left', 'floating-bottom-right', 'none'];
    $placement = get_theme_mod('mt_notification_center_placement', 'menu');
    return in_array($placement, $allowed, true) ? $placement : 'menu';
}

function mt_notification_center_render(string $placement): void {
    if (!mt_notification_center_available() || mt_notification_center_placement() !== $placement) {
        return;
    }
    echo do_shortcode('[mgd_notification_center placement="' . esc_attr($placement) . '"]'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/** Das Theme übernimmt die sichtbare Platzierung und verhindert eine doppelte Plugin-Glocke. */
add_filter('mgd_notification_center_auto_placement', function (string $placement): string {
    return mt_notification_center_available() ? 'none' : $placement;
});

add_action('customize_register', function (WP_Customize_Manager $customize): void {
    $customize->add_section('mt_notification_center', ['title' => __('Notification Center', 'michatheme-v5'), 'priority' => 72]);
    $customize->add_setting('mt_notification_center_placement', ['default' => 'menu', 'sanitize_callback' => 'sanitize_key']);
    $customize->add_control('mt_notification_center_placement', ['section' => 'mt_notification_center', 'label' => __('Platzierung', 'michatheme-v5'), 'type' => 'select', 'choices' => ['menu' => 'Hauptmenü', 'footer' => 'Footer', 'floating-top-left' => 'Schwebend oben links', 'floating-top-right' => 'Schwebend oben rechts', 'floating-bottom-left' => 'Schwebend unten links', 'floating-bottom-right' => 'Schwebend unten rechts', 'none' => 'Nur per Shortcode']]);
});

add_action('wp_enqueue_scripts', function (): void {
    if (mt_notification_center_available()) {
        wp_enqueue_style('mt-notification-center', get_template_directory_uri() . '/assets/mt-notification-center.css', [], MT_VERSION);
    }
});

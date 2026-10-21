<?php
defined('ABSPATH') || exit;

add_action('after_setup_theme', function (): void {
    load_theme_textdomain('michathemev5', get_template_directory() . '/languages');
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script']);
    add_theme_support('responsive-embeds');
    add_theme_support('custom-logo', ['height' => 96, 'width' => 320, 'flex-height' => true, 'flex-width' => true]);
    add_theme_support('align-wide');
    add_theme_support('wp-block-styles');
    register_nav_menus(['primary' => 'Hauptmenü', 'footer' => 'Footer-Menü (Rechtliches)']);
});

/** Meta-Brücke und blockierendes Start-Skript im <head>. */
add_action('wp_head', function (): void {
    $json = wp_json_encode(mt_config(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    echo '<meta name="mt-config" content="' . esc_attr((string) $json) . '">' . "\n";
    echo '<script src="' . esc_url(get_template_directory_uri() . '/assets/mt/mt-init.js?ver=' . MT_VERSION) . '"></script>' . "\n";
    if (mt_opt('perf_preload_fonts')) {
        echo '<link rel="preload" href="' . esc_url(get_template_directory_uri() . '/assets/mt/fonts/inter-latin-400-normal.woff2') . '" as="font" type="font/woff2" crossorigin>' . "\n";
    }
}, 1);

add_action('wp_enqueue_scripts', function (): void {
    $base = get_template_directory_uri() . '/assets/mt/';
    foreach (['mt-fonts', 'mt-tokens', 'mt-base'] as $h) {
        wp_enqueue_style($h, $base . $h . '.css', [], MT_VERSION);
    }
    wp_enqueue_style('mt-woo', get_template_directory_uri() . '/assets/mt-woo.css', ['mt-base'], MT_VERSION);
    wp_enqueue_script('mt-theme', $base . 'mt-theme.js', [], MT_VERSION, ['strategy' => 'defer', 'in_footer' => true]);
});

/** Kein Emoji-Skript, keine Embeds von Dritten ohne Einwilligung: weniger Anfragen, mehr Datenschutz. */
add_action('init', function (): void {
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('wp_print_styles', 'print_emoji_styles');
});

/** Bilder erst beim Scrollen laden, sofern gewünscht. */
add_filter('wp_lazy_loading_enabled', static fn ($default) => mt_opt('perf_lazy') ? $default : false);

/** Logo (hell/dunkel) ausgeben. */
function mt_logo(): void
{
    $light = mt_opt('logo_light');
    $dark = mt_opt('logo_dark');
    $alt = mt_opt('logo_alt') ?: get_bloginfo('name');
    if (!$light) {
        if (has_custom_logo()) {
            the_custom_logo();
        } else {
            echo '<a class="mt-site-title" href="' . esc_url(home_url('/')) . '">' . esc_html(get_bloginfo('name')) . '</a>';
        }

        return;
    }
    echo '<a href="' . esc_url(home_url('/')) . '" class="mt-logo-link' . ($dark ? ' mt-logo--has-dark' : '') . '">';
    echo '<img class="mt-logo mt-logo--light" src="' . esc_url($light) . '" alt="' . esc_attr($alt) . '">';
    if ($dark) {
        echo '<img class="mt-logo mt-logo--dark" src="' . esc_url($dark) . '" alt="" aria-hidden="true">';
    }
    echo '</a>';
}

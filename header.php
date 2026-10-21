<?php
/**
 * Kopfbereich.
 *
 * @package MichaThemeV5
 */
defined('ABSPATH') || exit;
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="mt-skip" href="#mt-main">Zum Inhalt springen</a>
<?php if (mt_opt('announcement') && mt_opt('announcement_text') !== '') : ?>
    <div class="mt-announcement" role="note"><?php echo esc_html(mt_opt('announcement_text')); ?></div>
<?php endif; ?>
<header class="site-header" role="banner">
    <div class="site-header__inner">
        <div class="site-header__brand"><?php mt_logo(); ?></div>
        <nav class="site-header__nav" aria-label="Hauptmenü">
            <?php wp_nav_menu(['theme_location' => 'primary', 'container' => false, 'menu_class' => 'mt-menu', 'fallback_cb' => false]); ?>
        </nav>
        <div class="site-header__tools">
            <?php get_search_form(); ?>
            <?php if (function_exists('wc_get_cart_url')) : ?>
                <a class="mt-cart-link" href="<?php echo esc_url(wc_get_cart_url()); ?>" aria-label="Warenkorb">
                    <span aria-hidden="true">🛒</span> <span class="mt-cart-count"><?php echo WC()->cart ? (int) WC()->cart->get_cart_contents_count() : 0; ?></span>
                </a>
            <?php endif; ?>
            <button type="button" class="mt-mode-toggle" data-mt-mode-toggle aria-label="Farbmodus wechseln">
                <span aria-hidden="true">◐</span> <span data-mt-mode-label>Wie das Gerät</span>
            </button>
        </div>
    </div>
</header>
<main id="mt-main" class="site-main" tabindex="-1">

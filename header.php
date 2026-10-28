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
        <div class="site-header__search"><?php get_search_form(); ?></div>
        <div class="site-header__tools">
            <?php if (mt_mode_toggle_at('header')) { echo mt_mode_toggle_html('header'); } // phpcs:ignore WordPress.Security.EscapeOutput -- in mt_mode_toggle_html escaped ?>
            <?php mt_notification_center_render('menu'); ?>
            <?php echo mt_wishlist_item_html(); // phpcs:ignore WordPress.Security.EscapeOutput -- in mt_wishlist_item_html escaped ?>
            <?php echo mt_fav_header_html(); // phpcs:ignore WordPress.Security.EscapeOutput -- in mt_fav_header_html escaped ?>
            <?php if (function_exists('wc_get_page_permalink')) : ?>
                <a class="mt-icon-link mt-account-link" href="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>" aria-label="Mein Konto"><?php echo mt_icon('user'); // phpcs:ignore WordPress.Security.EscapeOutput -- feste SVG-Zeichenkette ?></a>
            <?php endif; ?>
            <?php if (function_exists('wc_get_cart_url')) : ?>
                <?php echo mt_cart_link_html(); // phpcs:ignore WordPress.Security.EscapeOutput -- in mt_cart_link_html escaped ?>
            <?php endif; ?>
        </div>
        <nav class="site-header__nav" aria-label="Hauptmenü">
            <?php wp_nav_menu(['theme_location' => 'primary', 'container' => false, 'menu_class' => 'mt-menu', 'fallback_cb' => false]); ?>
        </nav>
    </div>
</header>
<main id="mt-main" class="site-main" tabindex="-1">

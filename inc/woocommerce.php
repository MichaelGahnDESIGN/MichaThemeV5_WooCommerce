<?php
defined('ABSPATH') || exit;

add_action('after_setup_theme', function (): void {
    add_theme_support('woocommerce');
    add_theme_support('wc-product-gallery-zoom');
    add_theme_support('wc-product-gallery-lightbox');
    add_theme_support('wc-product-gallery-slider');
});

/** Pflichtangabe neben dem Preis (PAngV): Hinweistext, optional mit Link auf eine Seite "versandkosten". */
add_filter('woocommerce_get_price_html', function ($html, $product) {
    if ($html === '' || is_admin()) {
        return $html;
    }
    $note = mt_opt('legal_price_note');
    if ($note === '') {
        return $html;
    }
    $page = get_page_by_path('versandkosten');
    $text = esc_html($note);
    if ($page) {
        $text = esc_html(preg_replace('/zzgl\.?\s*Versand/u', '', $note)) . ' <a href="' . esc_url(get_permalink($page)) . '">zzgl. Versandkosten</a>';
    }

    return $html . ' <span class="mt-price__note">' . $text . '</span>';
}, 20, 2);

/** Spalten und Anzahl je Seite aus den Optionen. */
add_filter('loop_shop_columns', static fn () => (int) mt_opt('listing_columns'));

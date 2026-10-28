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
    if ($html === '' || is_admin() || mt_legal_plugin_active()) {
        return $html; // Germanized / German Market setzen den Hinweis selbst
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

/** Warenkorb-Link im Kopfbereich: Icon, Zähler als Pille, Zahl steckt im aria-label */
function mt_cart_link_html(): string
{
    $count = (function_exists('WC') && WC()->cart) ? (int) WC()->cart->get_cart_contents_count() : 0;
    $label = $count > 0
        ? sprintf(esc_attr__('Warenkorb, %d Artikel', 'michathemev5'), $count)
        : esc_attr__('Warenkorb', 'michathemev5');
    $pill = $count > 0 ? '<span class="mt-cart-count" aria-hidden="true">' . ($count > 99 ? '99+' : (string) $count) . '</span>' : '';

    return '<a class="mt-cart-link" href="' . esc_url(wc_get_cart_url()) . '" aria-label="' . $label . '">' . mt_icon('bag') . $pill . '</a>';
}

/** Zähler aktualisiert sich nach "In den Warenkorb" ohne Neuladen (Woo-Fragmente) */
add_filter('woocommerce_add_to_cart_fragments', static function (array $fragments): array {
    $fragments['a.mt-cart-link'] = mt_cart_link_html();

    return $fragments;
});

/** Sonderpreis-Siegel: Text statt Prozentzahl (Prozent nur gegen den 30-Tage-Tiefstpreis, das entscheidet der Händler) */
add_filter('woocommerce_sale_flash', static function ($html, $post = null, $product = null): string {
    return '<span class="onsale">' . esc_html__('Sonderpreis', 'michathemev5') . '</span>';
}, 10, 3);

/** Satz über "Zur Kasse": Gesamtbetrag und Versand sieht man in der Kasse vor dem Bestellen */
function mt_checkout_hint_html(): string
{
    return '<p class="mt-checkout-hint">' . esc_html__('Versandkosten und Gesamtbetrag sehen Sie, bevor Sie bestellen.', 'michathemev5') . '</p>';
}
add_action('woocommerce_proceed_to_checkout', static function (): void {
    echo mt_checkout_hint_html(); // phpcs:ignore WordPress.Security.EscapeOutput -- oben escaped
}, 5);
add_filter('render_block_woocommerce/proceed-to-checkout-block', static fn ($html) => mt_checkout_hint_html() . $html, 9);

<?php
/**
 * Icon-Leiste im Kopfbereich: Hell/Dunkel-Schalter, Glocke, Merkliste, Favoriten.
 *
 * @package MichaThemeV5
 */
defined('ABSPATH') || exit;

/** Soll der Hell/Dunkel-Schalter an diesem Ort stehen? (Ort kommt aus dem Customizer) */
function mt_mode_toggle_at(string $place): bool
{
    return (bool) mt_opt('mode_toggle') && mt_opt('mode_toggle_place') === $place;
}

/** Schalter mit 3 Symbolen (System/Hell/Dunkel). Header = nur Icon, Footer/Konto = mit sichtbarem Text. */
function mt_mode_toggle_html(string $place): string
{
    $svg = static fn (string $cls, string $inner): string => '<svg class="mt-mode-ico mt-mode-ico--' . $cls . '" aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . $inner . '</svg>';
    $icons = $svg('system', '<circle cx="12" cy="12" r="9"/><path d="M12 3a9 9 0 0 1 0 18z" fill="currentColor"/>')
        . $svg('light', '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>')
        . $svg('dark', '<path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/>');
    $icon_only = $place === 'header';
    $label = esc_attr__('Farbmodus: Wie das Gerät. Zum Wechseln klicken.', 'michathemev5');

    return '<button type="button" class="mt-mode-toggle' . ($icon_only ? ' mt-mode-toggle--icon' : '') . '" data-mt-mode-toggle data-mt-place="' . esc_attr($place) . '"'
        . ' data-label-system="' . esc_attr__('Wie das Gerät', 'michathemev5') . '" data-label-light="' . esc_attr__('Hell', 'michathemev5') . '" data-label-dark="' . esc_attr__('Dunkel', 'michathemev5') . '"'
        . ' data-label-pattern="' . esc_attr__('Farbmodus: %s. Zum Wechseln klicken.', 'michathemev5') . '"'
        . ' aria-label="' . $label . '" title="' . $label . '">' . $icons
        . ($icon_only ? '' : '<span data-mt-mode-label>' . esc_html__('Wie das Gerät', 'michathemev5') . '</span>')
        . '</button>';
}

/** Zeile "Farbmodus" für Footer bzw. Konto */
function mt_mode_toggle_row(string $place): void
{
    if (!mt_mode_toggle_at($place)) {
        return;
    }
    echo '<p class="mt-mode-row mt-mode-row--' . esc_attr($place) . '"><span class="mt-mode-row__label">' . esc_html__('Farbmodus', 'michathemev5') . '</span> ' . mt_mode_toggle_html($place) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput -- oben escaped
}

// Konto: unter der Konto-Navigation (nur für eingeloggte Kunden sichtbar)
add_action('woocommerce_after_account_navigation', static function (): void {
    mt_mode_toggle_row('account');
});

/** Merkliste: nur wenn ein bekanntes Wunschlisten-Plugin aktiv ist, sonst null (Icon entfällt) */
function mt_wishlist_info(): ?array
{
    // YITH WooCommerce Wishlist - yith_wcwl_count_products() aus functions-yith-wcwl.php
    if (function_exists('YITH_WCWL') && function_exists('yith_wcwl_count_products')) {
        return ['count' => (int) yith_wcwl_count_products(), 'url' => (string) YITH_WCWL()->get_wishlist_url()];
    }
    // TI WooCommerce Wishlist - tinvwl_get_wishlist_products() + tinv_url_wishlist_default()
    if (function_exists('tinvwl_get_wishlist_products') && function_exists('tinv_url_wishlist_default')) {
        $items = tinvwl_get_wishlist_products();

        return ['count' => is_array($items) ? count($items) : 0, 'url' => (string) tinv_url_wishlist_default()];
    }

    return null;
}

function mt_wishlist_item_html(): string
{
    $info = mt_wishlist_info();
    if ($info === null || $info['url'] === '') {
        return '';
    }
    $n = max(0, $info['count']);
    $label = $n > 0 ? sprintf(esc_attr__('Merkliste, %d Artikel', 'michathemev5'), $n) : esc_attr__('Merkliste', 'michathemev5');
    $badge = $n > 0 ? '<span class="mt-list-count" aria-hidden="true">' . ($n > 99 ? '99+' : (string) $n) . '</span>' : '';

    return '<div class="mt-hdr-item mt-hdr-item--wish' . ($n === 0 ? ' mt-hdr-item--empty' : '') . '"><a class="mt-icon-link" href="' . esc_url($info['url']) . '" aria-label="' . $label . '">' . mt_icon('bookmark') . $badge . '</a></div>';
}

/** Favoriten (Herz-Liste im Browser) im Header, bleibt hidden bis mt-theme.js Einträge findet */
function mt_fav_header_html(): string
{
    if (!mt_opt('favorites')) {
        return '';
    }

    return '<div class="mt-hdr-item mt-hdr-item--favs" data-mt-fav-item hidden>'
        . '<button type="button" class="mt-icon-link" data-mt-fav-open aria-expanded="false" aria-controls="mt-fav-panel" data-label-count="' . esc_attr__('Favoriten, %s Einträge', 'michathemev5') . '" data-label-count-one="' . esc_attr__('Favoriten, %s Eintrag', 'michathemev5') . '" aria-label="' . esc_attr__('Favoriten', 'michathemev5') . '">'
        . mt_icon('heart') . '<span class="mt-list-count" data-mt-fav-count aria-hidden="true">0</span></button>'
        . '<section id="mt-fav-panel" class="mt-fav-panel" data-mt-fav-panel data-label-remove="' . esc_attr__('Aus Favoriten entfernen: %s', 'michathemev5') . '" aria-label="' . esc_attr__('Favoriten', 'michathemev5') . '" hidden><ul class="mt-fav-list" data-mt-fav-list></ul></section>'
        . '</div>';
}

/** Herz-Button für Karte (card=true, absolut oben rechts) oder Artikelseite */
function mt_fav_toggle_html(int $id, string $title, string $url, bool $card): string
{
    if (!mt_opt('favorites') || $id <= 0 || $title === '' || $url === '') {
        return '';
    }
    $add = esc_attr__('Zu Favoriten hinzufügen: %s', 'michathemev5');

    return '<button type="button" class="mt-fav-toggle' . ($card ? ' mt-fav-toggle--card' : '') . '" data-mt-fav-toggle data-mt-fav-id="' . esc_attr((string) $id) . '" data-mt-fav-title="' . esc_attr($title) . '" data-mt-fav-url="' . esc_attr($url) . '"'
        . ' aria-pressed="false" aria-label="' . esc_attr(str_replace('%s', $title, $add)) . '" data-label-add="' . $add . '" data-label-remove="' . esc_attr__('Aus Favoriten entfernen: %s', 'michathemev5') . '">'
        . '<svg aria-hidden="true" focusable="false" viewBox="0 0 24 24"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 0 0-7.8 7.8l1 1.1L12 21l7.8-7.5 1-1.1a5.5 5.5 0 0 0 0-7.8z"/></svg></button>';
}

function mt_fav_for_product(bool $card): string
{
    global $product;
    if (!($product instanceof WC_Product)) {
        return '';
    }

    // Link nur Pfad (gleiche Seite), Kern erlaubt nur eigene Domain
    return mt_fav_toggle_html($product->get_id(), wp_strip_all_tags($product->get_name()), wp_make_link_relative(get_permalink($product->get_id())), $card);
}

// Herz auf Karten (Listing, Suche, Verwandte) - vor dem Produktlink, Karte ist position: relative
add_action('woocommerce_before_shop_loop_item', static function (): void {
    echo mt_fav_for_product(true); // phpcs:ignore WordPress.Security.EscapeOutput -- in mt_fav_toggle_html escaped
}, 5);

// Herz auf Artikelseite, direkt nach dem Titel
add_action('woocommerce_single_product_summary', static function (): void {
    echo '<p class="mt-fav-single">' . mt_fav_for_product(false) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput -- in mt_fav_toggle_html escaped
}, 6);

// mt-woo.js > Glocke ein/aus, Herzen nach Ajax
add_action('wp_enqueue_scripts', static function (): void {
    wp_enqueue_script('mt-woo-js', get_template_directory_uri() . '/assets/mt-woo.js', ['mt-theme'], MT_VERSION, ['strategy' => 'defer', 'in_footer' => true]);
});

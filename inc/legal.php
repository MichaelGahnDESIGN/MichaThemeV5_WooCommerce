<?php
defined('ABSPATH') || exit;

// Recht Paket 1: Lieferzeit, Grundpreis, 30-Tage-Preis, Gewährleistung, GPSR, Ausverkauft, Widerruf, Video 2-Klick
// Technische Hinweise, keine Rechtsberatung. Texte rechtlich prüfen lassen.

/** Germanized / German Market aktiv? Dann machen die Plugins Lieferzeit, Grundpreis und Preishinweis */
function mt_legal_plugin_active(): bool
{
    $active = class_exists('WooCommerce_Germanized') || function_exists('wc_gzd_get_product')
        || class_exists('Woocommerce_German_Market') || class_exists('WGM_Template');

    return (bool) apply_filters('mt_legal_plugin_active', $active);
}

/** Custom Field als Text, leer wenn nicht da */
function mt_meta_text($product, string $key): string
{
    return $product instanceof WC_Product ? trim((string) $product->get_meta($key, true)) : '';
}

/** Lieferzeit, Grundpreis, 30-Tage-Preis unter dem Preis */
function mt_legal_price_extras($product): string
{
    if (!$product instanceof WC_Product) {
        return '';
    }
    $out = '';
    $plugin = mt_legal_plugin_active();

    if (mt_opt('legal_unit_price') && !$plugin) {
        $unit = mt_meta_text($product, '_mt_unit_price');
        if ($unit !== '') {
            $out .= '<span class="mt-unit-price">' . esc_html__('Grundpreis:', 'michathemev5') . ' ' . esc_html($unit) . '</span>';
        }
    }
    if (mt_opt('legal_lowest_price') === 'field' && $product->is_on_sale()) {
        $low = mt_meta_text($product, '_mt_lowest_price_30');
        if ($low !== '' && is_numeric(str_replace(',', '.', $low))) {
            $out .= '<span class="mt-lowest-price">' . esc_html__('Niedrigster Preis der letzten 30 Tage:', 'michathemev5') . ' '
                . wp_kses_post(wc_price((float) str_replace(',', '.', $low))) . '</span>';
        } elseif (current_user_can('manage_woocommerce')) {
            // Hinweis nur fuer Shop-Verwalter, kein erfundener Wert
            $out .= '<span class="mt-legal-hint">' . esc_html__('Hinweis für Shop-Verwalter: Streichpreis ohne Custom Field _mt_lowest_price_30 (niedrigster Preis der letzten 30 Tage).', 'michathemev5') . '</span>';
        }
    }

    return $out;
}

/** Artikelseite: Preis-Extras + Lieferzeit direkt unter dem Preis */
add_action('woocommerce_single_product_summary', function (): void {
    global $product;
    $out = mt_legal_price_extras($product);
    if (!mt_legal_plugin_active()) {
        $time = mt_meta_text($product, '_mt_delivery_time') ?: (string) mt_opt('legal_shipping_time');
        if ($time !== '') {
            $out .= '<span class="mt-delivery-time">' . esc_html($time) . '</span>';
        }
    }
    if ($out !== '') {
        echo '<div class="mt-legal-extras">' . $out . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput -- oben escaped
    }
}, 11);

/** Listen: Grundpreis und 30-Tage-Preis */
add_action('woocommerce_after_shop_loop_item_title', function (): void {
    global $product;
    $out = mt_legal_price_extras($product);
    if ($out !== '') {
        echo '<div class="mt-legal-extras">' . $out . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput -- oben escaped
    }
}, 12);

/** Gewährleistung Hinweis (Artikelseite) */
function mt_warranty_html(): string
{
    $text = trim((string) mt_opt('legal_warranty_text'));
    if (!mt_opt('legal_warranty') || $text === '') {
        return '';
    }

    return '<p class="mt-warranty" role="note">' . esc_html($text) . '</p>';
}
add_action('woocommerce_single_product_summary', function (): void {
    echo mt_warranty_html(); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in mt_warranty_html
}, 40);
/** Gewährleistung Warenkorb: klassisch und Block-Warenkorb */
add_action('woocommerce_before_cart_totals', function (): void {
    echo mt_warranty_html(); // phpcs:ignore WordPress.Security.EscapeOutput
});
add_filter('render_block_woocommerce/proceed-to-checkout-block', static fn ($html) => mt_warranty_html() . $html);

/** GPSR aus Custom Fields > Reiter Produktsicherheit */
function mt_gpsr_data($product): array
{
    $rows = [
        __('Hersteller', 'michathemev5') => mt_meta_text($product, '_mt_gpsr_manufacturer'),
        __('Verantwortliche Person in der EU', 'michathemev5') => mt_meta_text($product, '_mt_gpsr_responsible_person'),
        __('Kontakt', 'michathemev5') => mt_meta_text($product, '_mt_gpsr_contact'),
        __('Sicherheitshinweise', 'michathemev5') => mt_meta_text($product, '_mt_gpsr_safety_info'),
    ];

    return array_filter((array) apply_filters('mt_gpsr_data', $rows, $product), static fn ($v) => is_string($v) && trim($v) !== '');
}
add_filter('woocommerce_product_tabs', function (array $tabs): array {
    global $product;
    if (!mt_opt('legal_gpsr') || !mt_gpsr_data($product)) {
        return $tabs;
    }
    $tabs['mt_gpsr'] = [
        'title' => __('Produktsicherheit', 'michathemev5'),
        'priority' => 40,
        'callback' => function (): void {
            global $product;
            echo '<h2>' . esc_html__('Produktsicherheit', 'michathemev5') . '</h2><dl class="mt-gpsr">';
            foreach (mt_gpsr_data($product) as $label => $value) {
                echo '<dt>' . esc_html($label) . '</dt><dd>' . wp_kses_post(wpautop(esc_html($value))) . '</dd>';
            }
            echo '</dl>';
        },
    ];

    return $tabs;
});

/** Ausverkauft: Liste ausblenden oder kennzeichnen, Button auf der Artikelseite deaktiviert */
add_action('woocommerce_product_query', function (WP_Query $q): void {
    if (mt_opt('soldout_display') !== 'hide') {
        return;
    }
    $tax = (array) $q->get('tax_query');
    $tax[] = ['taxonomy' => 'product_visibility', 'field' => 'name', 'terms' => ['outofstock'], 'operator' => 'NOT IN'];
    $q->set('tax_query', $tax);
});
add_action('woocommerce_before_shop_loop_item_title', function (): void {
    global $product;
    if ($product instanceof WC_Product && !$product->is_in_stock()) {
        echo '<span class="mt-soldout">' . esc_html__('Ausverkauft', 'michathemev5') . '</span>';
    }
}, 9);
add_action('woocommerce_single_product_summary', function (): void {
    global $product;
    if ($product instanceof WC_Product && !$product->is_in_stock()) {
        echo '<p><button type="button" class="button alt mt-soldout-btn" disabled aria-disabled="true">' . esc_html__('Ausverkauft', 'michathemev5') . '</button></p>';
    }
}, 31);

/** Wiederrufs Link: Seite oder Woo-Endpunkt (Order withdrawal ab 11.1) */
function mt_withdrawal_url(): string
{
    $mode = (string) mt_opt('legal_withdrawal_link');
    if ($mode === 'native' && function_exists('wc_get_account_endpoint_url')) {
        return (string) wc_get_account_endpoint_url('order-withdrawal');
    }
    if ($mode === 'page') {
        $id = (int) mt_opt('legal_withdrawal_page');
        if ($id > 0 && get_post_status($id) === 'publish') {
            return (string) get_permalink($id);
        }
    }

    return '';
}
function mt_a11y_statement_url(): string
{
    $id = (int) mt_opt('a11y_statement_page');

    return $id > 0 && get_post_status($id) === 'publish' ? (string) get_permalink($id) : '';
}
/** Links im Footer: Widerruf, Barrierefreiheit */
function mt_legal_footer_items(): array
{
    $items = [];
    if (mt_withdrawal_url() !== '') {
        $items[] = ['label' => __('Vertrag widerrufen', 'michathemev5'), 'url' => mt_withdrawal_url(), 'class' => 'mt-withdraw-link'];
    }
    if (mt_a11y_statement_url() !== '') {
        $items[] = ['label' => __('Erklärung zur Barrierefreiheit', 'michathemev5'), 'url' => mt_a11y_statement_url(), 'class' => ''];
    }

    return $items;
}
/** Widerruf auch im Konto */
add_action('woocommerce_before_my_account', function (): void {
    $url = mt_withdrawal_url();
    if ($url !== '') {
        echo '<p><a class="mt-withdraw-link" href="' . esc_url($url) . '">' . esc_html__('Vertrag widerrufen', 'michathemev5') . '</a></p>';
    }
});

/** Video 2-Klick: YouTube/Vimeo erst nach Klick, nichts vorab geladen */
function mt_video_parse(string $url): ?array
{
    $host = strtolower((string) wp_parse_url($url, PHP_URL_HOST));
    $host = preg_replace('/^www\./', '', $host);
    if (in_array($host, ['youtube.com', 'm.youtube.com', 'youtube-nocookie.com', 'youtu.be'], true)) {
        if (preg_match('~(?:[?&]v=|youtu\.be/|/embed/|/shorts/)([A-Za-z0-9_-]{6,20})~', $url, $m)) {
            return ['youtube', $m[1]];
        }
    } elseif (in_array($host, ['vimeo.com', 'player.vimeo.com'], true) && preg_match('~/(\d{1,15})(?:[/?#]|$)~', $url, $m)) {
        return ['vimeo', $m[1]];
    }

    return null;
}
function mt_video_placeholder(string $provider, string $id, string $title = ''): string
{
    $name = $provider === 'vimeo' ? 'Vimeo' : 'YouTube';
    $priv = function_exists('get_privacy_policy_url') ? get_privacy_policy_url() : '';
    $out = '<div class="mt-video" data-mt-video data-mt-video-provider="' . esc_attr($provider) . '" data-mt-video-id="' . esc_attr($id) . '" data-mt-video-title="' . esc_attr($title) . '">';
    $out .= '<div class="mt-video__consent"><p>' . esc_html(sprintf(__('Dieses Video wird erst nach Klick von %s geladen. Dabei werden Daten an den Anbieter übertragen.', 'michathemev5'), $name)) . '</p>';
    if ($priv !== '') {
        $out .= '<p><a href="' . esc_url($priv) . '">' . esc_html__('Datenschutzerklärung', 'michathemev5') . '</a></p>';
    }
    $out .= '<button type="button" class="button mt-video__load" data-mt-video-load>' . esc_html(sprintf(__('Video von %s laden', 'michathemev5'), $name)) . '</button></div></div>';

    return $out;
}
function mt_video_link_fallback(string $url): string
{
    return '<p><a href="' . esc_url($url) . '" rel="noopener">' . esc_html__('Video auf externer Seite öffnen', 'michathemev5') . '</a></p>';
}
// kein Abruf beim Anbieter, auch nicht serverseitig
add_filter('pre_oembed_result', function ($pre, $url) {
    if (!mt_opt('legal_video_2click')) {
        return $pre;
    }
    $v = mt_video_parse((string) $url);

    return $v ? mt_video_placeholder($v[0], $v[1]) : $pre;
}, 10, 2);
// alte oEmbed Caches mit fertigem iframe abfangen
add_filter('embed_oembed_html', function ($html, $url) {
    if (!mt_opt('legal_video_2click') || !is_string($html) || !preg_match('~<iframe[^>]+src=["\'][^"\']*(youtube(?:-nocookie)?\.com|vimeo\.com)~i', $html)) {
        return $html;
    }
    $v = mt_video_parse((string) $url);

    return $v ? mt_video_placeholder($v[0], $v[1]) : mt_video_link_fallback((string) $url);
}, 10, 2);
add_shortcode('mt_video', function ($atts): string {
    $a = shortcode_atts(['url' => '', 'title' => ''], (array) $atts, 'mt_video');
    $v = mt_video_parse((string) $a['url']);

    return $v ? mt_video_placeholder($v[0], $v[1], (string) $a['title']) : '';
});

/** Skript und Stil dazu */
add_action('wp_enqueue_scripts', function (): void {
    $base = get_template_directory_uri() . '/assets/';
    wp_enqueue_style('mt-legal', $base . 'mt-legal.css', ['mt-base'], MT_VERSION);
    wp_enqueue_script('mt-legal', $base . 'mt-legal.js', [], MT_VERSION, ['strategy' => 'defer', 'in_footer' => true]);
});

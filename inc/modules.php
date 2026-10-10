<?php
/**
 * Pro-Module: Popup, Versand-Fortschritt, Bestands-Anzeige, Rabattanzeige.
 * Die Optionen kommen aus inc/mt-catalog.php (Gruppe "modules"). Ohne gültige Lizenz bleibt alles aus.
 *
 * @package MichaThemeV5
 */

defined('ABSPATH') || exit;

require_once __DIR__ . '/price-history.php';

use MichaTheme\Core\PriceHistory;

/** Konfiguration aller freigeschalteten Module: [Modul => [Option-ID => Wert]]. */
function mt_modules_config(): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    $cache = [];
    foreach (mt_catalog()['options'] as $id => $o) {
        if (($o['group'] ?? '') !== 'modules' || !isset($o['pro']) || !mt_pro($o['pro'])) {
            continue;
        }
        $cache[$o['pro']][$id] = mt_opt($id);
    }

    return $cache;
}

add_action('wp_head', function (): void {
    $cfg = mt_modules_config();
    if ($cfg) {
        echo '<meta name="mt-modules" content="' . esc_attr((string) wp_json_encode($cfg)) . '">' . "\n";
    }
}, 2);

add_action('wp_enqueue_scripts', function (): void {
    if (!mt_modules_config()) {
        return;
    }
    $base = get_template_directory_uri() . '/assets/mt/';
    wp_enqueue_style('mt-modules', $base . 'mt-modules.css', ['mt-base'], MT_VERSION);
    wp_enqueue_script('mt-modules', $base . 'mt-modules.js', [], MT_VERSION, ['strategy' => 'defer', 'in_footer' => true]);
}, 20);

/* ---- Versand-Fortschritt (Warenkorb und Mini-Warenkorb) ---- */
function mt_ship_progress_markup(): void
{
    $cfg = mt_modules_config()['versand-progress'] ?? null;
    if (!$cfg || (int) ($cfg['ship_threshold'] ?? 0) <= 0 || !function_exists('WC') || !WC()->cart || WC()->cart->is_empty()) {
        return;
    }
    printf(
        '<div data-mt-ship-progress data-total="%s" data-threshold="%s"></div>',
        esc_attr((string) round((float) WC()->cart->get_displayed_subtotal(), 2)),
        esc_attr((string) (int) $cfg['ship_threshold'])
    );
}
add_action('woocommerce_before_cart_table', 'mt_ship_progress_markup');
add_action('woocommerce_widget_shopping_cart_before_buttons', 'mt_ship_progress_markup');

/* ---- Bestands-Anzeige (nur echter, verwalteter Bestand) ---- */
add_action('woocommerce_single_product_summary', function (): void {
    if (!isset(mt_modules_config()['stock-progress'])) {
        return;
    }
    global $product;
    if (!$product instanceof WC_Product || !$product->managing_stock() || !$product->is_in_stock()) {
        return;
    }
    $qty = $product->get_stock_quantity();
    if (!is_int($qty) && !is_numeric($qty)) {
        return;
    }
    printf('<div data-mt-stock data-stock="%d"></div>', (int) $qty);
}, 26);

/* ---- Rabattanzeige: Bezugspreis ist der niedrigste Preis der 30 Tage vor der Senkung (§ 11 PAngV) ---- */
add_action('woocommerce_after_product_object_save', function ($product): void {
    if (!$product instanceof WC_Product_Simple) {
        return;
    }
    $price = $product->get_price('edit');
    if ($price === '' || !is_numeric($price)) {
        return;
    }
    $hist = get_post_meta($product->get_id(), '_mt_price_history', true);
    $new = PriceHistory::record(is_array($hist) ? $hist : [], (float) $price, time());
    if ($new !== $hist) {
        update_post_meta($product->get_id(), '_mt_price_history', $new);
    }
}, 20);

add_action('woocommerce_single_product_summary', function (): void {
    if (!isset(mt_modules_config()['rabatt'])) {
        return;
    }
    global $product;
    if (!$product instanceof WC_Product_Simple || !$product->is_on_sale()) {
        return;
    }
    $hist = get_post_meta($product->get_id(), '_mt_price_history', true);
    $low = PriceHistory::lowestBeforeCurrent(is_array($hist) ? $hist : [], (float) $product->get_price());
    if ($low === null) {
        return; // ohne belegbaren Vergleich keine Rabattaussage
    }
    $until = $product->get_date_on_sale_to();
    printf(
        '<div data-mt-discount data-current="%s" data-lowest30="%s" data-until="%s"></div>',
        esc_attr((string) $product->get_price()),
        esc_attr((string) $low),
        esc_attr($until ? $until->date('c') : '')
    );
}, 11);

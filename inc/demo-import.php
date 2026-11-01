<?php

declare(strict_types=1);

defined('ABSPATH') || exit;

/**
 * Demo-Inhalte für das WooCommerce-Theme: Import und Entfernen (Werkzeuge > Demo-Inhalte, WP-CLI `wp michatheme demo`).
 *
 * Quelle sind demo-data/products.json und demo-data/images/ im Theme-Ordner. Es gibt keinen Netzwerkzugriff.
 * Alles, was der Import anlegt, trägt die Markierung `_mt_demo = 1` (Produkte, Varianten, Bilder, Kategorien).
 * Entfernen löscht ausschließlich markierte Einträge; echte Produkte, Bilder und Kategorien bleiben unberührt.
 * Die Logik (MT_Demo_Importer) arbeitet gegen MT_Demo_Store und ist ohne WordPress testbar.
 */

/** Liest und prüft den Datensatz. */
final class MT_Demo_Data
{
    /** @return array<string,mixed> */
    public static function load(string $dir): array
    {
        $file = rtrim($dir, '/') . '/products.json';
        if (!is_file($file)) {
            throw new RuntimeException('Demo-Datensatz nicht gefunden.');
        }
        $d = json_decode((string) file_get_contents($file), true);
        if (!is_array($d)) {
            throw new RuntimeException('Demo-Datensatz ist kein gültiges JSON.');
        }
        $errors = self::validate($d, $dir);
        if ($errors) {
            throw new RuntimeException('Demo-Datensatz ungültig: ' . implode('; ', array_slice($errors, 0, 5)));
        }

        return $d;
    }

    /** @return list<string> Fehlertexte, leer = in Ordnung */
    public static function validate(array $d, string $dir): array
    {
        $e = [];
        $prefix = (string) ($d['sku_prefix'] ?? '');
        if ($prefix === '') {
            $e[] = 'sku_prefix fehlt';
        }
        $cats = [];
        foreach ((array) ($d['categories'] ?? []) as $c) {
            $slug = (string) ($c['slug'] ?? '');
            if (!preg_match('/^mtdemo-[a-z0-9-]+$/', $slug) || trim((string) ($c['name'] ?? '')) === '') {
                $e[] = 'Kategorie ungültig: ' . $slug;
            }
            $cats[$slug] = true;
        }
        $skus = [];
        foreach ((array) ($d['products'] ?? []) as $p) {
            $sku = (string) ($p['sku'] ?? '');
            if ($prefix === '' || !str_starts_with($sku, $prefix) || !preg_match('/^[A-Za-z0-9-]+$/', $sku)) {
                $e[] = "SKU ungültig: $sku";
            }
            $all = [$sku];
            foreach ((array) ($p['variations']['options'] ?? []) as $o) {
                $all[] = (string) ($o['sku'] ?? '');
            }
            foreach ($all as $s) {
                if (isset($skus[$s]) || !str_starts_with($s, $prefix)) {
                    $e[] = "SKU doppelt oder ohne Präfix: $s";
                }
                $skus[$s] = true;
            }
            foreach (['title', 'short', 'description', 'delivery_time'] as $k) {
                if (trim((string) ($p[$k] ?? '')) === '') {
                    $e[] = "$sku: $k fehlt";
                }
            }
            if (!is_numeric($p['price'] ?? null) || (float) $p['price'] <= 0) {
                $e[] = "$sku: Preis ungültig";
            }
            if (isset($p['compare_price']) && (!is_numeric($p['compare_price']) || (float) $p['compare_price'] <= (float) ($p['price'] ?? 0))) {
                $e[] = "$sku: Streichpreis ungültig";
            }
            if (!is_int($p['stock'] ?? null) || $p['stock'] < 0) {
                $e[] = "$sku: Bestand ungültig";
            }
            if (!isset($cats[(string) ($p['category'] ?? '')])) {
                $e[] = "$sku: Kategorie unbekannt";
            }
            if (count((array) ($p['images'] ?? [])) !== 3) {
                $e[] = "$sku: genau 3 Bilder erwartet";
            }
            foreach ((array) ($p['images'] ?? []) as $img) {
                if (!preg_match('~^images/[a-z0-9._-]+\.jpg$~', (string) $img) || !is_file(rtrim($dir, '/') . '/' . $img)) {
                    $e[] = "$sku: Bilddatei fehlt oder ungültig: $img";
                }
            }
        }

        return $e;
    }
}

/** Zugriff auf WordPress/WooCommerce; im Test durch eine Attrappe ersetzt. */
interface MT_Demo_Store
{
    /** @return array{id:int,demo:bool}|null */
    public function find_product(string $sku): ?array;

    /** @param array<string,mixed> $p @param list<int> $image_ids */
    public function create_product(array $p, int $term_id, array $image_ids): int;

    public function find_term(string $slug): ?int;

    /** @param array<string,mixed> $c */
    public function create_term(array $c): int;

    public function find_attachment(string $key): ?int;

    public function create_attachment(string $path, string $key, string $title, string $alt): int;

    /** @return list<int> */
    public function demo_products(): array;

    /** @return list<int> */
    public function demo_terms(): array;

    /** @return list<int> */
    public function demo_attachments(): array;

    /** SKU des Produkts (leer, wenn keine) */
    public function product_sku(int $id): string;

    public function delete_product(int $id): void;

    public function delete_term(int $id): void;

    public function delete_attachment(int $id): void;

    /** Nutzt ein (nicht gelöschtes) Produkt oder eine Variante dieses Bild als Artikelbild oder in der Galerie? */
    public function attachment_in_use_by_others(int $id): bool;

    /** Hängen Produkte ohne Demo-Markierung oder fremde Unterkategorien an dieser Kategorie? */
    public function term_in_use_by_others(int $id): bool;
}

final class MT_Demo_Importer
{
    public const SKU_PREFIX = 'MTDEMO-';

    public function __construct(private MT_Demo_Store $store, private string $dir)
    {
    }

    /** @return array{products:int,existing:int,foreign:int,categories:int,media:int} */
    public function import(): array
    {
        $d = MT_Demo_Data::load($this->dir);
        $r = ['products' => 0, 'existing' => 0, 'foreign' => 0, 'categories' => 0, 'media' => 0];
        $terms = [];
        foreach ($d['categories'] as $c) {
            $id = $this->store->find_term($c['slug']);
            if ($id === null) {
                $id = $this->store->create_term($c);
                $r['categories']++;
            }
            $terms[$c['slug']] = $id;
        }
        foreach ($d['products'] as $p) {
            $ex = $this->store->find_product($p['sku']);
            if ($ex !== null) {
                // Vorhandene Demo-Artikel bleiben, fremde Artikel mit gleicher SKU werden nie angefasst.
                $ex['demo'] ? $r['existing']++ : $r['foreign']++;
                continue;
            }
            $ids = [];
            foreach ($p['images'] as $i => $rel) {
                $key = basename($rel);
                $aid = $this->store->find_attachment($key);
                if ($aid === null) {
                    $aid = $this->store->create_attachment(rtrim($this->dir, '/') . '/' . $rel, $key, $p['title'] . ' (Demo-Bild ' . ($i + 1) . ')', $p['title']);
                    $r['media']++;
                }
                $ids[] = $aid;
            }
            $this->store->create_product($p, $terms[$p['category']], $ids);
            $r['products']++;
        }

        return $r;
    }

    /**
     * Demo-Artikel zum Löschen: Markierung _mt_demo=1 UND SKU-Präfix MTDEMO- UND SKU exakt aus dem Datensatz (Artikel- oder Varianten-SKU).
     * Kopien (WooCommerce übernimmt die Markierung und vergibt eine neue SKU wie MTDEMO-002-1 oder lässt sie leer) bleiben bewusst stehen.
     * Anzeige und Löschen nutzen dieselbe Bedingung.
     *
     * @return list<int>
     */
    private function demo_product_ids(): array
    {
        $known = [];
        foreach (MT_Demo_Data::load($this->dir)['products'] as $p) {
            $known[$p['sku']] = true;
            foreach ($p['variations']['options'] ?? [] as $o) {
                $known[$o['sku']] = true;
            }
        }

        return array_values(array_filter($this->store->demo_products(), function (int $id) use ($known): bool {
            $sku = $this->store->product_sku($id);

            return str_starts_with($sku, self::SKU_PREFIX) && isset($known[$sku]);
        }));
    }

    /** @return array{products:int,categories:int,media:int} */
    public function counts(): array
    {
        return ['products' => count($this->demo_product_ids()), 'categories' => count($this->store->demo_terms()), 'media' => count($this->store->demo_attachments())];
    }

    /** @return array{products:int,categories:int,media:int,categories_kept:int,media_kept:int} */
    public function remove(): array
    {
        $r = ['products' => 0, 'categories' => 0, 'media' => 0, 'categories_kept' => 0, 'media_kept' => 0];
        foreach ($this->demo_product_ids() as $id) {
            $this->store->delete_product($id);
            $r['products']++;
        }
        foreach ($this->store->demo_attachments() as $id) {
            if ($this->store->attachment_in_use_by_others($id)) {
                $r['media_kept']++; // ein echtes Produkt nutzt das Bild
                continue;
            }
            $this->store->delete_attachment($id);
            $r['media']++;
        }
        foreach ($this->store->demo_terms() as $id) {
            if ($this->store->term_in_use_by_others($id)) {
                $r['categories_kept']++;
                continue;
            }
            $this->store->delete_term($id);
            $r['categories']++;
        }

        return $r;
    }
}

/** Umsetzung mit WordPress und WooCommerce. */
final class MT_Demo_Store_WC implements MT_Demo_Store
{
    private const MARK = '_mt_demo';

    public function find_product(string $sku): ?array
    {
        $id = (int) wc_get_product_id_by_sku($sku);

        return $id > 0 ? ['id' => $id, 'demo' => get_post_meta($id, self::MARK, true) === '1'] : null;
    }

    public function create_product(array $p, int $term_id, array $image_ids): int
    {
        $variable = !empty($p['variations']['options']);
        $prod = $variable ? new WC_Product_Variable() : new WC_Product_Simple();
        $prod->set_name(sanitize_text_field($p['title']));
        $prod->set_status('publish');
        $prod->set_catalog_visibility('visible');
        $prod->set_short_description(wp_kses_post($p['short']));
        $prod->set_description(wp_kses_post($p['description']));
        $prod->set_sku($p['sku']);
        if (!$variable) {
            $prod->set_regular_price(wc_format_decimal($p['compare_price'] ?? $p['price']));
            if (isset($p['compare_price'])) {
                $prod->set_sale_price(wc_format_decimal($p['price']));
            }
            $prod->set_manage_stock(true);
            $prod->set_stock_quantity((int) $p['stock']);
            $prod->set_stock_status((int) $p['stock'] > 0 ? 'instock' : 'outofstock');
        }
        $prod->set_weight(wc_format_decimal($p['weight'] ?? ''));
        $prod->set_length(wc_format_decimal($p['dimensions']['length'] ?? ''));
        $prod->set_width(wc_format_decimal($p['dimensions']['width'] ?? ''));
        $prod->set_height(wc_format_decimal($p['dimensions']['height'] ?? ''));
        $attrs = [];
        $rows = $p['attributes'];
        $rows[] = ['name' => 'Lieferzeit', 'value' => $p['delivery_time']];
        foreach ($rows as $i => $row) {
            $a = new WC_Product_Attribute();
            $a->set_name(sanitize_text_field($row['name']));
            $a->set_options([sanitize_text_field($row['value'])]);
            $a->set_position($i);
            $a->set_visible(true);
            $a->set_variation(false);
            $attrs[] = $a;
        }
        if ($variable) {
            $a = new WC_Product_Attribute();
            $a->set_name(sanitize_text_field($p['variations']['attribute']));
            $a->set_options(array_map(static fn ($o) => sanitize_text_field($o['value']), $p['variations']['options']));
            $a->set_position(count($attrs));
            $a->set_visible(true);
            $a->set_variation(true);
            $attrs[] = $a;
        }
        $prod->set_attributes($attrs);
        $prod->set_category_ids([$term_id]);
        $prod->set_image_id($image_ids[0] ?? 0);
        $prod->set_gallery_image_ids(array_slice($image_ids, 1));
        $prod->update_meta_data(self::MARK, '1');
        $prod->update_meta_data('_mt_delivery_time', sanitize_text_field($p['delivery_time']));
        $id = (int) $prod->save();
        if ($variable) {
            $key = sanitize_title($p['variations']['attribute']);
            foreach ($p['variations']['options'] as $o) {
                $v = new WC_Product_Variation();
                $v->set_parent_id($id);
                $v->set_attributes([$key => sanitize_text_field($o['value'])]);
                $v->set_sku($o['sku']);
                $v->set_regular_price(wc_format_decimal($o['price'] ?? $p['price']));
                $v->set_manage_stock(true);
                $v->set_stock_quantity((int) $o['stock']);
                $v->set_stock_status((int) $o['stock'] > 0 ? 'instock' : 'outofstock');
                $v->set_weight(wc_format_decimal($p['weight'] ?? ''));
                $v->update_meta_data(self::MARK, '1');
                $v->save();
            }
            WC_Product_Variable::sync($id);
            wc_delete_product_transients($id);
        }

        return $id;
    }

    public function find_term(string $slug): ?int
    {
        $ids = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false, 'fields' => 'ids', 'orderby' => 'term_id', 'meta_query' => [['key' => '_mt_demo_slug', 'value' => $slug]]]);

        return is_array($ids) && $ids ? (int) $ids[0] : null;
    }

    public function create_term(array $c): int
    {
        $t = wp_insert_term(sanitize_text_field($c['name']), 'product_cat', ['slug' => $c['slug'], 'description' => sanitize_text_field($c['description'] ?? '')]);
        if (is_wp_error($t)) {
            throw new RuntimeException('Kategorie konnte nicht angelegt werden: ' . $t->get_error_message());
        }
        $id = (int) $t['term_id'];
        add_term_meta($id, self::MARK, '1', true);
        add_term_meta($id, '_mt_demo_slug', $c['slug'], true);

        return $id;
    }

    public function find_attachment(string $key): ?int
    {
        $ids = get_posts(['post_type' => 'attachment', 'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids', 'meta_query' => [
            ['key' => self::MARK, 'value' => '1'], ['key' => '_mt_demo_file', 'value' => $key],
        ]]);

        return $ids ? (int) $ids[0] : null;
    }

    public function create_attachment(string $path, string $key, string $title, string $alt): int
    {
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        $up = wp_upload_dir();
        if (!empty($up['error'])) {
            throw new RuntimeException('Upload-Ordner nicht beschreibbar.');
        }
        $name = wp_unique_filename($up['path'], $key);
        $target = $up['path'] . '/' . $name;
        if (!copy($path, $target)) {
            throw new RuntimeException('Bilddatei konnte nicht kopiert werden.');
        }
        $id = wp_insert_attachment(['post_mime_type' => 'image/jpeg', 'post_title' => sanitize_text_field($title), 'post_status' => 'inherit', 'guid' => $up['url'] . '/' . $name], $target, 0, true);
        if (is_wp_error($id)) {
            @unlink($target);
            throw new RuntimeException('Bild konnte nicht angelegt werden: ' . $id->get_error_message());
        }
        update_post_meta($id, '_wp_attachment_image_alt', sanitize_text_field($alt));
        update_post_meta($id, self::MARK, '1');
        update_post_meta($id, '_mt_demo_file', $key);
        wp_update_attachment_metadata($id, wp_generate_attachment_metadata($id, $target));

        return (int) $id;
    }

    public function demo_products(): array
    {
        return array_map('intval', get_posts(['post_type' => 'product', 'post_status' => ['publish', 'draft', 'pending', 'private', 'future', 'trash'], 'numberposts' => -1, 'fields' => 'ids', 'meta_key' => self::MARK, 'meta_value' => '1']));
    }

    public function demo_terms(): array
    {
        $ids = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false, 'fields' => 'ids', 'orderby' => 'term_id', 'meta_query' => [['key' => self::MARK, 'value' => '1']]]);

        return is_array($ids) ? array_map('intval', $ids) : [];
    }

    public function demo_attachments(): array
    {
        return array_map('intval', get_posts(['post_type' => 'attachment', 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids', 'meta_query' => [
            ['key' => self::MARK, 'value' => '1'], ['key' => '_mt_demo_file', 'compare' => 'EXISTS'],
        ]]));
    }

    public function product_sku(int $id): string
    {
        return (string) get_post_meta($id, '_sku', true);
    }

    public function delete_product(int $id): void
    {
        if (get_post_type($id) !== 'product' || get_post_meta($id, self::MARK, true) !== '1' || !str_starts_with($this->product_sku($id), MT_Demo_Importer::SKU_PREFIX)) {
            return; // Sicherung: nur markierte Produkte mit Demo-SKU
        }
        $p = wc_get_product($id);
        if ($p) {
            $p->delete(true); // löscht auch die Varianten
        }
    }

    public function delete_term(int $id): void
    {
        if (get_term_meta($id, self::MARK, true) === '1') {
            wp_delete_term($id, 'product_cat');
        }
    }

    public function delete_attachment(int $id): void
    {
        if (get_post_type($id) === 'attachment' && get_post_meta($id, self::MARK, true) === '1') {
            wp_delete_attachment($id, true);
        }
    }

    public function attachment_in_use_by_others(int $id): bool
    {
        $used = get_posts(['post_type' => ['product', 'product_variation'], 'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids', 'meta_query' => [
            'relation' => 'OR',
            ['key' => '_thumbnail_id', 'value' => (string) $id],
            ['key' => '_product_image_gallery', 'value' => '(^|,)' . $id . '(,|$)', 'compare' => 'REGEXP'],
        ]]);

        return $used !== [];
    }

    public function term_in_use_by_others(int $id): bool
    {
        $posts = get_posts(['post_type' => 'product', 'post_status' => ['publish', 'draft', 'pending', 'private', 'future', 'trash'], 'numberposts' => 1, 'fields' => 'ids',
            'tax_query' => [['taxonomy' => 'product_cat', 'field' => 'term_id', 'terms' => [$id], 'include_children' => false]]]);
        if ($posts) { // nach dem Löschen der Demo-Artikel bleibt hier nur Fremdes (auch markierte Kopien)
            return true;
        }
        $children = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false, 'fields' => 'ids', 'parent' => $id]);

        return is_array($children) && count(array_filter($children, static fn ($c) => get_term_meta((int) $c, self::MARK, true) !== '1')) > 0;
    }
}

function mt_demo_dir(): string
{
    return get_template_directory() . '/demo-data';
}

function mt_demo_importer(): MT_Demo_Importer
{
    return new MT_Demo_Importer(new MT_Demo_Store_WC(), mt_demo_dir());
}

/** Seite sehen und importieren. */
function mt_demo_cap(): string
{
    return 'manage_woocommerce';
}

/** Entfernen (löscht Daten): nur Administratoren. */
function mt_demo_remove_cap(): string
{
    return 'manage_options';
}

function mt_demo_register_menu(): void
{
    add_management_page('Demo-Inhalte', 'Demo-Inhalte', mt_demo_cap(), 'mt-demo', 'mt_demo_render_page');
}

function mt_demo_page_url(array $args = []): string
{
    return add_query_arg($args + ['page' => 'mt-demo'], admin_url('tools.php'));
}

function mt_demo_render_page(): void
{
    if (!current_user_can(mt_demo_cap())) {
        wp_die(esc_html('Keine Berechtigung.'), '', ['response' => 403]);
    }
    echo '<div class="wrap"><h1>' . esc_html('Demo-Inhalte') . '</h1>';
    $done = isset($_GET['mt_demo_done']) ? sanitize_key(wp_unslash((string) $_GET['mt_demo_done'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification
    $n = static fn (string $k): int => isset($_GET[$k]) ? absint(wp_unslash((string) $_GET[$k])) : 0; // phpcs:ignore WordPress.Security.NonceVerification
    $notes = [
        'import' => ['success', sprintf('Import fertig: %d Artikel neu angelegt, %d schon vorhanden, %d Kategorien und %d Bilder neu. Artikel mit gleicher Artikelnummer, die nicht zu den Demo-Inhalten gehören, wurden übersprungen: %d.', $n('p'), $n('e'), $n('c'), $n('m'), $n('f'))],
        'remove' => ['success', sprintf('Entfernt: %d Artikel, %d Bilder und %d Kategorien. Kategorien, die noch von eigenen Artikeln genutzt werden, blieben erhalten: %d.', $n('p'), $n('m'), $n('c'), $n('k'))],
        'noconfirm' => ['warning', 'Nichts gelöscht: Die Bestätigung fehlte.'],
        'changed' => ['warning', 'Nichts gelöscht: Der Bestand an Demo-Inhalten hat sich seit der Anzeige geändert. Bitte neu prüfen und erneut bestätigen.'],
        'error' => ['error', 'Der Vorgang ist fehlgeschlagen. Details stehen im Fehlerprotokoll des Servers.'],
    ];
    if (isset($notes[$done])) {
        printf('<div class="notice notice-%s is-dismissible"><p>%s</p></div>', esc_attr($notes[$done][0]), esc_html($notes[$done][1]));
    }
    if (!class_exists('WooCommerce')) {
        echo '<div class="notice notice-warning"><p>' . esc_html('WooCommerce ist nicht aktiv. Die Demo-Inhalte brauchen WooCommerce.') . '</p></div></div>';

        return;
    }
    try {
        $imp = mt_demo_importer();
        $c = $imp->counts();
        $d = MT_Demo_Data::load(mt_demo_dir());
    } catch (Throwable $e) {
        echo '<div class="notice notice-error"><p>' . esc_html($e->getMessage()) . '</p></div></div>';

        return;
    }
    $total = $c['products'] + $c['categories'] + $c['media'];
    echo '<p>' . esc_html(sprintf('Der Demo-Datensatz enthält %d Artikel in %d Kategorien, je Artikel 3 Platzhalterbilder. Alle Texte und Bilder sind frei erfunden. Es werden keine Daten aus dem Netz geladen.', count($d['products']), count($d['categories']))) . '</p>';

    echo '<h2>' . esc_html('Demo-Inhalte importieren') . '</h2>';
    echo '<p>' . esc_html('Legt Kategorien, Artikel (mit Varianten und Eigenschaften) und Bilder an. Ein zweiter Import erzeugt keine Dubletten.') . '</p>';
    echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
    echo '<input type="hidden" name="action" value="mt_demo_import">';
    wp_nonce_field('mt_demo_import', 'mt_demo_nonce');
    submit_button('Demo-Inhalte importieren', 'primary', 'submit', false);
    echo '</form>';

    echo '<h2>' . esc_html('Demo-Inhalte entfernen') . '</h2>';
    echo '<p>' . esc_html(sprintf('Aktuell vorhanden: %d Demo-Artikel, %d Demo-Bilder, %d Demo-Kategorien.', $c['products'], $c['media'], $c['categories'])) . '</p>';
    if ($total === 0) {
        echo '<p>' . esc_html('Es gibt nichts zu entfernen.') . '</p>';
    } elseif (!current_user_can(mt_demo_remove_cap())) {
        echo '<p>' . esc_html('Das Entfernen ist Administratoren vorbehalten.') . '</p>';
    } else {
        echo '<p>' . esc_html('Gelöscht wird nur, was der Import angelegt hat (Markierung _mt_demo, Artikelnummer MTDEMO-…). Eigene Artikel, Bilder und Kategorien bleiben unverändert. Das Löschen kann nicht rückgängig gemacht werden.') . '</p>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        echo '<input type="hidden" name="action" value="mt_demo_remove">';
        echo '<input type="hidden" name="mt_demo_expect" value="' . esc_attr((string) $total) . '">';
        wp_nonce_field('mt_demo_remove', 'mt_demo_nonce');
        echo '<p><label><input type="checkbox" name="mt_demo_confirm" value="1" required> ' . esc_html(sprintf('Ja, diese %d Demo-Artikel, %d Bilder und %d Kategorien endgültig löschen.', $c['products'], $c['media'], $c['categories'])) . '</label></p>';
        submit_button('Demo-Inhalte entfernen', 'delete', 'submit', false);
        echo '</form>';
    }
    echo '</div>';
}

function mt_demo_handle_import(): void
{
    if (!current_user_can(mt_demo_cap())) {
        wp_die(esc_html('Keine Berechtigung.'), '', ['response' => 403]);
    }
    check_admin_referer('mt_demo_import', 'mt_demo_nonce');
    @set_time_limit(300);
    try {
        $r = mt_demo_importer()->import();
        $args = ['mt_demo_done' => 'import', 'p' => $r['products'], 'e' => $r['existing'], 'f' => $r['foreign'], 'c' => $r['categories'], 'm' => $r['media']];
    } catch (Throwable $e) {
        error_log('MichaTheme Demo-Import: ' . $e->getMessage());
        $args = ['mt_demo_done' => 'error'];
    }
    wp_safe_redirect(mt_demo_page_url($args));
    exit;
}

function mt_demo_handle_remove(): void
{
    if (!current_user_can(mt_demo_remove_cap())) {
        wp_die(esc_html('Keine Berechtigung.'), '', ['response' => 403]);
    }
    check_admin_referer('mt_demo_remove', 'mt_demo_nonce');
    $confirm = isset($_POST['mt_demo_confirm']) && wp_unslash((string) $_POST['mt_demo_confirm']) === '1';
    $expect = isset($_POST['mt_demo_expect']) ? absint(wp_unslash((string) $_POST['mt_demo_expect'])) : -1;
    $args = ['mt_demo_done' => 'noconfirm'];
    if ($confirm) {
        try {
            $imp = mt_demo_importer();
            $c = $imp->counts();
            if ($c['products'] + $c['categories'] + $c['media'] !== $expect) {
                $args = ['mt_demo_done' => 'changed'];
            } else {
                @set_time_limit(300);
                $r = $imp->remove();
                $args = ['mt_demo_done' => 'remove', 'p' => $r['products'], 'm' => $r['media'], 'c' => $r['categories'], 'k' => $r['categories_kept']];
            }
        } catch (Throwable $e) {
            error_log('MichaTheme Demo-Entfernen: ' . $e->getMessage());
            $args = ['mt_demo_done' => 'error'];
        }
    }
    wp_safe_redirect(mt_demo_page_url($args));
    exit;
}

/** WP-CLI: wp michatheme demo import | remove [--yes] | status */
final class MT_Demo_CLI
{
    private function importer(): MT_Demo_Importer
    {
        if (!class_exists('WooCommerce')) {
            WP_CLI::error('WooCommerce ist nicht aktiv.');
        }

        return mt_demo_importer();
    }

    /**
     * Legt die Demo-Artikel an (wiederholbar, keine Dubletten).
     *
     * @when after_wp_load
     */
    public function import(array $args = [], array $assoc = []): void
    {
        try {
            $r = $this->importer()->import();
        } catch (RuntimeException $e) {
            WP_CLI::error($e->getMessage());
        }
        WP_CLI::success(sprintf('%d Artikel neu, %d vorhanden, %d fremde mit gleicher SKU übersprungen; %d Kategorien und %d Bilder neu.', $r['products'], $r['existing'], $r['foreign'], $r['categories'], $r['media']));
    }

    /**
     * Zeigt, wie viele Demo-Inhalte vorhanden sind.
     *
     * @when after_wp_load
     */
    public function status(array $args = [], array $assoc = []): void
    {
        $c = $this->importer()->counts();
        WP_CLI::line(sprintf('Demo-Artikel: %d, Demo-Bilder: %d, Demo-Kategorien: %d', $c['products'], $c['media'], $c['categories']));
    }

    /**
     * Entfernt ausschließlich markierte Demo-Inhalte (Artikel, Bilder, Kategorien).
     *
     * ## OPTIONS
     *
     * [--yes]
     * : Ohne Rückfrage löschen.
     *
     * @when after_wp_load
     */
    public function remove(array $args = [], array $assoc = []): void
    {
        $imp = $this->importer();
        $c = $imp->counts();
        if ($c['products'] + $c['categories'] + $c['media'] === 0) {
            WP_CLI::success('Keine Demo-Inhalte vorhanden.');

            return;
        }
        WP_CLI::line(sprintf('Zu löschen: %d Artikel, %d Bilder, %d Kategorien.', $c['products'], $c['media'], $c['categories']));
        if (empty($assoc['yes'])) {
            WP_CLI::confirm('Diese Demo-Inhalte endgültig löschen?');
        }
        $r = $imp->remove();
        WP_CLI::success(sprintf('Entfernt: %d Artikel, %d Bilder, %d Kategorien (%d Kategorien blieben, weil eigene Artikel daran hängen).', $r['products'], $r['media'], $r['categories'], $r['categories_kept']));
    }
}

/** Leerer Oberbefehl `wp michatheme`, damit `wp michatheme demo ...` Unterbefehle haben kann. */
final class MT_Demo_CLI_Namespace
{
}

function mt_demo_boot(): void
{
    add_action('admin_menu', 'mt_demo_register_menu');
    add_action('admin_post_mt_demo_import', 'mt_demo_handle_import');
    add_action('admin_post_mt_demo_remove', 'mt_demo_handle_remove');
    if (defined('WP_CLI') && WP_CLI) {
        $root = WP_CLI::get_root_command();
        $path = ['michatheme'];
        if ($root->find_subcommand($path) === false) {
            WP_CLI::add_command('michatheme', 'MT_Demo_CLI_Namespace', ['shortdesc' => 'MichaTheme-Befehle']);
        }
        WP_CLI::add_command('michatheme demo', 'MT_Demo_CLI', ['shortdesc' => 'Demo-Artikel importieren und entfernen']);
    }
}

if (function_exists('add_action')) {
    mt_demo_boot();
}

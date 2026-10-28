<?php
defined('ABSPATH') || exit;

use MichaThemeV5\Plugin\{FieldRenderer, PageRenderer};

/**
 * Menü und Seiten: Einstellungen · Lizenzen · Plugin-Empfehlungen · Info.
 * Jede Aktion: POST erzwingen, Rechte prüfen, Nonce prüfen. Meldungen kommen nur aus einer festen Liste (nie aus der URL).
 */
final class MT_Admin
{
    private const SLUGS = ['settings' => 'michatheme-v5', 'license' => 'michatheme-v5-license', 'catalog' => 'michatheme-v5-catalog', 'info' => 'michatheme-v5-info'];

    private const MESSAGES = [
        'saved' => ['ok', 'Einstellungen gespeichert.'],
        'unchanged' => ['ok', 'Es gab keine Änderungen zu speichern.'],
        'preset_ok' => ['ok', 'Preset angewendet. Mit dem Preset „Standard“ oder auf der Info-Seite („Einstellungen wiederherstellen“) geht es zurück.'],
        'preset_bad' => ['err', 'Dieses Preset gibt es nicht.'],
        'restored' => ['ok', 'Einstellungen aus der Sicherung wiederhergestellt.'],
        'no_backup' => ['err', 'Es gibt keine Sicherung zum Wiederherstellen.'],
        'lic_saved' => ['ok', 'Lizenzcode gespeichert und geprüft.'],
        'lic_format' => ['err', 'Der Lizenzcode sieht nicht richtig aus. Er besteht aus drei Teilen mit Punkt. Bitte vollständig aus dem Kundenkonto kopieren.'],
        'lic_removed' => ['ok', 'Lizenzcode entfernt. Das Theme läuft mit allen Free-Funktionen.'],
        'lic_checked' => ['ok', 'Lizenz neu geprüft.'],
        'lic_wait' => ['err', 'Bitte warten Sie eine Minute, bevor Sie erneut prüfen.'],
        'theme_copied' => ['ok', 'Theme kopiert. Als Nächstes können Sie es aktivieren.'],
        'theme_activated' => ['ok', 'Theme aktiviert.'],
        'need_confirm' => ['err', 'Bitte bestätigen Sie zuerst, dass Sie ein Backup haben.'],
    ];

    public static function boot(): void
    {
        add_action('admin_menu', [self::class, 'menu']);
        add_action('admin_enqueue_scripts', [self::class, 'assets']);
        add_action('admin_init', [self::class, 'redirect_after_activation']);
        add_filter('plugin_action_links_' . plugin_basename(MT_PLUGIN_FILE), [self::class, 'action_links']);
        foreach (['save_settings', 'apply_preset', 'restore_settings', 'license', 'catalog_refresh', 'theme_copy', 'theme_activate'] as $a) {
            add_action('admin_post_mt_' . $a, [self::class, 'handle_' . $a]);
        }
    }

    public static function url(string $page, array $args = []): string
    {
        return add_query_arg($args, admin_url('admin.php?page=' . self::SLUGS[$page]));
    }

    public static function menu(): void
    {
        add_menu_page('MichaThemeV5', 'MichaThemeV5', 'manage_options', self::SLUGS['settings'], [self::class, 'page_settings'], 'dashicons-art', 58);
        add_submenu_page(self::SLUGS['settings'], 'Einstellungen', 'Einstellungen', 'manage_options', self::SLUGS['settings'], [self::class, 'page_settings']);
        add_submenu_page(self::SLUGS['settings'], 'Lizenzen', 'Lizenzen', 'manage_options', self::SLUGS['license'], [self::class, 'page_license']);
        add_submenu_page(self::SLUGS['settings'], 'Plugin-Empfehlungen', 'Plugin-Empfehlungen', 'manage_options', self::SLUGS['catalog'], [self::class, 'page_catalog']);
        add_submenu_page(self::SLUGS['settings'], 'Info', 'Info', 'manage_options', self::SLUGS['info'], [self::class, 'page_info']);
    }

    public static function action_links(array $links): array
    {
        array_unshift($links, '<a href="' . esc_url(self::url('settings')) . '">Einstellungen</a>');

        return $links;
    }

    /** JS/CSS nur auf den eigenen Seiten. */
    public static function assets(string $hook): void
    {
        if (!str_contains($hook, 'michatheme-v5')) {
            return;
        }
        $u = MT_PLUGIN_URL . 'assets/admin/';
        $v = MT_PLUGIN_VERSION;
        wp_enqueue_media();
        wp_enqueue_style('mt-colorpicker', $u . 'mt-colorpicker.css', [], $v);
        wp_enqueue_style('mt-admin', $u . 'mt-admin.css', ['mt-colorpicker'], $v);
        wp_enqueue_script('mt-colorpicker', $u . 'mt-colorpicker.js', [], $v, true);
        wp_enqueue_script('mt-admin-logic', $u . 'mt-admin-logic.js', [], $v, true);
        wp_enqueue_script('mt-admin', $u . 'mt-admin.js', ['mt-colorpicker', 'mt-admin-logic'], $v, true);
    }

    public static function redirect_after_activation(): void
    {
        $s = get_option('michatheme_v5_state', []);
        if (!is_array($s) || empty($s['redirect'])) {
            return;
        }
        unset($s['redirect']);
        update_option('michatheme_v5_state', $s, false);
        if (wp_doing_ajax() || isset($_GET['activate-multi']) || !current_user_can('manage_options')) {
            return;
        }
        wp_safe_redirect(self::url('info'));
        exit;
    }

    // Gemeinsamer Rahmen

    private static function guard(string $capability, string $nonce): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !current_user_can($capability)) {
            wp_die('Keine Berechtigung.', '', ['response' => 403]);
        }
        check_admin_referer($nonce);
    }

    private static function back(string $page, string $msg, array $args = []): void
    {
        wp_safe_redirect(self::url($page, ['mt_msg' => $msg] + $args));
        exit;
    }

    private static function wrap(string $title, string $page, string $body): void
    {
        echo '<div class="wrap"><h1>' . esc_html($title) . '</h1>';
        echo '<nav class="nav-tab-wrapper" aria-label="MichaThemeV5 Bereiche">';
        foreach (['settings' => 'Einstellungen', 'license' => 'Lizenzen', 'catalog' => 'Plugin-Empfehlungen', 'info' => 'Info'] as $k => $lab) {
            echo '<a class="nav-tab' . ($k === $page ? ' nav-tab-active' : '') . '" href="' . esc_url(self::url($k)) . '"' . ($k === $page ? ' aria-current="page"' : '') . '>' . esc_html($lab) . '</a>';
        }
        echo '</nav>';
        $msg = isset($_GET['mt_msg']) ? sanitize_key(wp_unslash($_GET['mt_msg'])) : '';
        if (isset(self::MESSAGES[$msg])) {
            [$kind, $text] = self::MESSAGES[$msg];
            $extra = '';
            if ($msg === 'saved' && isset($_GET['n'])) {
                $n = (int) $_GET['n'];
                $text = $n === 1 ? '1 Einstellung gespeichert.' : $n . ' Einstellungen gespeichert.';
            }
            echo '<div class="notice notice-' . ($kind === 'ok' ? 'success' : 'error') . '" role="status"><p>' . esc_html($text) . $extra . '</p></div>';
        }
        echo $body; // bereits escaped (FieldRenderer/PageRenderer)
        echo '</div>';
    }

    // Einstellungen

    public static function page_settings(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('Keine Berechtigung.', '', ['response' => 403]);
        }
        ob_start();
        if (!MT_Store::theme_active()) {
            echo '<div class="mtadm mtadm-page"><section class="mtadm-card"><h2>Das Theme MichaThemeV5 ist nicht aktiv</h2>'
                . '<p>Die Einstellungen gehören zum Theme. Bitte richten Sie das Theme zuerst auf der Info-Seite ein oder aktivieren Sie es unter Design › Themes.</p>'
                . '<p><a class="mtadm-primary" href="' . esc_url(self::url('info')) . '">Zur Info-Seite</a></p></section></div>';
            self::wrap('MichaThemeV5 · Einstellungen', 'settings', (string) ob_get_clean());

            return;
        }
        $errs = get_transient('michatheme_v5_adj_' . get_current_user_id());
        if (is_array($errs) && $errs) {
            delete_transient('michatheme_v5_adj_' . get_current_user_id());
            $opts = MT_Store::options();
            echo '<div class="mtadm"><div class="mtadm-errors" role="alert"><h2>Diese Werte waren ungültig und wurden geleert</h2><ul>';
            foreach ($errs as $id) {
                if (isset($opts[$id])) {
                    echo '<li><a href="#mtf-' . esc_attr($id) . '">' . esc_html($opts[$id]['label']) . '</a></li>';
                }
            }
            echo '</ul></div></div>';
        }
        $form = '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">'
            . '<input type="hidden" name="action" value="mt_save_settings">' . wp_nonce_field('mt_save_settings', '_wpnonce', true, false);
        echo FieldRenderer::settings(MT_Store::schema(), MT_Store::values(), [
            'formOpen' => $form,
            'formClose' => FieldRenderer::saveBar('Änderungen speichern') . '</form>',
            'paletteJson' => wp_json_encode(MT_Store::palette()),
            'presetFormOpen' => '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="mt_apply_preset">' . wp_nonce_field('mt_apply_preset', '_wpnonce', true, false),
            'mediaButtons' => true,
        ]);
        self::wrap('MichaThemeV5 · Einstellungen', 'settings', (string) ob_get_clean());
    }

    public static function handle_save_settings(): void
    {
        self::guard('manage_options', 'mt_save_settings');
        if (!MT_Store::theme_active()) {
            self::back('settings', 'unchanged');
        }
        $raw = isset($_POST['mt']) && is_array($_POST['mt']) ? wp_unslash($_POST['mt']) : [];
        $input = [];
        foreach ($raw as $k => $v) {
            if (is_string($k) && (is_string($v) || is_int($v))) { // keine verschachtelten Werte
                $input[$k] = $v;
            }
        }
        $res = MT_Store::save($input);
        if (isset($_POST['mt_palette']) && is_string($_POST['mt_palette'])) {
            MT_Store::save_palette(wp_unslash($_POST['mt_palette']));
        }
        if ($res['adjusted']) {
            set_transient('michatheme_v5_adj_' . get_current_user_id(), $res['adjusted'], 120);
        }
        $n = count($res['changed']);
        self::back('settings', $n > 0 ? 'saved' : 'unchanged', $n > 0 ? ['n' => $n] : []);
    }

    public static function handle_apply_preset(): void
    {
        self::guard('manage_options', 'mt_apply_preset');
        $id = isset($_POST['mt_preset']) ? sanitize_key(wp_unslash($_POST['mt_preset'])) : '';
        $n = MT_Store::theme_active() ? MT_Store::apply_preset($id) : -1;
        self::back('settings', $n < 0 ? 'preset_bad' : 'preset_ok');
    }

    public static function handle_restore_settings(): void
    {
        self::guard('manage_options', 'mt_restore_settings');
        if (empty($_POST['mt_confirm'])) {
            self::back('info', 'need_confirm');
        }
        self::back('info', MT_Store::restore() > 0 ? 'restored' : 'no_backup');
    }

    // Lizenzen

    public static function page_license(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('Keine Berechtigung.', '', ['response' => 403]);
        }
        $form = '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="mt_license">' . wp_nonce_field('mt_license', '_wpnonce', true, false);
        $html = PageRenderer::license(MT_License::describe(), [
            'formOpen' => $form, 'formClose' => '</form>', 'hasToken' => MT_License::has_token(), 'masked' => MT_License::masked(), 'shopUrl' => MT_SHOP_URL,
        ]);
        self::wrap('MichaThemeV5 · Lizenzen', 'license', $html);
    }

    public static function handle_license(): void
    {
        self::guard('manage_options', 'mt_license');
        $action = isset($_POST['mt_license_action']) ? sanitize_key(wp_unslash($_POST['mt_license_action'])) : '';
        if ($action === 'remove') {
            MT_License::remove();
            self::back('license', 'lic_removed');
        }
        if ($action === 'check') {
            self::back('license', MT_License::recheck() ? 'lic_checked' : 'lic_wait');
        }
        $token = isset($_POST['mt_license_token']) && is_string($_POST['mt_license_token']) ? trim(wp_unslash($_POST['mt_license_token'])) : '';
        if ($action === 'save' && $token !== '') {
            self::back('license', MT_License::save($token) ? 'lic_saved' : 'lic_format');
        }
        self::back('license', 'lic_format');
    }

    // Plugin-Empfehlungen

    public static function page_catalog(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('Keine Berechtigung.', '', ['response' => 403]);
        }
        $form = '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="mt_catalog_refresh">' . wp_nonce_field('mt_catalog_refresh', '_wpnonce', true, false);
        $res = MT_Catalog::load(false);
        $html = PageRenderer::catalog($res, ['formOpen' => $form, 'formClose' => '</form>']);
        self::wrap('MichaThemeV5 · Plugin-Empfehlungen', 'catalog', $html);
    }

    public static function handle_catalog_refresh(): void
    {
        self::guard('manage_options', 'mt_catalog_refresh');
        $res = MT_Catalog::load(true);
        wp_safe_redirect(self::url('catalog', ['mt_refreshed' => $res['error'] === 'throttled' ? 0 : 1]));
        exit;
    }

    // Info

    public static function page_info(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('Keine Berechtigung.', '', ['response' => 403]);
        }
        global $wp_version;
        $lic = MT_License::state();
        $installed = MT_Theme_Installer::installed_version();
        $active = MT_Store::theme_active();
        $themeV = $active && defined('MT_VERSION') ? MT_VERSION : $installed;
        $rows = [
            ['label' => 'Plugin-Version', 'value' => MT_PLUGIN_VERSION, 'state' => 'info'],
            ['label' => 'Theme installiert', 'value' => $installed === null ? 'Nein. Bitte unten einrichten.' : 'Ja, Version ' . $installed, 'state' => $installed === null ? 'warn' : 'ok'],
            ['label' => 'Theme aktiv', 'value' => $active ? 'Ja' . ($themeV ? ', Version ' . $themeV : '') : 'Nein', 'state' => $active ? 'ok' : 'warn'],
            ['label' => 'Theme und Plugin passen zusammen', 'value' => $themeV === null ? 'Nicht prüfbar (Theme fehlt)' : (version_compare((string) $themeV, MT_PLUGIN_THEME_MIN, '>=') ? 'Ja' : 'Nein, das Theme ist älter als ' . MT_PLUGIN_THEME_MIN), 'state' => $themeV !== null && version_compare((string) $themeV, MT_PLUGIN_THEME_MIN, '>=') ? 'ok' : 'warn'],
            ['label' => 'Lizenzserver', 'value' => ($lic['reason'] ?? '') === 'unreachable' ? 'Gerade nicht erreichbar (Free bleibt aktiv)' : 'Erreichbar oder nicht nötig', 'state' => ($lic['reason'] ?? '') === 'unreachable' ? 'warn' : 'ok'],
            ['label' => 'WordPress / PHP', 'value' => $wp_version . ' / ' . PHP_VERSION, 'state' => 'info'],
            ['label' => 'WooCommerce', 'value' => defined('WC_VERSION') ? 'Version ' . WC_VERSION : 'Nicht aktiv', 'state' => defined('WC_VERSION') ? 'ok' : 'warn'],
        ];
        $changelog = is_readable(MT_PLUGIN_DIR . 'CHANGELOG.md') ? (string) file_get_contents(MT_PLUGIN_DIR . 'CHANGELOG.md') : '';
        $sys = "MichaThemeV5 Plugin " . MT_PLUGIN_VERSION . "\nTheme: " . ($themeV ?? 'nicht installiert') . "\nWordPress " . $wp_version . "\nPHP " . PHP_VERSION . "\nWooCommerce " . (defined('WC_VERSION') ? WC_VERSION : '-') . "\nLizenz: " . (($lic['valid'] ?? false) ? 'gültig (' . $lic['tier'] . ')' : 'Free (' . ($lic['reason'] ?? '-') . ')');
        $extra = self::installer_card($installed, $active) . self::restore_card()
            . '<section class="mtadm-card" aria-labelledby="mti-sys"><h2 id="mti-sys">Systeminfo (ohne Lizenzcode)</h2><p class="mtadm-help">Für Support-Anfragen kopieren.</p>'
            . '<label class="mtadm-sr" for="mti-sysbox">Systeminfo zum Kopieren</label><textarea id="mti-sysbox" class="mtadm-sysbox" readonly rows="6">' . esc_textarea($sys) . '</textarea></section>';
        $html = '<div class="mtadm">' . PageRenderer::info($rows, [
            'extra' => $extra,
            'links' => [['label' => 'Anleitung und Quellcode auf GitHub', 'url' => 'https://github.com/MichaelGahnDESIGN/MichaThemeV5_WooCommerce'], ['label' => 'Webseite', 'url' => MT_SHOP_URL]],
            'changelog' => $changelog,
        ]) . '</div>';
        self::wrap('MichaThemeV5 · Info', 'info', $html);
    }

    private static function installer_card(?string $installed, bool $active): string
    {
        $m = MT_Theme_Installer::manifest();
        $bundled = $m['version'] ?? null;
        $h = '<section class="mtadm-card" aria-labelledby="mti-theme"><h2 id="mti-theme">Theme einrichten</h2>';
        if ($bundled === null) {
            return $h . '<p>In diesem Plugin ist kein Theme enthalten. Laden Sie das komplette Plugin-ZIP von der Download-Seite, oder installieren Sie das Theme wie gewohnt unter Design › Themes › Theme hinzufügen.</p></section>';
        }
        $h .= '<p>Im Plugin enthaltenes Theme: Version <strong>' . esc_html($bundled) . '</strong>. Installiert: <strong>' . esc_html($installed ?? 'nicht installiert') . '</strong>.' . ($active ? ' Es ist aktiv.' : '') . '</p>';
        $h .= '<p>Es wird nichts automatisch kopiert. Ein bisheriger Theme-Ordner wird vorher gesichert (uploads/michatheme-v5-backup). Ihre Theme-Einstellungen bleiben gespeichert. Kind-Themes werden nie verändert.</p>';
        $needsCopy = $installed === null || version_compare($installed, $bundled, '<');
        if (current_user_can('install_themes')) {
            if ($needsCopy) {
                $h .= '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="mt_theme_copy"><input type="hidden" name="mt_step" value="ask">'
                    . wp_nonce_field('mt_theme_copy', '_wpnonce', true, false)
                    . '<label class="mtadm-check"><input type="checkbox" name="mt_confirm" value="1"> <span>Ich habe ein Backup meiner Seite.</span></label>'
                    . '<p class="mtadm-actions"><button type="submit" class="mtadm-primary">' . ($installed === null ? 'Theme kopieren' : 'Theme aktualisieren (kopieren)') . '</button></p></form>';
            } else {
                $h .= '<p><strong>Das Theme ist auf dem neuesten Stand.</strong></p>';
            }
        } else {
            $h .= '<p>Dafür brauchen Sie das Recht, Themes zu installieren.</p>';
        }
        if ($installed !== null && !$active && current_user_can('switch_themes')) {
            $h .= '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="mt_theme_activate"><input type="hidden" name="mt_step" value="ask">'
                . wp_nonce_field('mt_theme_activate', '_wpnonce', true, false)
                . '<p class="mtadm-actions"><button type="submit" class="mtadm-btn">Theme aktivieren</button></p></form>';
        }

        return $h . '</section>';
    }

    private static function restore_card(): string
    {
        $b = MT_Store::backup_info();
        $h = '<section class="mtadm-card" aria-labelledby="mti-bk"><h2 id="mti-bk">Einstellungen sichern und wiederherstellen</h2>';
        if ($b === null) {
            return $h . '<p>Noch keine Sicherung vorhanden. Vor jedem Speichern legt das Plugin automatisch eine an.</p></section>';
        }
        $h .= '<p>Letzte Sicherung: ' . esc_html(wp_date('d.m.Y H:i', (int) $b['at'])) . '. Beim Wiederherstellen werden die gesicherten Werte zurückgeschrieben.</p>'
            . '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="mt_restore_settings">' . wp_nonce_field('mt_restore_settings', '_wpnonce', true, false)
            . '<label class="mtadm-check"><input type="checkbox" name="mt_confirm" value="1"> <span>Ja, aktuelle Einstellungen durch die Sicherung ersetzen.</span></label>'
            . '<p class="mtadm-actions"><button type="submit" class="mtadm-btn">Einstellungen wiederherstellen</button></p></form>';

        return $h . '</section>';
    }

    // Theme kopieren / aktivieren: zwei Schritte (ask = Rückfrage-Seite, do = ausführen)

    private static function confirm_page(string $title, string $text, string $action, string $nonce, string $button): void
    {
        $html = '<p>' . esc_html($text) . '</p><form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">'
            . '<input type="hidden" name="action" value="' . esc_attr($action) . '"><input type="hidden" name="mt_step" value="do"><input type="hidden" name="mt_confirm" value="1">'
            . wp_nonce_field($nonce, '_wpnonce', true, false)
            . '<p><button type="submit" class="button button-primary">' . esc_html($button) . '</button> <a class="button" href="' . esc_url(self::url('info')) . '">Abbrechen</a></p></form>';
        wp_die($html, esc_html($title), ['response' => 200]);
    }

    public static function handle_theme_copy(): void
    {
        self::guard('install_themes', 'mt_theme_copy');
        if (empty($_POST['mt_confirm'])) {
            self::back('info', 'need_confirm');
        }
        $m = MT_Theme_Installer::manifest();
        if (($_POST['mt_step'] ?? '') !== 'do') {
            self::confirm_page('Theme kopieren?', 'Das mitgelieferte Theme (Version ' . ($m['version'] ?? '?') . ') wird in den Themes-Ordner kopiert. Ein vorhandenes Theme wird vorher gesichert. Wirklich fortfahren?', 'mt_theme_copy', 'mt_theme_copy', 'Ja, Theme kopieren');
        }
        // Dateisystem-Zugangsdaten (FTP) nur wenn WordPress sie braucht
        require_once ABSPATH . 'wp-admin/includes/file.php';
        ob_start();
        $creds = request_filesystem_credentials(self::url('info'), '', false, get_theme_root(), ['action', 'mt_step', 'mt_confirm', '_wpnonce']);
        $credForm = (string) ob_get_clean();
        if ($creds === false) {
            wp_die($credForm, 'Zugangsdaten nötig', ['response' => 200]);
        }
        $err = MT_Theme_Installer::copy();
        if ($err !== null) {
            wp_die('<p>' . esc_html($err) . '</p><p><a class="button" href="' . esc_url(self::url('info')) . '">Zurück zur Info-Seite</a></p>', 'Theme kopieren fehlgeschlagen', ['response' => 200]);
        }
        self::back('info', 'theme_copied');
    }

    public static function handle_theme_activate(): void
    {
        self::guard('switch_themes', 'mt_theme_activate');
        if (($_POST['mt_step'] ?? '') !== 'do') {
            self::confirm_page('Theme aktivieren?', 'MichaThemeV5 wird als aktives Theme gesetzt. Ihr bisheriges Theme wird abgelöst, seine Einstellungen bleiben gespeichert. Wirklich aktivieren?', 'mt_theme_activate', 'mt_theme_activate', 'Ja, Theme aktivieren');
        }
        $err = MT_Theme_Installer::activate();
        if ($err !== null) {
            wp_die('<p>' . esc_html($err) . '</p><p><a class="button" href="' . esc_url(self::url('info')) . '">Zurück zur Info-Seite</a></p>', 'Theme aktivieren fehlgeschlagen', ['response' => 200]);
        }
        self::back('info', 'theme_activated');
    }
}

<?php

declare(strict_types=1);

// Brückenversion 1.6.0: Baukasten statt altem Updater/Katalog/Install-Anschluss
// WordPress-Attrappen unten, kein echtes WP, kein Netz (Transport ist ein Fake)
define('ABSPATH', __DIR__);
define('HOUR_IN_SECONDS', 3600);
define('MINUTE_IN_SECONDS', 60);
define('MT_PLUGIN_VERSION', '1.6.0');

$GLOBALS['opts'] = [];
$GLOBALS['transients'] = [];
$GLOBALS['site_transients'] = [];
$GLOBALS['hooks'] = [];
$GLOBALS['activation'] = [];
$GLOBALS['can'] = true;
$GLOBALS['nonce_ok'] = true;

function add_filter(string $h, mixed $cb, int $prio = 10, int $args = 1): bool { $GLOBALS['hooks'][$h][$prio][] = $cb; return true; }
function add_action(string $h, mixed $cb, int $prio = 10, int $args = 1): bool { return add_filter($h, $cb, $prio, $args); }
function register_activation_hook(string $f, mixed $cb): void { $GLOBALS['activation'][] = $cb; }
function register_uninstall_hook(string $f, mixed $cb): void {}
function plugin_basename(string $f): string { return 'michatheme-v5/michatheme-v5.php'; }
function admin_url(string $p = ''): string { return 'https://shop.example.invalid/wp-admin/' . $p; }
function home_url(): string { return 'https://shop.example.invalid'; }
function wp_parse_url(string $u, int $c = -1): mixed { return parse_url($u, $c); }
function get_option(string $k, mixed $d = false): mixed { return $GLOBALS['opts'][$k] ?? $d; }
function update_option(string $k, mixed $v, mixed $a = null): bool { $GLOBALS['opts'][$k] = $v; return true; }
function delete_option(string $k): bool { unset($GLOBALS['opts'][$k]); return true; }
function get_transient(string $k): mixed { return $GLOBALS['transients'][$k] ?? false; }
function set_transient(string $k, mixed $v, int $t = 0): bool { $GLOBALS['transients'][$k] = $v; return true; }
function delete_transient(string $k): bool { unset($GLOBALS['transients'][$k]); return true; }
function get_site_transient(string $k): mixed { return $GLOBALS['site_transients'][$k] ?? false; }
function set_site_transient(string $k, mixed $v, int $t = 0): bool { $GLOBALS['site_transients'][$k] = $v; return true; }
function delete_site_transient(string $k): bool { unset($GLOBALS['site_transients'][$k]); return true; }
function current_user_can(string $c): bool { return $GLOBALS['can']; }
function wp_doing_ajax(): bool { return false; }
function wp_unslash(mixed $v): mixed { return $v; }
function wp_nonce_field(string $a, string $n = '_wpnonce', bool $r = true, bool $e = true): string { return '<input type="hidden" name="' . $n . '" value="n">'; }
function wp_verify_nonce(string $n, string $a): int|false { return $GLOBALS['nonce_ok'] && $n === 'n' ? 1 : false; }
function esc_url(string $u): string { return $u; }
function esc_html(string $s): string { return htmlspecialchars($s); }

$root = dirname(__DIR__) . '/plugin/michatheme-v5';
require $root . '/includes/kit-autoload.php';
require $root . '/includes/class-mt-legacy.php';

use Mgd\MichaThemeV5\Kit\Install\{Response, Transport};
use Mgd\MichaThemeV5\Kit\Install\Platform\WordPress as KitInstall;
use Mgd\MichaThemeV5\Kit\Platform\WordPress\Setup;

$fails = 0;
function check(bool $ok, string $msg): void
{
    global $fails;
    echo ($ok ? 'OK   ' : 'FEHL ') . $msg . "\n";
    if (!$ok) { $fails++; }
}
function reset_wp(): void
{
    $GLOBALS['opts'] = []; $GLOBALS['transients'] = []; $GLOBALS['site_transients'] = []; $GLOBALS['hooks'] = [];
    $GLOBALS['activation'] = []; $GLOBALS['can'] = true; $GLOBALS['nonce_ok'] = true; $_POST = [];
    Setup::resetCatalogActions();
    (new ReflectionProperty(\Mgd\MichaThemeV5\Kit\Platform\WordPress\InstallBoot::class, 'booted'))->setValue(null, []);
}
function run_hook(string $h): void
{
    foreach ($GLOBALS['hooks'][$h] ?? [] as $cbs) { foreach ($cbs as $cb) { $cb(); } }
}
// Fake: merkt sich Aufrufe, kein Netz
final class FakeHttp implements Transport
{
    public array $log = [];
    public function send(string $method, string $url, array $headers): Response { $this->log[] = [$method, $url, $headers]; return new Response(204); }
}

$main = (string) file_get_contents($root . '/michatheme-v5.php');
$admin = (string) file_get_contents($root . '/includes/class-mt-admin.php');
$un = (string) file_get_contents($root . '/uninstall.php');
$PRODUCT = 'michatheme-v5-woocommerce';
$ID = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';
$KEY = 'abcdefghijklmnopqrstuvwxyz012345';

// 1. Alter Code ist abgeschaltet (Quelltext)
check(!str_contains($main, 'MT_Updater::boot'), 'Alter Updater wird nicht mehr angemeldet');
check(!str_contains($main, 'Install\\Platform\\WordPress::boot(') && !str_contains($main, 'MT_Install::boot'), 'Alter Install-Anschluss (boot/ping) läuft nicht doppelt');
check(!str_contains($admin, "'license', 'catalog_refresh'") && str_contains($admin, '// catalog_refresh ist abgeschaltet'), 'Alte Empfehlungsliste: Handler nicht mehr angemeldet');
check(!str_contains($admin, 'MT_Install::settings_html') && !str_contains($admin, 'install_card'), 'Alter Schalter unten in den Einstellungen ist weg');
check(!str_contains($admin, 'add_menu_page') && !str_contains($admin, 'add_submenu_page') && str_contains($admin, 'function page_tools'), 'Kein eigenes Menü mehr, eine Seite mit Reitern unter MGD Tools');
check(str_contains($main, 'Setup::tools(') && str_contains($main, "'render' => ['MT_Admin', 'page_tools']") && str_contains($main, "'slug' => 'plugin-michatheme-v5-woocommerce'"), 'MGD Tools: Einstellungsseite und Katalog-Kennung eingetragen');
check(str_contains($main, "'license' => false"), 'MGD Tools: Lizenz-Baustein bleibt aus');
check(str_contains($main, "'repo' => 'MichaThemeV5_WooCommerce'"), 'Eigenes Repo wird aus der Liste ausgeblendet (repo)');
check(preg_match("/Setup::updater\\(__FILE__, '" . $PRODUCT . "', 'woocommerce', MT_PLUGIN_VERSION/", $main) === 1, 'Baukasten-Updater im Hauptplugin mit richtigem Produkt');
check(preg_match("/Setup::cleanupLegacy\\('" . $PRODUCT . "', MT_PLUGIN_VERSION/", $main) === 1, 'cleanupLegacy im Hauptplugin');
check(preg_match("/Setup::uninstall\\('" . $PRODUCT . "', 'woocommerce'\\)/", $un) === 1, 'uninstall.php ruft Setup::uninstall');
check(strpos($un, 'Setup::uninstall') < strpos($un, "delete_option('mgd_install_michatheme_v5_woocommerce')"), 'Abmelden läuft vor dem Löschen der Install-Option');
check(str_contains($un, "'michatheme_v5_license'") && str_contains($un, "mt-license-token"), 'Bisherige Deinstallation (Lizenzcode zurück ins Theme, eigene Daten) bleibt');

// 2. Baukasten-Updater ist angemeldet, alter Hook nicht
reset_wp();
$u = Setup::updater('/srv/wp-content/plugins/michatheme-v5/michatheme-v5.php', $PRODUCT, 'woocommerce', '1.6.0', 'MichaThemeV5', 'https://shop.example.invalid/wp-admin/admin.php?page=michatheme-v5-updates');
check($u !== null, 'Setup::updater liefert den Updater');
foreach (['pre_set_site_transient_update_plugins', 'plugins_api', 'upgrader_pre_download', 'auto_update_plugin'] as $h) {
    check(isset($GLOBALS['hooks'][$h]), 'Baukasten-Filter angemeldet: ' . $h);
}
check(!isset($GLOBALS['hooks']['update_plugins_theme.michael-gahn.de']), 'Alter Filter update_plugins_theme.michael-gahn.de nicht angemeldet');
check($u->autoUpdate(true, (object) ['plugin' => 'michatheme-v5/michatheme-v5.php']) === false, 'Kein Auto-Update fürs Plugin');
check(isset($GLOBALS['hooks']['admin_post_' . \Mgd\MichaThemeV5\Kit\Platform\WordPress\CatalogInstaller::action($PRODUCT)]), 'Knopf Installieren schon beim Laden angemeldet');
check(count($GLOBALS['activation']) >= 2, 'Aktivierungs-Hooks (Hinweis + Uninstall-Hook) angemeldet');

// 3. Genau ein Schalter, Lizenz aus
$dash = Setup::dashboard($PRODUCT, 'woocommerce', '1.6.0', 'MichaThemeV5', $u, null, false, '');
$html = $dash->html('2026-11-24');
check(substr_count($html, 'Installation bei Michael Gahn DESIGN melden') === 1 && substr_count($html, '<input type="checkbox"') === 1, 'Im Dashboard genau ein Schalter Installation melden');
check(!str_contains($html, 'mgd-kit-h-license') && !str_contains($html, 'Lizenzschlüssel'), 'Dashboard ohne Lizenz-Bereich');
check(!isset($GLOBALS['hooks']['admin_menu']), 'Dashboard legt mit parent leer keinen eigenen Kit-Menüpunkt an');
$GLOBALS['can'] = false;
check($dash->html('2026-11-24') === '', 'Dashboard ohne Recht manage_options bleibt leer');
$GLOBALS['can'] = true;

// 4. Install-ID, Schalter und Schlüssel bleiben (gleiche Option wie vorher)
reset_wp();
$state = ['report' => false, 'install_id' => $ID, 'key' => $KEY, 'last_ping' => 1];
$GLOBALS['opts']['mgd_install_michatheme_v5_woocommerce'] = json_encode($state);
$before = $GLOBALS['opts']['mgd_install_michatheme_v5_woocommerce'];
Setup::updater('/srv/x/michatheme-v5/michatheme-v5.php', $PRODUCT, 'woocommerce', '1.6.0', 'MichaThemeV5');
Setup::cleanupLegacy($PRODUCT, '1.6.0', ['mgd_install_michatheme_v5_woocommerce', 'mgd_install_*', 'mgd_*', 'mgd_kit_*']);
$form = KitInstall::settingsHtml($PRODUCT, 'woocommerce', '1.6.0');
check($GLOBALS['opts']['mgd_install_michatheme_v5_woocommerce'] === $before, 'Install-Option (ID, Schlüssel, Schalter) nach dem Umbau unverändert');
check(!str_contains($form, ' checked'), 'Schalter aus bleibt aus (Zustand wird übernommen)');
$c = KitInstall::client($PRODUCT, 'woocommerce', '1.6.0');
check($c !== null && $c->isEnabled() === false, 'Baukasten liest die alte Install-Option');
// alte und neue Option heißen gleich
check(str_contains((string) file_get_contents($root . '/includes/class-mt-install.php'), "OPTION = 'mgd_install_michatheme_v5_woocommerce'"), 'Option heißt wie beim alten Client');

// 5. cleanupLegacy löscht die Install-Option nicht
$GLOBALS['opts'] += ['mgd_install_other_product' => 'x', 'mgd_kit_rec_michatheme_v5_woocommerce' => 'r'];
run_hook('admin_init');
// Hinweis: admin_init vom Install-Client pingt nicht (report aus)
check(isset($GLOBALS['opts']['mgd_install_michatheme_v5_woocommerce']), 'cleanupLegacy löscht die Install-Option nicht (auch nicht mit mgd_*)');
check(isset($GLOBALS['opts']['mgd_install_other_product']) && isset($GLOBALS['opts']['mgd_kit_rec_michatheme_v5_woocommerce']), 'cleanupLegacy lässt Install-Daten und Kit-Daten in Ruhe');
check(($GLOBALS['opts']['mgd_kit_cleanup_michatheme_v5_woocommerce'] ?? '') === '1.6.0', 'cleanupLegacy läuft 1x pro Version');

// 6. Reste vom alten Updater/Katalog (michatheme_v5_*) räumt MT_Legacy
reset_wp();
$GLOBALS['site_transients']['michatheme_v5_release'] = ['version' => '1.5.1'];
foreach (['cat_fresh', 'cat_good', 'cat_pause', 'cat_last_refresh'] as $t) { $GLOBALS['transients']['michatheme_v5_' . $t] = 1; }
$GLOBALS['transients']['michatheme_v5_recheck'] = 1;
$GLOBALS['opts']['michatheme_v5_state'] = ['redirect' => false];
$GLOBALS['opts']['michatheme_v5_license'] = 'code';
$GLOBALS['opts']['mgd_install_michatheme_v5_woocommerce'] = '{"report":true}';
MT_Legacy::clean();
check($GLOBALS['site_transients'] === [] && !isset($GLOBALS['transients']['michatheme_v5_cat_good']) && !isset($GLOBALS['transients']['michatheme_v5_cat_fresh']), 'Alter Updater- und Katalog-Cache weg');
check(($GLOBALS['transients']['michatheme_v5_recheck'] ?? 0) === 1 && ($GLOBALS['opts']['michatheme_v5_license'] ?? '') === 'code' && isset($GLOBALS['opts']['mgd_install_michatheme_v5_woocommerce']), 'Lizenzcode, Lizenz-Prüfung und Install-Option bleiben');
$GLOBALS['site_transients']['michatheme_v5_release'] = ['x' => 1];
MT_Legacy::clean();
check(isset($GLOBALS['site_transients']['michatheme_v5_release']), 'Aufräumen läuft nur 1x pro Version');

// 7. Nur POST mit Nonce und Recht speichert den Schalter
function try_post(array $post, bool $can, bool $nonce): array
{
    reset_wp();
    global $PRODUCT, $ID;
    $GLOBALS['opts']['mgd_install_michatheme_v5_woocommerce'] = json_encode(['report' => true, 'install_id' => $ID, 'last_ping' => time()]);
    Setup::updater('/srv/x/michatheme-v5/michatheme-v5.php', $PRODUCT, 'woocommerce', '1.6.0', 'MichaThemeV5');
    $GLOBALS['can'] = $can;
    $GLOBALS['nonce_ok'] = $nonce;
    $_POST = $post;
    run_hook('admin_init');
    $_POST = [];

    return json_decode((string) $GLOBALS['opts']['mgd_install_michatheme_v5_woocommerce'], true);
}
$aus = ['mgd_install_save_michatheme_v5_woocommerce' => '1', '_mgd_install_nonce' => 'n']; // Haken weg = Feld fehlt
check((try_post([], true, true)['report'] ?? null) === true, 'GET (kein POST-Inhalt): nichts ändert sich');
check((try_post($aus, false, true)['report'] ?? null) === true, 'Ohne Recht manage_options: nichts ändert sich');
check((try_post($aus, true, false)['report'] ?? null) === true, 'Ohne gültige Nonce: nichts ändert sich');
check((try_post(['mgd_install_save_andere' => '1', '_mgd_install_nonce' => 'n'], true, true)['report'] ?? null) === true, 'Formular eines anderen Plugins ändert nichts');
$st = try_post($aus, true, true);
check(($st['report'] ?? null) === false && ($st['install_id'] ?? '') === $ID || ($st['report'] ?? null) === false, 'POST mit Nonce und Recht schaltet ab');

// 8. Deinstallieren: Plattform abmelden, nur eigene Daten weg
reset_wp();
$GLOBALS['opts'] = ['mgd_install_michatheme_v5_woocommerce' => json_encode(['report' => true, 'install_id' => $ID, 'key' => $KEY]),
    'mgd_kit_rec_michatheme_v5_woocommerce' => 'r', 'mgd_kit_cleanup_michatheme_v5_woocommerce' => '1.6.0', 'mgd_kit_ver_michatheme_v5_woocommerce' => '1.6.0',
    'mgd_install_mgd_share_wordpress' => '{"report":true}', 'michatheme_v5_license' => 'code'];
$GLOBALS['site_transients']['mgd_kit_upd_michatheme_v5_woocommerce'] = 'json';
$GLOBALS['transients']['mgd_install_notice_michatheme_v5_woocommerce'] = 1;
$http = new FakeHttp();
$ok = Setup::uninstall($PRODUCT, 'woocommerce', $http);
check($ok === true && count($http->log) === 1 && $http->log[0][0] === 'DELETE' && ($http->log[0][2]['X-MGD-Install-Id'] ?? '') === $ID && ($http->log[0][2]['Authorization'] ?? '') === 'Bearer ' . $KEY, 'Uninstall meldet genau einmal mit der alten ID und dem alten Schlüssel ab');
check(array_keys($GLOBALS['opts']) === ['mgd_install_mgd_share_wordpress', 'michatheme_v5_license'], 'Uninstall räumt nur die eigenen Kit-/Install-Optionen auf');
check($GLOBALS['site_transients'] === [] && $GLOBALS['transients'] === [], 'Uninstall räumt Zwischenspeicher und Hinweis auf');
check(Setup::uninstall('Böse Kennung', 'woocommerce', $http) === false && count($http->log) === 1, 'Falsche Produktkennung: nichts passiert');

echo "\n" . ($fails === 0 ? 'Alles gut.' : $fails . ' Fehler.') . "\n";
exit($fails === 0 ? 0 : 1);

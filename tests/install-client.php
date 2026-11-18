<?php

declare(strict_types=1);

// Install-Client (Free-Lizenz): boot wirft nie, nur erlaubte Felder, Schalter aus = nix senden. Ohne echtes Netz
define('ABSPATH', __DIR__);
define('HOUR_IN_SECONDS', 3600);
define('MINUTE_IN_SECONDS', 60);
define('MT_PLUGIN_VERSION', '1.5.0');
define('MT_PLUGIN_FILE', '/srv/wp-content/plugins/michatheme-v5/michatheme-v5.php');

$GLOBALS['opts'] = [];
$GLOBALS['actions'] = [];
$GLOBALS['transients'] = [];
$GLOBALS['site_transients'] = [];
$GLOBALS['remote'] = [];
$GLOBALS['is_admin'] = false;
$GLOBALS['throw_opts'] = false;

function get_option(string $k, mixed $d = false): mixed { if ($GLOBALS['throw_opts']) { throw new RuntimeException('db kaputt'); } return $GLOBALS['opts'][$k] ?? $d; }
function update_option(string $k, mixed $v, mixed $a = null): bool { if ($GLOBALS['throw_opts']) { throw new RuntimeException('db kaputt'); } $GLOBALS['opts'][$k] = $v; return true; }
function delete_option(string $k): bool { unset($GLOBALS['opts'][$k]); return true; }
function add_action(string $h, callable $cb, int $p = 10, int $a = 1): void { $GLOBALS['actions'][$h][] = $cb; }
function add_filter(string $h, mixed $cb, int $p = 10, int $a = 1): void { $GLOBALS['actions'][$h][] = $cb; }
function register_activation_hook(string $f, callable $cb): void { $GLOBALS['actions']['activate'][] = $cb; }
function set_transient(string $k, mixed $v, int $t): void { $GLOBALS['transients'][$k] = $v; }
function get_transient(string $k): mixed { return $GLOBALS['transients'][$k] ?? false; }
function delete_transient(string $k): void { unset($GLOBALS['transients'][$k]); }
function get_site_transient(string $k): mixed { return $GLOBALS['site_transients'][$k] ?? false; }
function set_site_transient(string $k, mixed $v, int $t): void { $GLOBALS['site_transients'][$k] = $v; }
function current_user_can(string $c): bool { return true; }
function wp_doing_ajax(): bool { return false; }
function is_admin(): bool { return $GLOBALS['is_admin']; }
function wp_verify_nonce(string $n, string $a): bool { return $n === 'ok'; }
function wp_unslash(mixed $v): mixed { return $v; }
function wp_nonce_field(string $a, string $n, bool $r = true, bool $e = true): string { return '<input type="hidden" name="' . $n . '" value="ok">'; }
function wp_parse_url(string $u, int $c = -1): mixed { return parse_url($u, $c); }
function home_url(): string { return 'https://Mein-Shop.example.invalid/shop?x=1'; }
function esc_html(string $s): string { return htmlspecialchars($s, ENT_QUOTES); }
function esc_url(string $s): string { return $s; }
function is_wp_error(mixed $v): bool { return false; }
function wp_safe_remote_get(string $url, array $opt): array { $GLOBALS['remote'][] = $url; return ['status' => 500, 'body' => '']; }
function wp_remote_retrieve_response_code(array $r): int { return $r['status']; }
function wp_remote_retrieve_body(array $r): string { return $r['body']; }
function plugin_basename(string $f): string { return 'michatheme-v5/michatheme-v5.php'; }

$root = dirname(__DIR__) . '/plugin/michatheme-v5';
require $root . '/includes/class-mt-install.php';
require $root . '/includes/class-mt-updater.php';

use MichaThemeV5\Plugin\Install\{InstallClient, Response, Settings, Transport, CallbackStore};
use MichaThemeV5\Plugin\Install\Platform\WordPress;

$fails = 0;
function check(bool $ok, string $msg): void
{
    global $fails;
    echo ($ok ? 'OK   ' : 'FEHL ') . $msg . "\n";
    if (!$ok) { $fails++; }
}

// Fake-Netz: merkt sich alles, schickt nix raus
final class FakeTransport implements Transport
{
    public array $sent = [];
    public function __construct(private ?Response $reply = null, private bool $throw = false) {}
    public function send(string $method, string $url, array $headers): Response
    {
        $this->sent[] = [$method, $url, $headers];
        if ($this->throw) { throw new RuntimeException('offline'); }
        return $this->reply ?? new Response(200, ['x-mgd-install-key' => str_repeat('k', 32)], '{"schema":1}');
    }
}
function client(FakeTransport $t, array &$state, int $now = 1800000000): InstallClient
{
    $store = new CallbackStore(static function () use (&$state) { return json_encode($state); }, static function (string $v) use (&$state): void { $state = json_decode($v, true); });
    return new InstallClient($t, $store, 'michatheme-v5-woocommerce', 'woocommerce', '1.5.0', 'mein-shop.example.invalid', static fn (): int => $now);
}

// boot wirft nie
$thrown = null;
try {
    WordPress::boot(MT_PLUGIN_FILE, 'michatheme-v5-woocommerce', 'woocommerce', MT_PLUGIN_VERSION, 'https://x.invalid/wp-admin/admin.php?page=michatheme-v5');
    WordPress::boot(MT_PLUGIN_FILE, 'Ungültig Produkt!', 'woocommerce', MT_PLUGIN_VERSION, 'https://x.invalid/');
} catch (\Throwable $e) {
    $thrown = $e;
}
check($thrown === null, 'boot wirft keinen Fehler (auch mit falschem Produktnamen)');
check(count($GLOBALS['actions']['admin_init'] ?? []) === 2 && isset($GLOBALS['actions']['admin_notices'], $GLOBALS['actions']['activate']), 'boot meldet admin_init, admin_notices und Aktivierung an');
check(!isset($GLOBALS['actions']['init']) && !isset($GLOBALS['actions']['wp']) && !isset($GLOBALS['actions']['template_redirect']), 'Kein Frontend-Hook');

// admin_init mit kaputter Datenbank / falschem Produkt: kein Absturz, kein Netz (Schalter aus)
$GLOBALS['opts'][MT_Install::OPTION] = json_encode(['report' => false]);
$thrown = null;
try {
    foreach ($GLOBALS['actions']['admin_init'] as $cb) { $cb(); }
    // kaputte DB nur bei der Seite testen, admin_init würde sonst echt ins Netz gehen
    $GLOBALS['throw_opts'] = true;
    WordPress::settingsHtml('michatheme-v5-woocommerce', 'woocommerce', '1.5.0');
} catch (\Throwable $e) {
    $thrown = $e;
}
$GLOBALS['throw_opts'] = false;
check($thrown === null, 'admin_init und Einstellungsseite überleben eine kaputte Datenbank');
foreach ($GLOBALS['actions']['activate'] as $cb) { $cb(); }
check(get_transient(MT_Install::NOTICE) === 1, 'Nach dem Aktivieren wird einmal der Hinweis vorgemerkt');
ob_start();
foreach ($GLOBALS['actions']['admin_notices'] as $cb) { $cb(); }
$notice = (string) ob_get_clean();
check(str_contains($notice, 'theme.michael-gahn.de') && str_contains($notice, 'page=michatheme-v5') && get_transient(MT_Install::NOTICE) === false, 'Hinweis mit Link zum Schalter, nur einmal');

// ping sendet nur erlaubte Felder
$state = [];
$t = new FakeTransport();
check(client($t, $state)->ping(true) === true, 'Erste Meldung klappt');
[$m, $url, $h] = $t->sent[0];
$allowed = ['X-MGD-Install-Id', 'X-MGD-Platform', 'X-MGD-Domain', 'X-MGD-Version', 'Authorization'];
check($m === 'GET' && $url === 'https://theme.michael-gahn.de/api/v1/updates/michatheme-v5-woocommerce.json', 'Nur unsere Adresse, Produkt nur im Pfad, ohne Query');
check(array_diff(array_keys($h), $allowed) === [] && !isset($h['Authorization']), 'Nur erlaubte Felder (erste Meldung ohne Schlüssel)');
check($h['X-MGD-Platform'] === 'woocommerce' && $h['X-MGD-Version'] === '1.5.0' && $h['X-MGD-Domain'] === 'mein-shop.example.invalid' && InstallClient::isUuid($h['X-MGD-Install-Id']), 'Werte: Shopsystem, Version, Domain, zufällige ID');
check(($state['key'] ?? '') === str_repeat('k', 32) && isset($state['last_ping']), 'Schlüssel gespeichert');
check(client($t, $state, 1800000000 + 3600)->ping() === false && count($t->sent) === 1, 'Am selben Tag keine zweite Meldung');
check(client($t, $state, 1800000000 + 86401)->ping() === true && count($t->sent) === 2, 'Nach einem Tag wieder');
$h2 = $t->sent[1][2];
check(array_diff(array_keys($h2), $allowed) === [] && $h2['Authorization'] === 'Bearer ' . str_repeat('k', 32) && $h2['X-MGD-Install-Id'] === $h['X-MGD-Install-Id'], 'Weitere Meldung mit Schlüssel und derselben ID');

// Plattform weg: kein Fehler nach außen, Pause
$st2 = [];
$off = new FakeTransport(null, true);
check(client($off, $st2)->ping(true) === false && isset($st2['next_try']), 'Offline: kein Absturz, neuer Versuch später');

// Schalter aus > kein Senden
$t3 = new FakeTransport();
$c = client($t3, $state, 1800000000 + 200000);
Settings::handle($c, [Settings::actionName($c) => '1']);
check($c->isEnabled() === false && !isset($state['key']) && !isset($state['install_id']), 'Schalter aus: Schlüssel und ID weg');
check(count($t3->sent) === 1 && $t3->sent[0][0] === 'DELETE', 'Beim Abschalten nur die Löschanfrage');
$c->ping(true);
$c->ping();
check(count($t3->sent) === 1, 'Schalter aus: keine Meldung mehr, auch nicht erzwungen');
$GLOBALS['opts'][MT_Install::OPTION] = json_encode(['report' => false]);
check(MT_Install::due() === false, 'Schalter aus: auch keine Update-Abfrage über die Zähl-Adresse');
$html = WordPress::settingsHtml('michatheme-v5-woocommerce', 'woocommerce', '1.5.0');
check(str_contains($html, Settings::HINT) && !str_contains($html, ' checked') && str_contains($html, '_mgd_install_nonce'), 'Einstellungsseite: Hinweis, Schalter aus, Nonce');

// Updater: feste Datei, wenn nicht Admin oder Meldung heute schon war (kein echtes Netz)
$GLOBALS['opts'][MT_Install::OPTION] = json_encode(['report' => true, 'last_ping' => time() - 60]);
$GLOBALS['is_admin'] = true;
check(MT_Install::due() === false && MT_Install::feed() === null, 'Heute schon gemeldet: keine zweite Meldung über die Update-Prüfung');
MT_Updater::offer(false, [], 'michatheme-v5/michatheme-v5.php');
check(($GLOBALS['remote'][0] ?? '') === MT_Updater::FEED_URL, 'Update-Prüfung nimmt dann die feste Datei');
$GLOBALS['is_admin'] = false;
$GLOBALS['opts'][MT_Install::OPTION] = '';
check(MT_Install::due() === true && MT_Install::feed() === null, 'Außerhalb vom Admin (Cron, Shop) nie über die Zähl-Adresse');

// Plugin-Kopf, readme, Deinstallation
$main = (string) file_get_contents($root . '/michatheme-v5.php');
$kurz = 'Meldet sich höchstens einmal am Tag bei theme.michael-gahn.de (Plugin, Version, Shopsystem, Domain, zufällige ID). Abschaltbar in den Einstellungen.';
check(str_contains($main, $kurz) && str_contains((string) file_get_contents($root . '/readme.txt'), $kurz), 'Kurztext in Plugin-Kopf und readme.txt');
check(preg_match("/try \\{\\s*\\\\MichaThemeV5\\\\Plugin\\\\Install\\\\Platform\\\\WordPress::boot\\(__FILE__, 'michatheme-v5-woocommerce', 'woocommerce', MT_PLUGIN_VERSION/", $main) === 1, 'Hauptplugin startet den Client in try/catch mit richtigem Produkt');
$un = (string) file_get_contents($root . '/uninstall.php');
check(str_contains($un, "delete_option('" . MT_Install::OPTION . "')") && str_contains($un, "delete_transient('" . MT_Install::NOTICE . "')"), 'Deinstallation entfernt die Daten des Install-Clients');
$theme = '';
foreach (['functions.php', 'inc'] as $p) {
    $path = dirname(__DIR__) . '/' . $p;
    foreach (is_dir($path) ? glob($path . '/*.php') : [$path] as $f) { $theme .= (string) file_get_contents($f); }
}
check(!str_contains($theme, 'InstallClient') && !str_contains($theme, 'api/v1/updates'), 'Theme allein meldet sich nicht');

echo "\n" . ($fails === 0 ? 'Alles gut.' : $fails . ' Fehler.') . "\n";
exit($fails === 0 ? 0 : 1);

<?php

declare(strict_types=1);

// Update-Weg vom Plugin: Prüfsummen-Datei, SHA-256 vorm Entpacken, Pfade im ZIP, kein Auto-Update
define('ABSPATH', __DIR__);
define('HOUR_IN_SECONDS', 3600);
define('MINUTE_IN_SECONDS', 60);
define('MT_PLUGIN_FILE', '/srv/wp-content/plugins/michatheme-v5/michatheme-v5.php');

$GLOBALS['hooks'] = [];
$GLOBALS['site_transients'] = [];
$GLOBALS['remote'] = [];
$GLOBALS['downloads'] = [];
$GLOBALS['download_file'] = '';

final class WP_Error
{
    public function __construct(public string $code = '', public string $message = '') {}
}
function add_filter(string $hook, mixed $cb, int $prio = 10, int $args = 1): void { $GLOBALS['hooks'][$hook][] = $cb; }
function plugin_basename(string $file): string { return 'michatheme-v5/michatheme-v5.php'; }
function get_site_transient(string $k): mixed { return $GLOBALS['site_transients'][$k] ?? false; }
function set_site_transient(string $k, mixed $v, int $ttl): void { $GLOBALS['site_transients'][$k] = $v; }
function is_wp_error(mixed $v): bool { return $v instanceof WP_Error; }
function wp_safe_remote_get(string $url, array $opt): array { $GLOBALS['remote'][] = [$url, $opt]; return $GLOBALS['remote_reply']; }
function wp_remote_retrieve_response_code(array $r): int { return $r['status']; }
function wp_remote_retrieve_body(array $r): string { return $r['body']; }
function download_url(string $url, int $timeout = 300): mixed
{
    $GLOBALS['downloads'][] = $url;
    $tmp = tempnam(sys_get_temp_dir(), 'mt-dl-');
    copy($GLOBALS['download_file'], $tmp);
    return $tmp;
}

require dirname(__DIR__) . '/plugin/michatheme-v5/includes/class-mt-updater.php';

$fails = 0;
function check(bool $ok, string $msg): void
{
    global $fails;
    echo ($ok ? 'OK   ' : 'FEHL ') . $msg . "\n";
    if (!$ok) { $fails++; }
}
function zip_with(array $entries): string
{
    $file = tempnam(sys_get_temp_dir(), 'mt-zip-') . '.zip';
    $zip = new ZipArchive();
    $zip->open($file, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    foreach ($entries as $name => $body) { $zip->addFromString($name, $body); }
    $zip->close();
    return $file;
}

$good = zip_with(['michatheme-v5/michatheme-v5.php' => "<?php\n/* Version: 0.6.0 */", 'michatheme-v5/theme/michatheme-v5/style.css' => '/* Version: 0.6.0 */']);
$url = 'https://github.com/MichaelGahnDESIGN/MichaThemeV5_WooCommerce/releases/download/v0.6.0/michatheme-v5-plugin-0.6.0.zip';
$feed = ['schema' => 1, 'product' => 'michatheme-v5-woocommerce', 'version' => '0.6.0', 'filename' => 'michatheme-v5-plugin-0.6.0.zip',
    'size' => filesize($good), 'sha256' => hash_file('sha256', $good), 'root_dir' => 'michatheme-v5', 'download_url' => $url];

// Prüfsummen-Datei
check(MT_Updater::FEED_URL === 'https://theme.michael-gahn.de/plugin-updates/michatheme-v5-woocommerce.json', 'Feste Adresse der Prüfsummen-Datei');
$ok = MT_Updater::parse_feed($feed);
check(is_array($ok) && $ok['version'] === '0.6.0' && $ok['package'] === $url && $ok['sha256'] === $feed['sha256'], 'Gültige Prüfsummen-Datei akzeptiert');
$bad = [['schema' => 2], ['product' => 'michatheme-v5'], ['version' => '0.6.0-beta'], ['version' => '0.7.0'], ['filename' => 'x.zip'],
    ['root_dir' => 'other'], ['size' => 0], ['size' => 30000000], ['size' => '12'], ['sha256' => strtoupper($feed['sha256'])],
    ['sha256' => 'abc'], ['download_url' => 'https://evil.example/michatheme-v5-plugin-0.6.0.zip'],
    ['download_url' => str_replace('https://', 'http://', $url)]];
$allRejected = true;
foreach ($bad as $change) { if (MT_Updater::parse_feed(array_replace($feed, $change)) !== null) { $allRejected = false; echo '     durchgerutscht: ' . json_encode($change) . "\n"; } }
check($allRejected, 'Ungültige Prüfsummen-Dateien abgewiesen');

// Hinweis in der Plugin-Liste kommt nur aus der Prüfsummen-Datei
$GLOBALS['remote_reply'] = ['status' => 200, 'body' => json_encode($feed)];
$offer = MT_Updater::offer(false, [], 'michatheme-v5/michatheme-v5.php');
check(is_array($offer) && $offer['version'] === '0.6.0' && $offer['package'] === $url, 'Update-Hinweis aus der Prüfsummen-Datei');
[$calledUrl, $opt] = $GLOBALS['remote'][0];
check($calledUrl === MT_Updater::FEED_URL && $opt['redirection'] === 0 && $opt['limit_response_size'] <= 65536, 'Abruf ohne Weiterleitung und mit Größenlimit');
check(!str_contains((string) file_get_contents(dirname(__DIR__) . '/plugin/michatheme-v5/includes/class-mt-updater.php'), 'api.github.com'), 'Keine Abfrage der GitHub-API mehr');
check(MT_Updater::offer('x', [], 'anderes/plugin.php') === 'x', 'Fremde Plugins bleiben unberührt');

// SHA-256 und Pfade vorm Entpacken
$GLOBALS['download_file'] = $good;
$res = MT_Updater::verify_download(false, $url, null);
check(is_string($res) && is_file($res) && hash_file('sha256', $res) === $feed['sha256'], 'Passendes ZIP wird an WordPress übergeben');
check(MT_Updater::verify_download(false, 'https://example.org/other.zip', null) === false, 'Fremde Pakete laufen normal weiter');
$GLOBALS['site_transients'] = [];
$res = MT_Updater::verify_download(false, $url, null);
check(is_string($res) && hash_file('sha256', $res) === $feed['sha256'], 'Auch ohne Zwischenspeicher wird geprüft');
$GLOBALS['site_transients'] = [];
$GLOBALS['remote_reply'] = ['status' => 500, 'body' => ''];
check(MT_Updater::verify_download(false, $url, null) instanceof WP_Error, 'Ohne Prüfsumme kein Update');
$GLOBALS['site_transients'] = [];
$GLOBALS['remote_reply'] = ['status' => 200, 'body' => json_encode($feed)];

$evil = zip_with(['michatheme-v5/michatheme-v5.php' => 'boese']);
$GLOBALS['download_file'] = $evil;
$before = count(glob(sys_get_temp_dir() . '/mt-dl-*') ?: []);
$res = MT_Updater::verify_download(false, $url, null);
check($res instanceof WP_Error, 'Falsche Prüfsumme bricht das Update ab');
check(count(glob(sys_get_temp_dir() . '/mt-dl-*') ?: []) === $before, 'Abgelehnter Download wird gelöscht');

foreach ([['../evil.php' => 'x'], ['michatheme-v5/../evil.php' => 'x'], ['other/evil.php' => 'x'], ['/abs.php' => 'x'], ['michatheme-v5\\evil.php' => 'x']] as $entries) {
    $zip = zip_with(['michatheme-v5/michatheme-v5.php' => 'ok'] + $entries);
    check(!MT_Updater::zip_safe($zip), 'Unsicherer Pfad abgewiesen: ' . array_key_first($entries));
}
check(MT_Updater::zip_safe($good), 'Sauberes ZIP erlaubt');

// Kein stilles Update
check(MT_Updater::no_auto_update(true, (object) ['plugin' => 'michatheme-v5/michatheme-v5.php']) === false, 'Automatische WordPress-Updates für dieses Plugin aus');
check(MT_Updater::no_auto_update(true, (object) ['plugin' => 'andere/andere.php']) === true, 'Andere Plugins behalten ihre Einstellung');

MT_Updater::boot();
foreach (['update_plugins_theme.michael-gahn.de', 'upgrader_pre_download', 'auto_update_plugin'] as $hook) {
    check(isset($GLOBALS['hooks'][$hook]), 'Filter angemeldet: ' . $hook);
}
$main = (string) file_get_contents(dirname(__DIR__) . '/plugin/michatheme-v5/michatheme-v5.php');
check(str_contains($main, 'Update URI: https://theme.michael-gahn.de/plugin-updates/michatheme-v5-woocommerce.json'), 'Update URI zeigt auf die Prüfsummen-Datei');
check(preg_match('/MT_Updater::boot\(\);\s*\n\s*if \(is_admin\(\)\)/', $main) === 1, 'Updater auch im Cron angemeldet (nicht nur im Admin)');

echo "\n" . ($fails === 0 ? 'Alles gut.' : $fails . ' Fehler.') . "\n";
exit($fails === 0 ? 0 : 1);

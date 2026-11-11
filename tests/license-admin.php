<?php

declare(strict_types=1);

// Lizenzcode: Entfernen wirkt überall, Umzug ohne Klartext-Rest, kein Absturz bei Cache-Fehlern
define('ABSPATH', __DIR__);
$GLOBALS['opts'] = [];
$GLOBALS['mods'] = [];
$GLOBALS['throw_cache'] = false;
function get_option(string $k, mixed $d = false): mixed { return $GLOBALS['opts'][$k] ?? $d; }
function update_option(string $k, mixed $v, mixed $a = null): bool { if ($GLOBALS['db_broken'] ?? false) { return false; } $GLOBALS['opts'][$k] = $v; return true; }
function delete_option(string $k): bool { unset($GLOBALS['opts'][$k]); return true; }
function get_theme_mod(string $k, mixed $d = false): mixed { return $GLOBALS['mods'][$k] ?? $d; }
function set_theme_mod(string $k, mixed $v): void { $GLOBALS['mods'][$k] = $v; }
function remove_theme_mod(string $k): void { unset($GLOBALS['mods'][$k]); }
function get_transient(string $k): mixed { if ($GLOBALS['throw_cache']) { throw new RuntimeException('cache kaputt'); } return false; }
function set_transient(string $k, mixed $v, int $t): void {}
function delete_transient(string $k): void {}
function delete_site_transient(string $k): void {}
final class MT_Fake_Theme { public function __construct(private string $td) {} public function get(string $k): string { return $k === 'TextDomain' ? $this->td : ''; } }
function wp_get_themes(): array { return ['twentytwenty' => new MT_Fake_Theme('twentytwenty'), 'MichaThemeV5' => new MT_Fake_Theme('michatheme-v5')]; }
function wp_parse_url(string $u, int $c): mixed { return parse_url($u, $c); }
function home_url(): string { return 'https://shop.example.invalid'; }
function wp_remote_post(string $u, array $o): array { return ['status' => 500, 'body' => '']; }
function is_wp_error(mixed $r): bool { return false; }
function wp_remote_retrieve_body(array $r): string { return $r['body']; }
function wp_remote_retrieve_response_code(array $r): int { return $r['status']; }

$root = dirname(__DIR__);
foreach (['Sanitizer', 'LicenseClient', 'LicenseTexts'] as $c) {
    $f = $root . '/plugin/michatheme-v5/includes/generated/' . $c . '.php';
    if (is_file($f)) { require_once $f; }
}
require $root . '/plugin/michatheme-v5/includes/class-mt-license.php';
final class MT_Bridge {}
require $root . '/inc/license.php';

$fails = 0;
function check(bool $ok, string $msg): void
{
    global $fails;
    echo ($ok ? 'OK   ' : 'FEHL ') . $msg . "\n";
    if (!$ok) { $fails++; }
}

$code = 'eyJhbGciOiJFZERTQSJ9.eyJzdWIiOiJ0ZXN0In0.c2lnbmF0dXJl';

// Umzug aus dem Customizer: Original wird entfernt
$GLOBALS['mods']['mt-license-token'] = $code;
MT_License::migrate();
check(get_option('michatheme_v5_license') === $code, 'Code ins Plugin übernommen');
check(!isset($GLOBALS['mods']['mt-license-token']), 'Klartext-Kopie im Customizer entfernt');

// Datenbank kaputt: Code bleibt im Customizer statt zu verschwinden
$GLOBALS['opts'] = []; $GLOBALS['mods'] = ['mt-license-token' => $code]; $GLOBALS['db_broken'] = true;
MT_License::migrate();
check(($GLOBALS['mods']['mt-license-token'] ?? '') === $code, 'Fehlgeschlagener Umzug löscht den Code nicht');
$GLOBALS['db_broken'] = false;
MT_License::migrate();

// Altlast: Kopie im Customizer wird später auch aufgeräumt
$GLOBALS['mods']['mt-license-token'] = $code;
MT_License::migrate();
check(!isset($GLOBALS['mods']['mt-license-token']) && get_option('michatheme_v5_license') === $code, 'Alte Klartext-Kopie wird aufgeräumt');

// Entfernen: Theme fällt nicht auf den alten Code zurück
$GLOBALS['mods']['mt-license-token'] = $code;
MT_License::remove();
check(get_option('michatheme_v5_license', '') === '' && !isset($GLOBALS['mods']['mt-license-token']), 'Entfernen löscht Option und Theme-Einstellung');
check(mt_license_token() === '', 'Theme prüft danach ohne Code (Free)');

// Kein Absturz bei Cache-Fehler
$GLOBALS['opts']['michatheme_v5_license'] = $code;
$GLOBALS['throw_cache'] = true;
$s = mt_license_state();
check(($s['valid'] ?? true) === false && ($s['tier'] ?? '') === 'free', 'Theme-Status bei Fehler: Free statt Absturz');
check(is_string(mt_license_status_text()), 'Customizer-Text bei Fehler ohne Absturz');
$GLOBALS['throw_cache'] = false;

// Deinstallation: Code geht nicht verloren, Theme ohne Plugin kann weiter prüfen
$GLOBALS['opts']['michatheme_v5_license'] = $code;
$GLOBALS['mods'] = [];
$GLOBALS['opts']['theme_mods_MichaThemeV5'] = ['mt-color' => '#fff'];
define('WP_UNINSTALL_PLUGIN', true);
require $root . '/plugin/michatheme-v5/uninstall.php';
check(get_option('michatheme_v5_license', '') === '' && ($GLOBALS['opts']['theme_mods_MichaThemeV5']['mt-license-token'] ?? '') === $code
    && ($GLOBALS['opts']['theme_mods_MichaThemeV5']['mt-color'] ?? '') === '#fff', 'Beim Löschen des Plugins landet der Code im MichaTheme');
check(!isset($GLOBALS['mods']['mt-license-token']), 'Nicht im gerade aktiven fremden Theme');

// Customizer: kein Klartext-Feld
$cust = (string) file_get_contents($root . '/inc/customizer.php');
check(str_contains($cust, "'type' => 'password'") && str_contains($cust, "class_exists('MT_Bridge', false)"), 'Customizer: Passwortfeld, mit Plugin nur Verweis auf die Lizenzseite');
check(preg_match("/license_token' && class_exists\\('MT_Bridge', false\\)\\) \\{\\s*continue;/", $cust) === 1, 'Mit Plugin wird die Einstellung gar nicht registriert (kein Wert in den Customizer-Daten)');

echo "\n" . ($fails === 0 ? 'Alles gut.' : $fails . ' Fehler.') . "\n";
exit($fails === 0 ? 0 : 1);

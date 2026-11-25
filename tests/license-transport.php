<?php

declare(strict_types=1);

// Echter Theme-/Plugin-Client, WordPress-HTTP durch eine speicherinterne Testhülle ersetzt.
define('ABSPATH', __DIR__);
$calls = [];
$cache = [];
function wp_parse_url(string $url, int $component): mixed { return parse_url($url, $component); }
function home_url(): string { return 'https://shop.example.invalid'; }
function get_transient(string $key): mixed { return $GLOBALS['cache'][$key] ?? false; }
function set_transient(string $key, mixed $value, int $ttl): void { $GLOBALS['cache'][$key] = $value; }
function wp_remote_post(string $url, array $options): array
{
    $GLOBALS['calls'][] = [$url, $options];
    return ['status' => 200, 'body' => json_encode(['valid' => true, 'license' => ['tier' => 'pro', 'modules' => []], 'cache_valid_until' => gmdate('c', time() + 3600)])];
}
function is_wp_error(mixed $response): bool { return false; }
function wp_remote_retrieve_body(array $response): string { return $response['body']; }
function wp_remote_retrieve_response_code(array $response): int { return $response['status']; }

require dirname(__DIR__) . '/inc/license.php';
require dirname(__DIR__) . '/plugin/michatheme-v5/includes/generated/LicenseClient.php';
require dirname(__DIR__) . '/plugin/michatheme-v5/includes/class-mt-license.php';

foreach ([mt_license(), MT_License::client()] as $index => $client) {
    if (!$client->state('test.license.' . $index)['valid']) {
        throw new RuntimeException('Gültige Testantwort wurde nicht erkannt.');
    }
    [$url, $request] = $calls[$index];
    if ($url !== 'https://theme.michael-gahn.de/api/license/verify' || parse_url($url, PHP_URL_QUERY) !== null
        || $request['headers']['Authorization'] !== 'Bearer test.license.' . $index
        || json_decode($request['body'], true) !== ['domain' => 'shop.example.invalid']
        || $request['redirection'] !== 0 || $request['sslverify'] !== true || $request['limit_response_size'] > 262144) {
        throw new RuntimeException('Unsicherer Lizenztransport.');
    }
}
echo "Theme und Plugin: POST, Bearer-Header, TLS-Prüfung, keine Weiterleitungen geprüft.\n";

<?php
defined('ABSPATH') || exit;

require_once __DIR__ . '/license-client.php';

use MichaThemeV5\Client\LicenseClient;

/** Lizenzclient: prüft den Schlüssel serverseitig gegen theme.michael-gahn.de, 24 h Zwischenspeicher, Gnadenfrist bei Ausfall. */
function mt_license(): LicenseClient
{
    static $client = null;
    if ($client === null) {
        $api = defined('MT_LICENSE_API') ? MT_LICENSE_API : 'https://theme.michael-gahn.de/api';
        $domain = (string) wp_parse_url(home_url(), PHP_URL_HOST);
        $client = new LicenseClient(
            $api,
            $domain,
            static function (string $url): ?array {
                $r = wp_remote_get($url, ['timeout' => 4, 'redirection' => 2, 'headers' => ['Accept' => 'application/json']]);
                if (is_wp_error($r)) {
                    return null;
                }
                $json = json_decode((string) wp_remote_retrieve_body($r), true);

                return ['status' => (int) wp_remote_retrieve_response_code($r), 'json' => is_array($json) ? $json : []];
            },
            static function (string $k): ?array {
                $v = get_transient($k);

                return is_array($v) ? $v : null;
            },
            static function (string $k, array $v, int $ttl): void {
                set_transient($k, $v, $ttl);
            }
        );
    }

    return $client;
}

function mt_license_state(): array
{
    return mt_license()->state((string) get_theme_mod('mt-license-token', ''));
}

/** Ist das Modul (z. B. eines separat ausgelieferten Add-ons) per Lizenz freigeschaltet? Im Theme selbst nutzt keine Funktion diese Prüfung. */
function mt_license_allows(string $module): bool
{
    try {
        return mt_license()->allows((string) get_theme_mod('mt-license-token', ''), $module);
    } catch (\Throwable $e) {
        return false; // Der Shop bricht nie: bei jedem Fehler gilt Free.
    }
}

/** Lesbarer Status für den Customizer. */
function mt_license_status_text(): string
{
    $s = mt_license_state();
    if ($s['valid']) {
        return 'Lizenz gültig (' . $s['tier'] . ').';
    }
    $why = ['no_token' => 'Kein Schlüssel eingetragen. Das Theme läuft mit allen Free-Funktionen.', 'unreachable' => 'Lizenzserver nicht erreichbar. Free-Funktionen bleiben aktiv.',
        'token_expired' => 'Lizenz abgelaufen.', 'token_revoked' => 'Lizenz widerrufen oder gekündigt.', 'domain_mismatch' => 'Der Schlüssel gilt nicht für diese Domain.'];

    return $why[$s['reason']] ?? 'Lizenz ungültig. Free-Funktionen bleiben aktiv.';
}

<?php
defined('ABSPATH') || exit;

use MichaThemeV5\Plugin\CatalogFeed;

/** Plugin-Empfehlungen: Feed von theme.michael-gahn.de. Fehler sind leise, im Shop (Frontend) wird nie abgerufen. */
final class MT_Catalog
{
    public const SELF_ID = 'plugin-michatheme-v5-woocommerce';

    public static function load(bool $refresh = false): array
    {
        if (!is_admin()) {
            return ['items' => [], 'stale' => false, 'error' => 'unreachable', 'checked_at' => 0];
        }
        try {
            $feed = new CatalogFeed(
                'woocommerce',
                self::SELF_ID,
                static function (string $url): ?array {
                    $r = wp_remote_get($url, ['timeout' => 5, 'redirection' => 0, 'limit_response_size' => CatalogFeed::MAX_BYTES + 1, 'headers' => ['Accept' => 'application/json']]);
                    if (is_wp_error($r)) {
                        return null;
                    }

                    return ['status' => (int) wp_remote_retrieve_response_code($r), 'body' => (string) wp_remote_retrieve_body($r)];
                },
                static fn (string $k): mixed => get_transient('michatheme_v5_' . $k) === false ? null : get_transient('michatheme_v5_' . $k),
                static function (string $k, mixed $v, int $ttl): void {
                    set_transient('michatheme_v5_' . $k, $v, $ttl);
                }
            );

            return $feed->load($refresh);
        } catch (\Throwable $e) {
            return ['items' => [], 'stale' => false, 'error' => 'unreachable', 'checked_at' => 0];
        }
    }
}

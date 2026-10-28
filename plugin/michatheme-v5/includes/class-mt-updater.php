<?php
defined('ABSPATH') || exit;

/**
 * Update-Hinweis über GitHub (Muster der anderen MGD-Plugins). Nur Hinweis in der WordPress-Liste, installiert wird nichts von allein.
 * Quelle und Paketname sind fest, nur stabile Releases, bei jedem Fehler still.
 */
final class MT_Updater
{
    private const REPO = 'MichaelGahnDESIGN/MichaThemeV5_WooCommerce';
    private const PACKAGE = 'michatheme-v5-plugin-{version}.zip';

    public static function boot(): void
    {
        add_filter('update_plugins_github.com', [self::class, 'offer'], 30, 3);
    }

    /** @param mixed $update */
    public static function offer($update, array $data, string $file)
    {
        if ($file !== plugin_basename(MT_PLUGIN_FILE)) {
            return $update;
        }
        $key = 'michatheme_v5_release';
        $release = get_site_transient($key);
        if ($release === false) {
            $release = [];
            $r = wp_safe_remote_get('https://api.github.com/repos/' . self::REPO . '/releases/latest', ['timeout' => 8, 'redirection' => 0, 'limit_response_size' => 262144, 'headers' => ['Accept' => 'application/vnd.github+json']]);
            if (!is_wp_error($r) && wp_remote_retrieve_response_code($r) === 200) {
                $b = json_decode(wp_remote_retrieve_body($r), true);
                if (is_array($b) && empty($b['draft']) && empty($b['prerelease']) && preg_match('/^v?((?:0|[1-9][0-9]*)\.(?:0|[1-9][0-9]*)\.(?:0|[1-9][0-9]*))$/D', (string) ($b['tag_name'] ?? ''), $m)) {
                    $name = str_replace('{version}', $m[1], self::PACKAGE);
                    $url = 'https://github.com/' . self::REPO . '/releases/download/' . $b['tag_name'] . '/' . $name;
                    foreach ((array) ($b['assets'] ?? []) as $a) {
                        if (($a['name'] ?? '') === $name && ($a['browser_download_url'] ?? '') === $url) {
                            $release = ['version' => $m[1], 'package' => $url, 'slug' => 'michatheme-v5'];
                        }
                    }
                }
            }
            set_site_transient($key, $release, $release ? 6 * HOUR_IN_SECONDS : 15 * MINUTE_IN_SECONDS);
        }

        return $release ?: false;
    }
}

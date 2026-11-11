<?php
defined('ABSPATH') || exit;

/**
 * Update-Hinweis wie beim MGD Update Client: Prüfsummen-Datei auf theme.michael-gahn.de, ZIP vom GitHub-Release.
 * Nur Hinweis in der WordPress-Liste, eingespielt wird nur per Klick. SHA-256 und Pfade werden vor dem Entpacken geprüft.
 */
final class MT_Updater
{
    public const FEED_URL = 'https://theme.michael-gahn.de/plugin-updates/michatheme-v5-woocommerce.json';
    private const PRODUCT = 'michatheme-v5-woocommerce';
    private const REPO = 'MichaelGahnDESIGN/MichaThemeV5_WooCommerce';
    private const ROOT = 'michatheme-v5';
    private const MAX_BYTES = 20000000;
    private const MAX_ENTRIES = 5000;
    private const KEY = 'michatheme_v5_release';

    public static function boot(): void
    {
        // Hostname aus "Update URI" im Plugin-Kopf
        add_filter('update_plugins_theme.michael-gahn.de', [self::class, 'offer'], 30, 3);
        // als letzter Filter, damit kein anderes Plugin die Prüfung überspringt
        add_filter('upgrader_pre_download', [self::class, 'verify_download'], PHP_INT_MAX, 3);
        add_filter('auto_update_plugin', [self::class, 'no_auto_update'], 99, 2);
    }

    /**
     * Prüfsummen-Datei streng prüfen. null = unbrauchbar.
     * @return array{version:string,package:string,sha256:string,size:int,slug:string}|null
     */
    public static function parse_feed(array $feed): ?array
    {
        $ver = $feed['version'] ?? null;
        if (($feed['schema'] ?? null) !== 1 || ($feed['product'] ?? null) !== self::PRODUCT || !is_string($ver)
            || !preg_match('/^(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)$/D', $ver)) {
            return null;
        }
        $name = 'michatheme-v5-plugin-' . $ver . '.zip';
        // Adresse muss exakt zum Tag passen
        $url = 'https://github.com/' . self::REPO . '/releases/download/v' . $ver . '/' . $name;
        $size = $feed['size'] ?? null;
        $sha = $feed['sha256'] ?? null;
        if (($feed['filename'] ?? null) !== $name || ($feed['root_dir'] ?? null) !== self::ROOT || ($feed['download_url'] ?? null) !== $url
            || !is_int($size) || $size <= 0 || $size > self::MAX_BYTES || !is_string($sha) || !preg_match('/^[a-f0-9]{64}$/D', $sha)) {
            return null;
        }

        return ['version' => $ver, 'package' => $url, 'sha256' => $sha, 'size' => $size, 'slug' => 'michatheme-v5'];
    }

    /** @param mixed $update */
    public static function offer($update, array $data, string $file)
    {
        if ($file !== plugin_basename(MT_PLUGIN_FILE)) {
            return $update;
        }
        $release = self::release();

        return $release ? ['version' => $release['version'], 'package' => $release['package'], 'slug' => $release['slug']] : false;
    }

    /** @return array<string,mixed> leer = kein Update bekannt */
    private static function release(): array
    {
        $release = get_site_transient(self::KEY);
        // alter Eintrag (ohne sha256/size) zählt nicht
        if (is_array($release) && ($release === [] || self::cached_ok($release))) {
            return $release;
        }
        $release = [];
        $r = wp_safe_remote_get(self::FEED_URL, ['timeout' => 8, 'redirection' => 0, 'limit_response_size' => 65536, 'headers' => ['Accept' => 'application/json']]);
        if (!is_wp_error($r) && wp_remote_retrieve_response_code($r) === 200) {
            $b = json_decode(wp_remote_retrieve_body($r), true);
            $release = is_array($b) ? (self::parse_feed($b) ?? []) : [];
        }
        set_site_transient(self::KEY, $release, $release ? 6 * HOUR_IN_SECONDS : 15 * MINUTE_IN_SECONDS);

        return $release;
    }

    private static function cached_ok(array $r): bool
    {
        $v = (string) ($r['version'] ?? '');

        return preg_match('/^(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)$/D', $v) === 1
            && ($r['package'] ?? '') === 'https://github.com/' . self::REPO . '/releases/download/v' . $v . '/michatheme-v5-plugin-' . $v . '.zip'
            && is_string($r['sha256'] ?? null) && preg_match('/^[a-f0-9]{64}$/D', $r['sha256']) === 1
            && is_int($r['size'] ?? null) && $r['size'] > 0 && $r['size'] <= self::MAX_BYTES;
    }

    /**
     * upgrader_pre_download: unser Paket selbst laden und prüfen, bevor WordPress entpackt.
     * Bei Fehler bricht WordPress ab, die alte Version bleibt unverändert.
     * @param mixed $reply
     * @return mixed
     */
    public static function verify_download($reply, $package, $upgrader)
    {
        if (is_wp_error($reply) || !is_string($package) || !str_starts_with($package, 'https://github.com/' . self::REPO . '/releases/download/')) {
            return $reply;
        }
        // unser Paket nie ungeprüft durchlassen, auch wenn der Zwischenspeicher leer ist
        $release = self::release();
        if (($release['package'] ?? '') !== $package) {
            if (is_string($reply) && is_file($reply)) {
                @unlink($reply);
            }

            return new WP_Error('mt_update_unknown', 'Für dieses Update-Paket gibt es keine passende Prüfsumme. Die installierte Version bleibt unverändert.');
        }
        if ($reply !== false) {
            // ein anderes Plugin hat schon geladen: genau diese Datei prüfen
            $tmp = is_string($reply) && is_file($reply) ? $reply : new WP_Error('mt_update_foreign', 'Das Update-Paket wurde von einem anderen Plugin geladen und kann nicht geprüft werden.');
        } else {
            $tmp = download_url($package, 60);
        }
        if (is_wp_error($tmp)) {
            return $tmp;
        }
        clearstatcache(true, $tmp);
        if (filesize($tmp) !== $release['size'] || !hash_equals($release['sha256'], (string) hash_file('sha256', $tmp)) || !self::zip_safe($tmp)) {
            @unlink($tmp);

            return new WP_Error('mt_update_checksum', 'Das Update-Paket ist beschädigt oder stimmt nicht mit der Prüfsumme überein. Die installierte Version bleibt unverändert.');
        }

        return $tmp;
    }

    /** Alle Einträge unter michatheme-v5/, keine ../, keine absoluten Pfade, keine Backslashes, keine Symlinks. */
    public static function zip_safe(string $file): bool
    {
        if (!class_exists('ZipArchive')) {
            return false;
        }
        $zip = new ZipArchive();
        if ($zip->open($file) !== true) {
            return false;
        }
        try {
            if ($zip->numFiles < 1 || $zip->numFiles > self::MAX_ENTRIES) {
                return false;
            }
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = (string) $zip->getNameIndex($i);
                if ($name === '' || str_contains($name, '\\') || str_contains($name, "\0") || str_starts_with($name, '/')
                    || !str_starts_with($name, self::ROOT . '/')) {
                    return false;
                }
                foreach (explode('/', rtrim($name, '/')) as $seg) {
                    if ($seg === '..' || $seg === '.' || $seg === '') {
                        return false;
                    }
                }
                // Symlink im ZIP (Unix-Modus 0120000)
                if ($zip->getExternalAttributesIndex($i, $os, $attr) && $os === ZipArchive::OPSYS_UNIX && (($attr >> 16) & 0170000) === 0120000) {
                    return false;
                }
            }
        } finally {
            $zip->close();
        }

        return true;
    }

    /**
     * Kein stilles Update: WordPress-Auto-Update für dieses Plugin immer aus.
     * @param mixed $update
     * @return mixed
     */
    public static function no_auto_update($update, $item)
    {
        return is_object($item) && ($item->plugin ?? '') === plugin_basename(MT_PLUGIN_FILE) ? false : $update;
    }
}

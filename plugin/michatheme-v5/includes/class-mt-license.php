<?php
defined('ABSPATH') || exit;

use MichaThemeV5\Plugin\{LicenseClient, LicenseTexts, Sanitizer};

/** Lizenzcode und Status. Der Code steht nur in der Option michatheme_v5_license (nicht automatisch geladen), nie in Logs oder im Frontend. */
final class MT_License
{
    private const OPT = 'michatheme_v5_license';

    public static function token(): string
    {
        $t = get_option(self::OPT, '');

        return is_string($t) ? $t : '';
    }

    public static function has_token(): bool
    {
        return self::token() !== '';
    }

    /** Letzte 6 Zeichen, der Rest bleibt verborgen. */
    public static function masked(): string
    {
        $t = self::token();

        return $t === '' ? '' : '…' . substr($t, -6);
    }

    public static function save(string $token): bool
    {
        $token = trim($token);
        if (!Sanitizer::licenseFormat($token)) {
            return false;
        }
        update_option(self::OPT, $token, false);
        self::client()->forget($token);

        return true;
    }

    public static function remove(): void
    {
        delete_option(self::OPT);
        // alter Customizer-Wert muss mit weg, sonst prüft das Theme weiter damit
        remove_theme_mod('mt-license-token');
    }

    /** Einmaliger Umzug aus der Theme-Einstellung, danach kein Klartext mehr im Customizer. */
    public static function migrate(): void
    {
        if (self::has_token()) {
            // Altlast aus früheren Versionen: gleiche Kopie im Customizer aufräumen
            if (get_theme_mod('mt-license-token', '') === self::token()) {
                remove_theme_mod('mt-license-token');
            }

            return;
        }
        $old = get_theme_mod('mt-license-token', '');
        if (is_string($old) && Sanitizer::licenseFormat(trim($old))) {
            update_option(self::OPT, trim($old), false);
            remove_theme_mod('mt-license-token');
        }
    }

    public static function client(): LicenseClient
    {
        static $client = null;
        if ($client === null) {
            $api = defined('MT_LICENSE_API') ? MT_LICENSE_API : 'https://theme.michael-gahn.de/api';
            $domain = (string) wp_parse_url(home_url(), PHP_URL_HOST);
            $client = new LicenseClient(
                $api,
                $domain,
                static function (string $url, array $request): ?array {
                    $r = wp_remote_post($url, ['timeout' => 4, 'redirection' => 0, 'sslverify' => true, 'limit_response_size' => 262144, 'headers' => $request['headers'], 'body' => $request['body']]);
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

    /** @return array<string,mixed> Der Shop bricht nie: bei jedem Fehler gilt Free. */
    public static function state(): array
    {
        try {
            return self::client()->state(self::token());
        } catch (\Throwable $e) {
            return ['valid' => false, 'tier' => 'free', 'modules' => [], 'source' => 'none', 'reason' => 'unreachable'];
        }
    }

    public static function allows(string $module): bool
    {
        try {
            return self::client()->allows(self::token(), $module);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** "Jetzt prüfen": höchstens einmal pro Minute wirklich neu fragen. */
    public static function recheck(): bool
    {
        if (get_transient('michatheme_v5_recheck') !== false) {
            return false;
        }
        set_transient('michatheme_v5_recheck', 1, 60);
        self::client()->forget(self::token());

        return true;
    }

    /** @return array{kind:string,title:string,text:string} */
    public static function describe(): array
    {
        $s = self::state();

        return LicenseTexts::describe($s, is_string($s['period_end'] ?? null) ? $s['period_end'] : null);
    }
}

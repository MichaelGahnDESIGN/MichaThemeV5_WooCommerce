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
    }

    /** Einmalige Kopie aus der Theme-Einstellung (Original bleibt stehen). */
    public static function migrate(): void
    {
        if (self::has_token()) {
            return;
        }
        $old = get_theme_mod('mt-license-token', '');
        if (is_string($old) && Sanitizer::licenseFormat(trim($old))) {
            update_option(self::OPT, trim($old), false);
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

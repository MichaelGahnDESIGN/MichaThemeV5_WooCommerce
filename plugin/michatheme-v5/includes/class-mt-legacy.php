<?php
defined('ABSPATH') || exit;

// Reste vom alten Updater + alter Empfehlungsliste (ab 1.6.0 macht das der Baukasten)
// Kit-cleanupLegacy nimmt nur mgd_ Namen > diese hier räumen wir selber, 1x pro Version
final class MT_Legacy
{
    // Site-Transient vom alten Updater (michatheme_v5_release) + Transients der alten Empfehlungsliste
    public const NAMES = ['michatheme_v5_release', 'michatheme_v5_cat_fresh', 'michatheme_v5_cat_good', 'michatheme_v5_cat_pause', 'michatheme_v5_cat_last_refresh'];

    public static function clean(): void
    {
        try {
            $state = get_option('michatheme_v5_state', []);
            $state = is_array($state) ? $state : [];
            if (($state['legacy_clean'] ?? '') === MT_PLUGIN_VERSION) {
                return;
            }
            foreach (self::NAMES as $n) {
                delete_site_transient($n);
                delete_transient($n);
            }
            $state['legacy_clean'] = MT_PLUGIN_VERSION;
            update_option('michatheme_v5_state', $state, false);
        } catch (\Throwable $e) {
            // egal
        }
    }
}

<?php

declare(strict_types=1);

namespace MichaTheme\Core;

/**
 * Preisverlauf für die Rabattanzeige (§ 11 PAngV): Bezugspreis ist der niedrigste Preis der
 * letzten 30 Tage VOR der Preisermäßigung. Reine Funktionen, ohne WordPress-Abhängigkeit.
 * Ein Verlauf ist eine Liste [[Zeitstempel, Preis], ...] (aufsteigend); jeder Eintrag gilt ab seinem Zeitpunkt.
 */
final class PriceHistory
{
    public const WINDOW = 30 * 86400;
    public const MAX_ENTRIES = 200;

    /** Neuen Preis eintragen, wenn er sich geändert hat. */
    public static function record(array $history, float $price, int $now): array
    {
        $history = self::clean($history);
        $last = $history ? $history[count($history) - 1] : null;
        if ($last !== null && abs($last[1] - $price) < 0.005) {
            return $history;
        }
        $history[] = [$now, round($price, 2)];
        // Eintrag behalten, der vor dem Fenster gilt, ältere verwerfen
        $cut = $now - self::WINDOW;
        while (count($history) > 2 && $history[1][0] <= $cut) {
            array_shift($history);
        }

        return array_slice($history, -self::MAX_ENTRIES);
    }

    /**
     * Niedrigster Preis der 30 Tage vor der letzten Preisänderung, oder null, wenn kein belastbarer
     * Vergleich möglich ist (kein früherer Preis, oder der aktuelle Preis ist nicht niedriger).
     */
    public static function lowestBeforeCurrent(array $history, float $current): ?float
    {
        $history = self::clean($history);
        $n = count($history);
        if ($n < 2) {
            return null;
        }
        $since = $history[$n - 1][0];
        $from = $since - self::WINDOW;
        $low = null;
        for ($i = 0; $i < $n - 1; $i++) {
            [$start, $price] = $history[$i];
            $end = $history[$i + 1][0];
            if ($end > $from && $start < $since) { // Preis galt zeitweise im Fenster
                $low = $low === null ? $price : min($low, $price);
            }
        }

        return $low !== null && $current < $low ? $low : null;
    }

    /** @return list<array{0:int,1:float}> */
    private static function clean(array $history): array
    {
        $out = [];
        foreach ($history as $e) {
            if (is_array($e) && isset($e[0], $e[1]) && is_numeric($e[0]) && is_numeric($e[1])) {
                $out[] = [(int) $e[0], (float) $e[1]];
            }
        }
        usort($out, fn ($a, $b) => $a[0] <=> $b[0]);

        return $out;
    }
}

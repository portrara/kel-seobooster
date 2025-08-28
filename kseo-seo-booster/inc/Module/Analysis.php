<?php
/**
 * Deterministic content analysis (no external APIs)
 *
 * @package KSEO\SEO_Booster\Module
 */

namespace KSEO\SEO_Booster\Module;

if (!defined('ABSPATH')) { exit; }

class Analysis {
    /**
     * Analyze content and seed to extract entities, intent, difficulty, suggestions
     * @param string $content
     * @param string $seed
     * @param string $locale
     * @return array
     */
    public static function analyze(string $content, string $seed, string $locale = 'en'): array {
        $text = wp_strip_all_tags($content);
        $seed = trim((string) $seed);

        // Entities: capitalized phrases + H1/H2 words
        $entities = array();
        preg_match_all('/\b([A-Z][a-zA-Z]+(?:\s+[A-Z][a-zA-Z]+)*)\b/u', $text, $m1);
        if (!empty($m1[1])) { $entities = array_values(array_unique(array_slice($m1[1], 0, 25))); }
        // Intent by keyword modifiers
        $intent = self::detect_intent($seed . ' ' . $text);
        // Difficulty heuristic 0-100
        $difficulty = self::difficulty_score($text, $seed);
        // Suggestions: uni/bi-grams from seed + text
        $suggestions = self::suggest($text, $seed);

        return array(
            'entities' => $entities,
            'intent' => $intent,
            'difficulty' => $difficulty,
            'suggestions' => $suggestions,
        );
    }

    private static function detect_intent(string $text): string {
        $t = strtolower($text);
        if (preg_match('/\b(how|tutorial|guide)\b/', $t)) return 'informational';
        if (preg_match('/\b(what|definition|meaning)\b/', $t)) return 'informational';
        if (preg_match('/\b(best|top|vs|compare|review)\b/', $t)) return 'commercial';
        if (preg_match('/\b(price|buy|deal|discount|near me|order)\b/', $t)) return 'transactional';
        if (preg_match('/\b(near me|location|hours|address)\b/', $t)) return 'local';
        return 'informational';
    }

    private static function difficulty_score(string $text, string $seed): int {
        $len = max(1, strlen($text));
        $uniq = count(array_unique(str_split(strtolower(preg_replace('/[^a-z]/', '', $text)))));
        $ratio = $uniq / 26.0;
        $mods = 0;
        if (preg_match('/\b(best|top|cheap|free)\b/i', $seed)) $mods += 10;
        if (preg_match('/\b(price|buy|order)\b/i', $seed)) $mods += 15;
        $base = min(100, 20 + (int) (log($len, 10) * 10) + (int) ($ratio * 30) + $mods);
        return max(0, min(100, $base));
    }

    private static function suggest(string $text, string $seed): array {
        $words = preg_split('/\W+/u', strtolower($seed . ' ' . $text), -1, PREG_SPLIT_NO_EMPTY);
        $words = array_slice(array_values(array_unique($words)), 0, 100);
        $unigrams = array_slice($words, 0, 20);
        $bigrams = array();
        for ($i = 0; $i < min(count($words) - 1, 20); $i++) {
            $bigrams[] = $words[$i] . ' ' . $words[$i + 1];
        }
        return array_values(array_unique(array_merge($unigrams, $bigrams)));
    }
}



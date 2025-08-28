<?php
/**
 * Deterministic recommendations generator
 *
 * @package KSEO\SEO_Booster\Module
 */

namespace KSEO\SEO_Booster\Module;

if (!defined('ABSPATH')) { exit; }

class Recommendations {
    /**
     * Build recommendations from analysis and post context
     * @param array $analysis
     * @param \WP_Post|array $post
     * @return array
     */
    public static function build(array $analysis, $post): array {
        $title = is_object($post) ? get_the_title($post) : (isset($post['title']) ? (string) $post['title'] : '');
        $bestEntity = isset($analysis['entities'][0]) ? $analysis['entities'][0] : '';
        $intent = isset($analysis['intent']) ? $analysis['intent'] : 'informational';
        $suggestTitle = self::title_from($title, $bestEntity, $intent);
        $metaDesc = self::meta_from($title, $bestEntity, $intent);
        $outline = self::outline_from($analysis);
        $faq = self::faq_from($analysis, $title);
        $jsonLd = self::jsonld_from($title, $metaDesc, $faq);
        return array(
            'title' => $suggestTitle,
            'meta' => array('description' => $metaDesc),
            'outline' => $outline,
            'faq' => $faq,
            'schema' => $jsonLd
        );
    }

    private static function title_from(string $title, string $entity, string $intent): string {
        $prefix = $intent === 'transactional' ? 'Buy ' : ($intent === 'commercial' ? 'Best ' : 'Guide: ');
        $core = $entity !== '' ? $entity : $title;
        $out = trim($prefix . $core);
        return mb_substr($out, 0, 60);
    }

    private static function meta_from(string $title, string $entity, string $intent): string {
        $msg = $intent === 'transactional' ? 'Compare prices and order today.' : ($intent === 'commercial' ? 'See top picks and comparisons.' : 'Learn key facts and best practices.');
        $core = $entity !== '' ? $entity : $title;
        $out = $core . ' – ' . $msg;
        return mb_substr($out, 0, 155);
    }

    private static function outline_from(array $analysis): array {
        $ents = array_slice(isset($analysis['entities']) ? (array) $analysis['entities'] : array(), 0, 6);
        $sugs = array_slice(isset($analysis['suggestions']) ? (array) $analysis['suggestions'] : array(), 0, 6);
        $sections = array();
        foreach ($ents as $e) { $sections[] = array('h2' => $e, 'children' => array()); }
        foreach ($sugs as $s) { $sections[] = array('h2' => ucwords($s), 'children' => array()); }
        return array_slice($sections, 0, 8);
    }

    private static function faq_from(array $analysis, string $title): array {
        $q1 = 'What is ' . (isset($analysis['entities'][0]) ? $analysis['entities'][0] : $title) . '?';
        $q2 = 'How to use ' . (isset($analysis['entities'][1]) ? $analysis['entities'][1] : 'it') . '?';
        $q3 = 'What are the benefits of ' . (isset($analysis['entities'][2]) ? $analysis['entities'][2] : $title) . '?';
        return array(
            array('question' => $q1, 'answer' => 'A concise explanation relevant to the topic.'),
            array('question' => $q2, 'answer' => 'Step-by-step guidance derived from the content.'),
            array('question' => $q3, 'answer' => 'Key advantages summarized for readers.')
        );
    }

    private static function jsonld_from(string $title, string $desc, array $faq): array {
        $faqItems = array_map(function ($q) {
            return array(
                '@type' => 'Question',
                'name' => $q['question'],
                'acceptedAnswer' => array(
                    '@type' => 'Answer',
                    'text' => $q['answer']
                )
            );
        }, $faq);
        return array(
            '@context' => 'https://schema.org',
            '@graph' => array(
                array(
                    '@type' => 'Article',
                    'headline' => $title,
                    'description' => $desc
                ),
                array(
                    '@type' => 'FAQPage',
                    'mainEntity' => $faqItems
                )
            )
        );
    }
}



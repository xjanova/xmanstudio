<?php

namespace App\Support;

use App\Models\Article;
use Illuminate\Support\Str;

/**
 * The text of a Facebook post that shares a knowledge-base article: teach first, sell softly.
 *
 *   title
 *   a short teaser (the excerpt, or the opening paragraph)
 *   one concrete thing the reader learns right here (the article's first list item)
 *   the link, with a quiet "who wrote this"
 *   3–5 hashtags
 *
 * No emoji walls, no "comment to get…" bait (Facebook cuts the reach of that), no price, no
 * "buy now": the article itself carries the products it relates to.
 */
final class FacebookArticleCaption
{
    public const TEASER_CHARS = 300;

    public const TIP_CHARS = 180;

    public const MAX_HASHTAGS = 5;

    /** A hashtag per category, for when the article has few tags of its own. */
    private const CATEGORY_TAGS = [
        'ai' => 'AI',
        'web' => 'พัฒนาเว็บไซต์',
        'security' => 'ความปลอดภัยไซเบอร์',
        'blockchain' => 'Blockchain',
        'infra' => 'Cloud',
        'business' => 'ไอทีสำหรับธุรกิจ',
        'howto' => 'สอนไอที',
        'it' => 'ข่าวไอที',
    ];

    private const BRAND_TAG = 'XMANStudio';

    private const GENERIC_TAG = 'ความรู้ไอที';

    public static function for(Article $article): string
    {
        $lines = [trim((string) $article->title)];

        $teaser = self::teaser($article);
        if ($teaser !== '') {
            $lines[] = '';
            $lines[] = $teaser;
        }

        $tip = self::tip($article, $teaser);
        if ($tip !== null) {
            $lines[] = '';
            $lines[] = 'เกร็ดหนึ่งข้อจากบทความ: ' . $tip;
        }

        $lines[] = '';
        $lines[] = 'อ่านฉบับเต็มได้ที่ ' . $article->url();
        $lines[] = 'คลังความรู้ไอทีภาษาไทย โดย XMAN Studio';
        $lines[] = '';
        $lines[] = implode(' ', array_map(fn ($tag) => '#' . $tag, self::hashtags($article)));

        return implode("\n", $lines);
    }

    /** The excerpt when there is one, else the opening paragraph — cut at a word boundary. */
    public static function teaser(Article $article): string
    {
        $text = self::plain((string) $article->excerpt);
        if ($text === '') {
            $text = self::plain(self::nth('p', (string) $article->body, 0) ?? '');
        }

        return self::cut($text, self::TEASER_CHARS);
    }

    /**
     * One concrete thing the post teaches by itself: the first list item worth reading (steps and
     * checklists are where an IT article teaches), else the second paragraph. Skipped when it
     * would only repeat the teaser.
     */
    public static function tip(Article $article, string $teaser = ''): ?string
    {
        $body = (string) $article->body;
        $candidates = [];
        for ($i = 0; $i < 6 && ($li = self::nth('li', $body, $i)) !== null; $i++) {
            $candidates[] = $li;
        }
        if (($p = self::nth('p', $body, 1)) !== null) {
            $candidates[] = $p;
        }

        foreach ($candidates as $html) {
            $text = self::plain($html);
            if (mb_strlen($text) < 20 || ($teaser !== '' && str_contains($teaser, Str::limit($text, 40, '')))) {
                continue;
            }

            return self::cut($text, self::TIP_CHARS);
        }

        return null;
    }

    /**
     * 3–5 hashtags: the article's own tags and focus keyword first, then its category, a generic
     * "IT knowledge" tag, and the brand last.
     *
     * @return list<string>
     */
    public static function hashtags(Article $article): array
    {
        $candidates = array_merge(
            array_values(array_filter((array) ($article->tags ?? []), 'is_string')),
            [(string) $article->focus_keyword, self::CATEGORY_TAGS[$article->category] ?? self::CATEGORY_TAGS['it'], self::GENERIC_TAG],
        );

        $tags = [];
        foreach ($candidates as $candidate) {
            $tag = self::tagify($candidate);
            if ($tag === '' || isset($tags[mb_strtolower($tag)]) || mb_strtolower($tag) === mb_strtolower(self::BRAND_TAG)) {
                continue;
            }
            $tags[mb_strtolower($tag)] = $tag;
            if (count($tags) === self::MAX_HASHTAGS - 1) {
                break;
            }
        }

        return [...array_values($tags), self::BRAND_TAG];
    }

    /** "Next.js 15" → "Nextjs15"; Thai stays Thai (with its vowel and tone marks). */
    private static function tagify(string $text): string
    {
        $tag = preg_replace('/[^\p{L}\p{M}\p{N}_]+/u', '', $text) ?? '';

        return mb_strlen($tag) > 30 || preg_match('/^\d+$/', $tag) ? '' : $tag;
    }

    /** Inner HTML of the $index-th <$tag> element of the body, or null. */
    private static function nth(string $tag, string $html, int $index): ?string
    {
        if (preg_match_all('~<' . $tag . '(?:\s[^>]*)?>(.*?)</' . $tag . '>~isu', $html, $m) && isset($m[1][$index])) {
            return $m[1][$index];
        }

        return null;
    }

    private static function plain(string $html): string
    {
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace('/\s+/u', ' ', $text) ?? '');
    }

    /** At most $limit characters, ending on a space when one is near (Thai breaks at spaces between phrases). */
    private static function cut(string $text, int $limit): string
    {
        if (mb_strlen($text) <= $limit) {
            return $text;
        }

        $head = mb_substr($text, 0, $limit);
        $space = mb_strrpos($head, ' ');
        if ($space !== false && $space > $limit * 0.6) {
            $head = mb_substr($head, 0, $space);
        }

        return rtrim($head, ' ,.;:-–—') . '…';
    }
}

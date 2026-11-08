<?php

namespace App\Support;

use Illuminate\Support\Str;
use League\CommonMark\CommonMarkConverter;

/**
 * Article bodies are written in Markdown (by the AI writer or an admin) and stored as HTML.
 * Raw HTML in the Markdown is stripped and unsafe links (javascript:, data:) are dropped,
 * so whatever a model or a pasted text contains, the page only ever gets plain formatting.
 */
class ArticleHtml
{
    public static function fromMarkdown(string $markdown): string
    {
        $converter = new CommonMarkConverter([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 20,
        ]);

        $html = (string) $converter->convert(self::stripFence($markdown));

        return self::decorate($html);
    }

    /** Plain text of an HTML body, for word counts, descriptions and the RSS feed. */
    public static function text(string $html): string
    {
        $text = html_entity_decode(strip_tags(str_replace(['</p>', '</li>', '</h2>', '</h3>', '<br>'], ' ', $html)), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace('/\s+/u', ' ', $text) ?? '');
    }

    /**
     * Thai has no spaces between words, so "words" is estimated from characters: about
     * 5 Thai characters per word, which is close to what a Thai word segmenter reports.
     */
    public static function wordCount(string $html): int
    {
        $text = self::text($html);
        $thaiChars = preg_match_all('/[\x{0E00}-\x{0E7F}]/u', $text);
        $latinWords = preg_match_all('/[A-Za-z0-9]+/', $text);

        return (int) round($thaiChars / 5) + $latinWords;
    }

    public static function readingMinutes(int $words): int
    {
        return max(1, (int) ceil($words / 220));
    }

    /**
     * Headings (h2/h3) with their ids, for the table of contents.
     *
     * @return array<int, array{level:int, id:string, text:string}>
     */
    public static function headings(string $html): array
    {
        preg_match_all('/<h([23]) id="([^"]+)">(.*?)<\/h\1>/su', $html, $m, PREG_SET_ORDER);

        return array_map(fn ($h) => [
            'level' => (int) $h[1],
            'id' => $h[2],
            'text' => html_entity_decode(strip_tags($h[3]), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
        ], $m);
    }

    /** A model sometimes wraps its whole answer in ```markdown fences. */
    private static function stripFence(string $markdown): string
    {
        $markdown = trim($markdown);

        if (preg_match('/^```[a-z]*\s*\n(.*)\n```$/s', $markdown, $m)) {
            return $m[1];
        }

        return $markdown;
    }

    /**
     * Give h2/h3 stable ids (table of contents), and make links that leave the site open in
     * a new tab without passing on our page as referrer authority.
     */
    private static function decorate(string $html): string
    {
        $used = [];

        $html = preg_replace_callback('/<h([23])>(.*?)<\/h\1>/su', function ($m) use (&$used) {
            $base = Str::slug(strip_tags($m[2])) ?: 'section';
            $id = $base;
            $n = 2;
            while (isset($used[$id])) {
                $id = $base . '-' . $n++;
            }
            $used[$id] = true;

            return '<h' . $m[1] . ' id="' . e($id) . '">' . $m[2] . '</h' . $m[1] . '>';
        }, $html) ?? $html;

        $host = parse_url(config('app.url'), PHP_URL_HOST);

        return preg_replace_callback('/<a href="([^"]+)"/u', function ($m) use ($host) {
            $href = $m[1];
            $linkHost = parse_url(html_entity_decode($href), PHP_URL_HOST);
            $external = $linkHost && $host && ! str_ends_with($linkHost, $host);

            return $external
                ? '<a href="' . $href . '" target="_blank" rel="noopener nofollow"'
                : '<a href="' . $href . '"';
        }, $html) ?? $html;
    }
}

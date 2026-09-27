<?php

namespace App\Services\AiChat;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;
use DOMXPath;

/**
 * What a page says, read out of its HTML: the title, the meta description, the
 * headings and the readable text of the main content, capped.
 *
 * Used by SiteIndex on the site's own rendered pages. Menus, footers, scripts,
 * forms and anything hidden are dropped, so what is left is what a visitor
 * reads on that page and not the chrome every page repeats.
 */
class PageText
{
    public const TEXT_LIMIT = 2500;

    /** Never content: code, media, form controls, and the chat itself. */
    private const DROP = '//script|//style|//noscript|//template|//svg|//iframe|//canvas|//video|//audio'
        . '|//select|//input|//textarea|//*[@aria-hidden="true"]|//*[@hidden]|//*[@data-ai-ignore]'
        . '|//*[@id="ai-chat-widget"]|//*[@id="xu-chat"]|//*[@id="xu-guide"]';

    /** Elements that start a new line of text. */
    private const BLOCKS = [
        'address', 'article', 'aside', 'blockquote', 'dd', 'details', 'div', 'dl', 'dt', 'figcaption', 'figure',
        'footer', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'header', 'hr', 'li', 'main', 'nav', 'ol', 'p', 'pre',
        'section', 'summary', 'table', 'tbody', 'td', 'th', 'thead', 'tr', 'ul', 'button', 'label',
    ];

    /**
     * @return array{title: string, description: string, h1: string, headings: array<int, string>, text: string}
     */
    public static function fromHtml(string $html, int $limit = self::TEXT_LIMIT): array
    {
        $empty = ['title' => '', 'description' => '', 'h1' => '', 'headings' => [], 'text' => ''];

        if (trim($html) === '') {
            return $empty;
        }

        $dom = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        // The XML declaration is how libxml is told the page is UTF-8; without
        // it every Thai character comes out as mojibake.
        $loaded = $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_COMPACT);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $loaded) {
            return $empty;
        }

        $xpath = new DOMXPath($dom);
        // Decoded once more: a few pages escape their title twice ("สั่งงาน &amp; รับใบเสนอราคา").
        $title = self::clean(html_entity_decode((string) $xpath->evaluate('string(//title)'), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $description = self::clean((string) $xpath->evaluate('string(//meta[@name="description"]/@content)'));

        foreach ($xpath->query(self::DROP) ?: [] as $node) {
            $node->parentNode?->removeChild($node);
        }

        // The site prints labels in Thai and English side by side (the bi component),
        // with nothing between them but CSS: "10 นาทีYour own server". Give them a slash.
        foreach ($xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " bi-en ")]') ?: [] as $node) {
            $node->parentNode?->insertBefore($dom->createTextNode(' / '), $node);
        }

        $root = self::first($xpath, '//main') ?? self::first($xpath, '//body');

        if ($root === null) {
            return ['title' => $title, 'description' => $description] + $empty;
        }

        $headings = [];
        foreach ($xpath->query('.//h1|.//h2|.//h3', $root) ?: [] as $heading) {
            $text = self::clean($heading->textContent, 150);
            if ($text !== '' && ! in_array($text, $headings, true)) {
                $headings[] = $text;
            }
            if (count($headings) >= 30) {
                break;
            }
        }

        $h1 = self::clean((string) $xpath->evaluate('string(.//h1)', $root), 200);

        // The site's menu and footer sit inside <main> on some layouts, and
        // repeat on every page. A page's own <header> (its hero) stays.
        $chrome = $root->nodeName === 'main' ? './/nav|.//footer' : './/header|.//nav|.//footer';
        foreach ($xpath->query($chrome, $root) ?: [] as $node) {
            $node->parentNode?->removeChild($node);
        }

        return [
            'title' => $title,
            'description' => $description,
            'h1' => $h1,
            'headings' => $headings,
            'text' => self::cap(self::lines($root), $limit),
        ];
    }

    private static function first(DOMXPath $xpath, string $expression): ?DOMElement
    {
        $nodes = $xpath->query($expression);
        $node = $nodes !== false && $nodes->length > 0 ? $nodes->item(0) : null;

        return $node instanceof DOMElement ? $node : null;
    }

    /** Collapse whitespace (NBSP and zero-width ones included) and cap the length. */
    public static function clean(string $text, int $max = 0): string
    {
        $text = trim(preg_replace('/[\s\x{00A0}\x{200B}\x{FEFF}]+/u', ' ', $text) ?? '');

        return $max > 0 && mb_strlen($text) > $max ? rtrim(mb_substr($text, 0, $max - 1)) . '…' : $text;
    }

    /** @return array<int, string> the element's text, one line per block, each line once */
    private static function lines(DOMNode $root): array
    {
        $lines = [];
        $buffer = '';

        $flush = function () use (&$lines, &$buffer) {
            $line = self::clean($buffer);
            $buffer = '';
            // A line of icons or punctuation says nothing.
            if ($line !== '' && preg_match('/[\p{L}\p{N}]/u', $line)) {
                $lines[$line] = true;
            }
        };

        $walk = function (DOMNode $node) use (&$walk, &$buffer, $flush) {
            foreach ($node->childNodes as $child) {
                if ($child instanceof DOMText) {
                    $buffer .= $child->nodeValue;

                    continue;
                }

                if (! $child instanceof DOMElement) {
                    continue;
                }

                $name = strtolower($child->nodeName);

                if ($name === 'br') {
                    $flush();

                    continue;
                }

                $block = in_array($name, self::BLOCKS, true);
                if ($block) {
                    $flush();
                }

                $walk($child);

                if ($block) {
                    $flush();
                }
            }
        };

        $walk($root);
        $flush();

        // A line that is only a number ("150") became an integer key.
        return array_map('strval', array_keys($lines));
    }

    /** Stored page text cut to a length on a line boundary, so no answer quotes half a word. */
    public static function excerpt(string $text, int $limit): string
    {
        return mb_strlen($text) <= $limit ? $text : rtrim(self::cap(explode("\n", $text), $limit)) . "\n…";
    }

    /** @param  array<int, string>  $lines */
    private static function cap(array $lines, int $limit): string
    {
        $out = '';

        foreach ($lines as $line) {
            $next = $out === '' ? $line : $out . "\n" . $line;

            if (mb_strlen($next) > $limit) {
                // One long paragraph must not leave the page with no text at all.
                return $out === '' ? self::clean($line, $limit) : $out;
            }

            $out = $next;
        }

        return $out;
    }
}

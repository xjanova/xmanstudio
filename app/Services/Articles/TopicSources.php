<?php

namespace App\Services\Articles;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Where article ideas come from: what Thai people are searching for right now.
 *
 *  - Google Trends, Thailand, daily trending searches (RSS). Mostly sport and celebrities;
 *    the topic picker keeps only what an IT company can honestly teach about.
 *  - Google News, Thailand, Technology section (RSS): the tech stories Thai readers are
 *    getting today. Used as a signal of interest, never copied — the writer explains the
 *    subject behind the headline in its own words.
 *
 * Both are public feeds without an API key. A feed that fails returns nothing: the writer
 * still has the catalogue to write from.
 */
class TopicSources
{
    public const TRENDS_URL = 'https://trends.google.com/trending/rss?geo=TH';

    public const TECH_NEWS_URL = 'https://news.google.com/rss/headlines/section/topic/TECHNOLOGY?hl=th&gl=TH&ceid=TH:th';

    /**
     * @return array<int, array{topic:string, source:string, context:string}>
     */
    public function all(int $newsLimit = 40): array
    {
        return array_merge($this->trends(), $this->techNews($newsLimit));
    }

    /** @return array<int, array{topic:string, source:string, context:string}> */
    public function trends(): array
    {
        $xml = $this->fetch(self::TRENDS_URL);
        if (! $xml) {
            return [];
        }

        $out = [];
        foreach ($xml->channel->item ?? [] as $item) {
            $ht = $item->children('ht', true);
            $news = [];
            foreach ($ht->news_item ?? [] as $n) {
                $news[] = trim((string) $n->news_item_title);
            }

            $out[] = [
                'topic' => trim((string) $item->title),
                'source' => 'trend',
                'context' => trim('ค้นหา ' . (string) $ht->approx_traffic . ' ครั้ง · ' . implode(' | ', array_slice(array_filter($news), 0, 2))),
            ];
        }

        return $out;
    }

    /** @return array<int, array{topic:string, source:string, context:string}> */
    public function techNews(int $limit = 40): array
    {
        $xml = $this->fetch(self::TECH_NEWS_URL);
        if (! $xml) {
            return [];
        }

        $out = [];
        foreach ($xml->channel->item ?? [] as $item) {
            // "Headline - Publisher": keep the headline, the publisher is not the topic.
            $title = preg_replace('/\s+-\s+[^-]+$/u', '', trim((string) $item->title)) ?? '';
            if ($title === '') {
                continue;
            }

            $out[] = ['topic' => $title, 'source' => 'news', 'context' => 'ข่าวเทคโนโลยีที่คนไทยกำลังอ่านวันนี้'];

            if (count($out) >= $limit) {
                break;
            }
        }

        return $out;
    }

    private function fetch(string $url): ?\SimpleXMLElement
    {
        try {
            $response = Http::withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; XMANStudio-Articles/1.0; +https://xman4289.com)'])
                ->timeout(20)
                ->get($url);

            if (! $response->successful()) {
                Log::warning('Articles: topic feed answered HTTP ' . $response->status(), ['url' => $url]);

                return null;
            }

            $previous = libxml_use_internal_errors(true);
            $xml = simplexml_load_string($response->body(), \SimpleXMLElement::class, LIBXML_NOCDATA | LIBXML_NONET);
            libxml_use_internal_errors($previous);

            return $xml ?: null;
        } catch (\Throwable $e) {
            Log::warning('Articles: topic feed failed: ' . $e->getMessage(), ['url' => $url]);

            return null;
        }
    }
}

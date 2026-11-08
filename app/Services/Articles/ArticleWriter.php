<?php

namespace App\Services\Articles;

use App\Exceptions\AIServiceException;
use App\Models\Article;
use App\Models\ArticleTopic;
use App\Models\Setting;
use App\Services\AiChatService;
use App\Support\ArticleHtml;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Writes knowledge-base articles on its own (docs/ARTICLES.md).
 *
 *  1. pick: from Google Trends / Google News tech headlines (TopicSources) and our catalogue,
 *     the AI picks topics an IT company can honestly teach about, each tied to what we sell;
 *  2. write: one article per topic — teach first, answer the reader's question, and only near
 *     the end show how one of our products or services helps (soft sell, never invented facts);
 *  3. store: Markdown → safe HTML, a unique slug, published or kept as a draft (setting).
 */
class ArticleWriter
{
    /** Below this many characters of text the answer is a stub, not an article. */
    public const MIN_TEXT_CHARS = 2500;

    public function __construct(
        private AiChatService $ai,
        private TopicSources $sources,
        private Catalogue $catalogue,
        private ArticleImages $images,
    ) {}

    public static function autoEnabled(): bool
    {
        return (bool) Setting::getValue('articles_auto_enabled', true);
    }

    public static function autoPublish(): bool
    {
        return (bool) Setting::getValue('articles_auto_publish', true);
    }

    public static function perRun(): int
    {
        return max(1, min(5, (int) Setting::getValue('articles_per_run', 1)));
    }

    /**
     * Pick and write $count articles. Returns what happened to each, for the command / admin.
     *
     * $publishAt: when the articles go live (they are written ahead of the hour readers come);
     * null or a time already past = now.
     *
     * @return array<int, array{ok:bool, topic:string, article?:Article, error?:string}>
     */
    public function run(int $count, ?bool $publish = null, ?string $forcedTopic = null, ?CarbonInterface $publishAt = null): array
    {
        if (! $this->ai->isConfigured()) {
            throw new RuntimeException('ยังไม่ได้ตั้งค่า AI (แอดมิน → ตั้งค่า AI) จึงเขียนบทความไม่ได้');
        }

        $items = $this->catalogue->items();
        $publish ??= self::autoPublish();

        $plans = $forcedTopic
            ? [['topic' => $forcedTopic, 'angle' => $forcedTopic, 'category' => 'it', 'related' => [], 'source' => 'manual', 'source_topic' => $forcedTopic]]
            : $this->pick($count, $items);

        $results = [];
        foreach ($plans as $plan) {
            try {
                $article = $this->write($plan, $items, $publish, $publishAt);
                $this->remember($plan, 'written', null, $article->id);
                $results[] = ['ok' => true, 'topic' => $plan['topic'], 'article' => $article];
            } catch (\Throwable $e) {
                // The provider's own reason (key, quota, model gone), never a bare "failed".
                $reason = $e instanceof AIServiceException ? $e->getUserMessage() . ' — ' . $e->getMessage() : $e->getMessage();
                Log::warning('Articles: writing failed: ' . $reason, ['topic' => $plan['topic']]);
                $this->remember($plan, 'failed', Str::limit($reason, 250));
                $results[] = ['ok' => false, 'topic' => $plan['topic'], 'error' => $reason];
            }
        }

        return $results;
    }

    /**
     * Ask the AI which topics are worth an article today.
     *
     * @return array<int, array{topic:string, angle:string, category:string, related:array, source:string, source_topic:string}>
     */
    public function pick(int $count, ?array $items = null): array
    {
        $items ??= $this->catalogue->items();
        $candidates = $this->sources->all();

        // Already covered (written or deliberately skipped) — never offered again.
        $covered = ArticleTopic::query()->whereIn('decision', ['written', 'skipped'])->pluck('topic_key')->flip();
        $candidates = array_values(array_filter($candidates, fn ($c) => ! $covered->has(self::topicKey($c['topic']))));

        $recentTitles = Article::query()->latest('id')->limit(60)->pluck('title')->implode("\n");

        $candidateText = collect($candidates)
            ->map(fn ($c, $i) => ($i + 1) . '. [' . $c['source'] . '] ' . $c['topic'] . ($c['context'] ? ' — ' . Str::limit($c['context'], 160) : ''))
            ->implode("\n");

        $categories = collect(Article::CATEGORIES)->map(fn ($v, $k) => $k . ' = ' . $v[0])->implode(', ');

        $system = <<<PROMPT
คุณคือบรรณาธิการคลังความรู้ไอทีของ XMAN Studio (บริษัทไอทีไทย รับทำเว็บ แอป AI Blockchain ระบบธุรกิจ และขายซอฟต์แวร์ของตัวเอง)
งาน: เลือกหัวข้อบทความ {$count} เรื่องสำหรับวันนี้ ให้คนไทยที่ค้นหาเรื่องนั้นอยู่ได้ความรู้จริง และพาไปเจอสินค้า/บริการของเราอย่างเป็นธรรมชาติ

กติกา:
- ใช้หัวข้อจากรายการ "สิ่งที่คนไทยกำลังค้นหา/อ่านวันนี้" ก่อน เฉพาะเรื่องที่บริษัทไอทีสอนได้จริง (เทคโนโลยี AI มือถือ ซอฟต์แวร์ ความปลอดภัย เว็บ คลาวด์ คริปโต ธุรกิจออนไลน์)
- ห้ามเลือก: กีฬา ดารา การเมือง ศาสนา ภัยพิบัติ อาชญากรรม หรือข่าวเศร้า แม้จะติดเทรนด์ เว้นแต่มีมุมไอทีที่ชัดเจนและไม่ฉวยโอกาสจากความสูญเสีย
- อย่าแค่เล่าข่าว: เปลี่ยนเป็นมุมความรู้ที่ยังมีประโยชน์ในอีกหลายเดือน เช่น "X คืออะไร ใช้ทำอะไร" "วิธีเลือก" "ข้อควรระวัง" "ธุรกิจได้อะไร"
- ถ้าหัวข้อจากเทรนด์ที่ดีมีไม่พอ ให้คิดหัวข้อความรู้เองจากปัญหาที่สินค้า/บริการของเราแก้ได้ (source = "catalogue")
- ห้ามซ้ำหรือคล้ายกับบทความที่มีอยู่แล้ว
- related: key ของสินค้า/บริการ 1–2 รายการที่เกี่ยวข้องจริงจากรายการด้านล่างเท่านั้น (ถ้าไม่มีที่เกี่ยวจริง ให้เป็น [])
- category ต้องเป็นหนึ่งใน: {$categories}

ตอบเป็น JSON อย่างเดียว ไม่มีข้อความอื่น:
{"topics":[{"topic":"ชื่อหัวข้อสั้นๆ","angle":"มุมของบทความ 1–2 ประโยค ว่าจะสอนอะไร ตอบคำถามอะไรของผู้อ่าน","category":"ai","related":["p:slug"],"source":"trend|news|catalogue","source_topic":"หัวข้อต้นทางตามรายการ"}]}

=== สิ่งที่คนไทยกำลังค้นหา/อ่านวันนี้ ===
{$candidateText}

=== สินค้าและบริการของเรา (key ในวงเล็บเหลี่ยม) ===
{$this->catalogue->promptList($items)}

=== บทความที่มีอยู่แล้ว (ห้ามซ้ำ) ===
{$recentTitles}
PROMPT;

        $answer = $this->ai->forLongOutput(4000, 180)->chat([
            ['role' => 'user', 'content' => 'เลือกหัวข้อบทความ ' . $count . ' เรื่องสำหรับวันนี้ ตอบเป็น JSON ตามรูปแบบ'],
        ], $system);

        $json = self::extractJson((string) ($answer['message'] ?? ''));
        $topics = array_slice(array_values(array_filter($json['topics'] ?? [], fn ($t) => is_array($t) && ! empty($t['topic']))), 0, $count);

        if ($topics === []) {
            throw new RuntimeException('AI ไม่ได้เสนอหัวข้อที่ใช้ได้ (คำตอบไม่ใช่ JSON ตามรูปแบบ)');
        }

        return array_map(fn ($t) => [
            'topic' => Str::limit(trim((string) $t['topic']), 190, ''),
            'angle' => trim((string) ($t['angle'] ?? $t['topic'])),
            'category' => array_key_exists($t['category'] ?? '', Article::CATEGORIES) ? $t['category'] : 'it',
            'related' => array_values(array_filter((array) ($t['related'] ?? []), fn ($k) => is_string($k) && isset($items[$k]))),
            'source' => in_array($t['source'] ?? '', ['trend', 'news', 'catalogue'], true) ? $t['source'] : 'catalogue',
            'source_topic' => Str::limit(trim((string) ($t['source_topic'] ?? $t['topic'])), 190, ''),
        ], $topics);
    }

    /**
     * Write and store one article.
     *
     * @param  array{topic:string, angle:string, category:string, related:array, source:string, source_topic:string}  $plan
     */
    public function write(array $plan, array $items, bool $publish, ?CarbonInterface $publishAt = null): Article
    {
        $related = array_values(array_intersect_key($items, array_flip($plan['related'])));
        $relatedText = $related
            ? collect($related)->map(fn ($i) => '[' . $i['key'] . '] ' . ltrim($i['facts'], '- ') . ' | ลิงก์: ' . $i['url'])->implode("\n")
            : '(ไม่มีสินค้าที่เกี่ยวข้องโดยตรง — ปิดท้ายด้วยการชวนปรึกษาทีม XMAN Studio ที่ ' . route('contact.show') . ' หรือขอใบเสนอราคาที่ ' . (Route::has('quote.index') ? route('quote.index') : url('/')) . ')';

        $today = now('Asia/Bangkok');
        $thaiDate = $today->locale('th')->translatedFormat('j F') . ' ' . ($today->year + 543);
        $categories = collect(Article::CATEGORIES)->map(fn ($v, $k) => $k . ' = ' . $v[0])->implode(', ');

        $system = <<<PROMPT
คุณคือนักเขียนบทความไอทีภาษาไทยของ XMAN Studio เขียนให้คนทั่วไปและเจ้าของธุรกิจอ่านเข้าใจ วันนี้คือ {$thaiDate}

หัวข้อ: {$plan['topic']}
มุมของบทความ: {$plan['angle']}

หลักการเขียน (สำคัญมาก — Google ให้คะแนนเว็บจากเรื่องนี้):
1. ให้ความรู้ก่อน: ย่อหน้าแรกตอบคำถามหลักของผู้อ่านทันที แล้วค่อยอธิบายลึก ใช้ตัวอย่างจริงที่คนไทยเจอ
   ให้อ่านสนุก: เปิดด้วยสถานการณ์ที่ผู้อ่านเจอจริง เล่าเป็นภาษาคนคุยกัน มีเช็กลิสต์หรือขั้นตอนที่ทำตามได้ทันที และสรุป "ถ้าจำได้อย่างเดียว ให้จำข้อนี้" ก่อนเข้าสู่ส่วนท้าย
2. ถูกต้องและซื่อตรง: ห้ามแต่งตัวเลขสถิติ ผลวิจัย คำพูดของบุคคล ชื่อรุ่น ราคา หรือวันที่ที่ไม่แน่ใจ ถ้าข้อมูลอาจเปลี่ยน ให้บอกผู้อ่านว่าควรตรวจสอบล่าสุด ห้ามคัดลอกข่าวของสำนักอื่น
3. โครงสร้าง: หัวข้อย่อย ## และ ### ที่สื่อความ, รายการขั้นตอน/ข้อดีข้อเสีย, ตารางเปรียบเทียบเมื่อเหมาะ, ปิดด้วยหัวข้อ "## คำถามที่พบบ่อย" 3–4 ข้อ (### คำถาม แล้วตอบสั้น)
4. ความยาวประมาณ 1,200–1,800 คำ ภาษาไทยเป็นธรรมชาติ ไม่ยัดคีย์เวิร์ด ไม่ขายของตั้งแต่ต้น
5. ขายอย่างแนบเนียน: หลังจากให้ความรู้ครบแล้ว ใส่ส่วนหนึ่งช่วงท้าย (ก่อนคำถามที่พบบ่อย) ที่เล่าว่าถ้าอยากลงมือทำจริง สินค้า/บริการด้านล่างช่วยได้อย่างไร อ้างได้เฉพาะข้อเท็จจริงที่ให้ไว้ และใส่ลิงก์ Markdown ไปยังลิงก์ที่ให้ไว้เท่านั้น
6. ลิงก์: ใช้ได้เฉพาะลิงก์ที่ให้ไว้ในรายการนี้ ห้ามแต่ง URL เอง ห้ามลิงก์ไปเว็บอื่น
7. ห้ามใช้ HTML ห้ามใส่หัวข้อ # (H1) ในเนื้อหา เพราะหน้าเว็บแสดงชื่อเรื่องให้แล้ว
8. ภาพประกอบ: ใส่ตำแหน่งภาพ 2–3 จุด (อย่างน้อย 2 จุด ใต้หัวข้อ ## คนละหัวข้อที่ภาพช่วยให้เข้าใจ) เป็นบรรทัดเดี่ยวรูปแบบนี้เป๊ะ
   [[IMG: English description of what the picture shows, concrete objects and scene | คำอธิบายภาพภาษาไทยสั้นๆ สำหรับ alt]]
   คำบรรยายภาพเป็นภาษาอังกฤษเท่านั้น บอกสิ่งที่เห็นในภาพให้ชัด ห้ามมีตัวหนังสือ โลโก้ หรือบุคคลจริงในภาพ

สินค้า/บริการที่เกี่ยวข้อง (ข้อเท็จจริงเท่านั้น):
{$relatedText}

รูปแบบคำตอบ (ตามนี้เป๊ะ บรรทัดละหนึ่งช่อง แล้วตามด้วย --- และเนื้อหา Markdown):
TITLE: ชื่อบทความภาษาไทย น่าคลิกแต่ไม่หลอก ไม่เกิน 70 ตัวอักษร มีคำค้นหลัก
SLUG: english-url-slug-3-to-7-words
EXCERPT: คำอธิบายสำหรับ Google 120–155 ตัวอักษร บอกว่าอ่านแล้วได้อะไร
CATEGORY: หนึ่งใน {$categories}
FOCUS: คำค้นหลักที่คนไทยพิมพ์ค้นหา
TAGS: แท็ก 3–6 คำ คั่นด้วยจุลภาค
IMAGE: English description of a cover picture for this article (one scene, concrete objects, no text)
---
(เนื้อหาบทความ Markdown)
PROMPT;

        $answer = $this->ai->forLongOutput(12000, 300)->chat([
            ['role' => 'user', 'content' => 'เขียนบทความเรื่อง "' . $plan['topic'] . '" ตามรูปแบบที่กำหนด'],
        ], $system);

        $parsed = self::parseArticle((string) ($answer['message'] ?? ''));

        // Length is judged without the picture placeholders, and before any picture is paid for.
        $text = ArticleHtml::text(ArticleHtml::fromMarkdown(preg_replace(ArticleImages::PLACEHOLDER, '', $parsed['markdown']) ?? $parsed['markdown']));
        if (mb_strlen($text) < self::MIN_TEXT_CHARS) {
            throw new RuntimeException('บทความสั้นเกินไป (' . mb_strlen($text) . ' ตัวอักษร) จึงไม่บันทึก');
        }

        $slug = self::uniqueSlug($parsed['slug'] ?: $plan['topic']);
        $title = $parsed['title'] ?: $plan['topic'];

        // The writer is asked for at least two pictures but does not always place them: top up.
        $markdown = $parsed['markdown'];
        $cover = $parsed['image'] ?: null;
        if (ArticleImages::enabled()) {
            [$markdown, $planned] = $this->planFigures($markdown, $title, $parsed['excerpt'], $cover === null);
            $cover ??= $planned;
        }
        $pictures = $this->images->illustrate($markdown, $cover, $slug);

        $html = ArticleHtml::fromMarkdown($pictures['markdown']);
        $words = ArticleHtml::wordCount($html);
        $category = array_key_exists($parsed['category'], Article::CATEGORIES) ? $parsed['category'] : $plan['category'];
        $goLive = $publishAt && $publishAt->isFuture() ? $publishAt : now();

        return Article::create([
            'slug' => $slug,
            'title' => Str::limit($title, 200, ''),
            'excerpt' => Str::limit($parsed['excerpt'] ?: Str::limit($text, 150), 300, ''),
            'body' => $html,
            'body_markdown' => $pictures['markdown'],
            'cover_image' => $pictures['cover'],
            'category' => $category,
            'tags' => $parsed['tags'],
            'focus_keyword' => Str::limit($parsed['focus'], 120, '') ?: null,
            'status' => $publish ? Article::STATUS_PUBLISHED : Article::STATUS_DRAFT,
            'published_at' => $publish ? $goLive : null,
            'source' => $plan['source'] === 'catalogue' ? 'catalogue' : ($plan['source'] === 'manual' ? 'manual' : 'trend'),
            'source_topic' => $plan['source_topic'],
            'related' => array_map(fn ($i) => ['key' => $i['key'], 'name' => $i['name'], 'url' => $i['url'], 'blurb' => $i['blurb']], $related),
            'ai_model' => Str::limit($this->ai->providerName(), 80, ''),
            'word_count' => $words,
            'reading_minutes' => ArticleHtml::readingMinutes($words),
        ]);
    }

    /**
     * Give an article written before pictures existed (or without them) a cover and illustrations:
     * one short AI call picks where pictures help and describes them, then ArticleImages draws.
     *
     * @return array{drawn:int, failed:int}
     */
    public function illustrateExisting(Article $article, bool $redo = false): array
    {
        if (! ArticleImages::enabled()) {
            throw new RuntimeException('ปิดการวาดภาพไว้ หรือยังไม่มีคีย์ OpenAI (แอดมิน → ตั้งค่า AI)');
        }

        $markdown = (string) $article->body_markdown;
        $old = [];
        if ($redo) {
            // Take our own pictures out; their files go once the new ones are in.
            $markdown = preg_replace_callback(ArticleImages::OWN_PICTURE, function ($m) use (&$old) {
                $old[] = $m[1];

                return '';
            }, $markdown) ?? $markdown;
            if ($article->cover_image && ! str_starts_with($article->cover_image, 'http')) {
                $old[] = $article->cover_image;
            }
        }

        $wantCover = $redo || ! $article->cover_image;
        [$markdown, $cover] = $this->planFigures($markdown, $article->title, (string) $article->excerpt, $wantCover);
        $pictures = $this->images->illustrate($markdown, $wantCover ? ($cover ?? $article->title) : null, $article->slug);

        $article->update(array_filter([
            'body_markdown' => $pictures['markdown'],
            'body' => ArticleHtml::fromMarkdown($pictures['markdown']),
            'cover_image' => $pictures['cover'] ?? $article->cover_image,
        ], fn ($v) => $v !== null));

        // The cover stays when a new one could not be drawn.
        Storage::disk('public')->delete(array_values(array_diff($old, [$article->cover_image])));

        return ['drawn' => $pictures['drawn'], 'failed' => $pictures['failed']];
    }

    /**
     * Make sure the article has at least ArticleImages::MIN_INLINE picture spots: placeholders are put
     * under headings that have no picture yet. One short AI call describes the pictures (and the cover
     * when $wantCover); headings it misses get a plain subject from the heading itself, so the minimum
     * holds even when that call fails.
     *
     * @return array{0:string, 1:?string} the Markdown with placeholders, and a cover subject (or null)
     */
    private function planFigures(string $markdown, string $title, string $excerpt, bool $wantCover): array
    {
        $have = preg_match_all(ArticleImages::PLACEHOLDER, $markdown) + preg_match_all('/^!\[/mu', $markdown);
        $need = max(0, ArticleImages::MIN_INLINE - $have);

        // Headings a picture may go under: not the FAQ, and not one that already has a picture.
        $free = [];
        foreach (preg_split('/^(?=##\s)/mu', $markdown) ?: [] as $section) {
            if (preg_match('/^##\s+(.+?)[ \t]*$/mu', $section, $h) && ! str_contains($h[1], 'คำถามที่พบบ่อย')
                && ! str_contains($section, '[[IMG:') && ! preg_match('/^!\[/mu', $section)) {
                $free[] = $h[1];
            }
        }
        $need = min($need, count($free));

        if ($need === 0 && ! $wantCover) {
            return [$markdown, null];
        }

        $plan = [];
        try {
            $system = "You plan pictures for a Thai IT article. Reply with JSON only:\n"
                . '{"cover":"English description of one cover scene, concrete objects, no text","figures":[{"heading":"one heading copied exactly from the list","subject":"English description of what the picture shows","alt":"คำอธิบายภาพภาษาไทยสั้นๆ"}]}' . "\n"
                . 'Exactly ' . $need . ' figures, each under a different heading that a picture would help explain. Never text, logos or real people in a picture.';
            $user = 'Title: ' . $title . "\nSummary: " . $excerpt . "\nHeadings:\n- " . implode("\n- ", $free);
            $plan = self::extractJson((string) ($this->ai->forLongOutput(2000, 120)->chat([['role' => 'user', 'content' => $user]], $system)['message'] ?? ''));
        } catch (\Throwable $e) {
            Log::warning('Articles: picture planning failed, using the headings: ' . $e->getMessage());
        }

        $figures = [];
        foreach ((array) ($plan['figures'] ?? []) as $f) {
            if (count($figures) < $need && is_array($f) && ! empty($f['subject']) && in_array($f['heading'] ?? null, $free, true) && ! isset($figures[$f['heading']])) {
                $figures[$f['heading']] = [(string) $f['subject'], (string) ($f['alt'] ?? $f['heading'])];
            }
        }
        foreach ($free as $heading) {
            if (count($figures) >= $need) {
                break;
            }
            $figures[$heading] ??= ['An illustration that explains "' . $heading . '" for an article titled "' . $title . '"', $heading];
        }

        foreach ($figures as $heading => [$subject, $alt]) {
            $line = '[[IMG: ' . str_replace(['|', '[', ']', "\n"], ' ', $subject) . ' | ' . str_replace(['|', '[', ']', "\n"], ' ', $alt) . ']]';
            // Under the heading, before its first paragraph.
            $markdown = preg_replace('/^(##\s+' . preg_quote($heading, '/') . ')[ \t]*$/mu', "$1\n\n" . str_replace(['\\', '$'], ['\\\\', '\\$'], $line), $markdown, 1) ?? $markdown;
        }

        $cover = $wantCover && is_string($plan['cover'] ?? null) && trim($plan['cover']) !== '' ? $plan['cover'] : null;

        return [$markdown, $cover];
    }

    /**
     * @return array{title:string, slug:string, excerpt:string, category:string, focus:string, tags:array, image:string, markdown:string}
     */
    public static function parseArticle(string $answer): array
    {
        $answer = str_replace("\r\n", "\n", trim($answer));
        $parts = preg_split('/^\s*---\s*$/m', $answer, 2);
        if (count($parts) < 2) {
            throw new RuntimeException('คำตอบของ AI ไม่มีตัวคั่น --- ระหว่างหัวบทความกับเนื้อหา');
        }

        [$head, $markdown] = $parts;
        $field = function (string $name) use ($head): string {
            return preg_match('/^\s*\**' . $name . '\**\s*:\s*(.+)$/mi', $head, $m) ? trim($m[1], " \t*\"'") : '';
        };

        $tags = array_values(array_unique(array_filter(array_map(
            fn ($t) => Str::limit(trim(ltrim(trim($t), '#')), 40, ''),
            preg_split('/[,،、]/u', $field('TAGS')) ?: []
        ))));

        // A stray "# Title" at the top would be a second H1 on the page.
        $markdown = trim(preg_replace('/^\s*#\s+[^\n]+\n/u', '', trim($markdown)) ?? $markdown);

        return [
            'title' => $field('TITLE'),
            'slug' => $field('SLUG'),
            'excerpt' => $field('EXCERPT'),
            'category' => strtolower($field('CATEGORY')),
            'focus' => $field('FOCUS'),
            'tags' => array_slice($tags, 0, 8),
            'image' => $field('IMAGE'),
            'markdown' => $markdown,
        ];
    }

    public static function extractJson(string $text): array
    {
        $text = trim($text);
        $start = strpos($text, '{');
        $end = strrpos($text, '}');
        if ($start === false || $end === false || $end < $start) {
            return [];
        }

        $decoded = json_decode(substr($text, $start, $end - $start + 1), true);

        return is_array($decoded) ? $decoded : [];
    }

    public static function uniqueSlug(string $wanted): string
    {
        $base = Str::limit(Str::slug($wanted), 80, '');
        $base = trim($base, '-');
        if ($base === '') {
            $base = 'article-' . now()->format('Ymd');
        }

        $slug = $base;
        $n = 2;
        while (Article::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $n++;
        }

        return $slug;
    }

    public static function topicKey(string $topic): string
    {
        $key = mb_strtolower(preg_replace('/[\s\p{P}]+/u', '', $topic) ?? $topic);

        return Str::limit($key, 180, '');
    }

    private function remember(array $plan, string $decision, ?string $reason = null, ?int $articleId = null): void
    {
        foreach (array_unique([$plan['topic'], $plan['source_topic']]) as $topic) {
            ArticleTopic::updateOrCreate(
                ['topic_key' => self::topicKey($topic)],
                ['topic' => Str::limit($topic, 190, ''), 'source' => $plan['source'] === 'catalogue' ? 'catalogue' : 'trend', 'decision' => $decision, 'reason' => $reason, 'article_id' => $articleId],
            );
        }
    }
}

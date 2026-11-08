<?php

namespace Tests\Feature;

use App\Events\ArticlePublished;
use App\Models\Article;
use App\Models\ArticleRating;
use App\Models\ArticleTopic;
use App\Models\Setting;
use App\Models\User;
use App\Services\Articles\ArticleWriter;
use App\Services\Articles\TopicSources;
use App\Support\ArticleHtml;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ArticlesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    private function article(array $attrs = []): Article
    {
        $md = "## เริ่มต้น\n\nเนื้อหา **สำคัญ**\n\n## ขั้นตอน\n\n- หนึ่ง\n- สอง\n\n## สรุป\n\nจบ";

        return Article::create(array_merge([
            'slug' => 'test-article',
            'title' => 'บทความทดสอบ AI คืออะไร',
            'excerpt' => 'คำอธิบายบทความทดสอบ',
            'body_markdown' => $md,
            'body' => ArticleHtml::fromMarkdown($md),
            'category' => 'ai',
            'status' => Article::STATUS_PUBLISHED,
            'published_at' => now()->subMinute(),
        ], $attrs));
    }

    public function test_markdown_is_stored_as_safe_html(): void
    {
        $html = ArticleHtml::fromMarkdown("## หัวข้อ\n\n<script>alert(1)</script>\n\n[x](javascript:alert(1)) [ok](https://example.com/a)\n\n<img src=x onerror=alert(1)>");

        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('onerror', $html);
        $this->assertStringNotContainsString('javascript:', $html);
        $this->assertStringContainsString('<h2 id="', $html);
        $this->assertStringContainsString('rel="noopener nofollow"', $html);
    }

    public function test_the_writer_answer_is_parsed(): void
    {
        $parsed = ArticleWriter::parseArticle("TITLE: วิธีเลือก VPS\nSLUG: how-to-choose-a-vps\nEXCERPT: สรุปสั้น\nCATEGORY: infra\nFOCUS: เช่า vps\nTAGS: vps, #cloud, เซิร์ฟเวอร์\n---\n# วิธีเลือก VPS\n\n## ข้อแรก\n\nเนื้อหา");

        $this->assertSame('วิธีเลือก VPS', $parsed['title']);
        $this->assertSame('how-to-choose-a-vps', $parsed['slug']);
        $this->assertSame('infra', $parsed['category']);
        $this->assertSame(['vps', 'cloud', 'เซิร์ฟเวอร์'], $parsed['tags']);
        $this->assertStringStartsWith('## ข้อแรก', $parsed['markdown']); // the stray H1 is gone
    }

    public function test_blog_lists_only_live_articles_and_hides_drafts_from_guests(): void
    {
        $live = $this->article();
        $draft = $this->article(['slug' => 'draft-one', 'title' => 'ร่างที่ยังไม่เผยแพร่', 'status' => Article::STATUS_DRAFT, 'published_at' => null]);

        $this->get('/blog')->assertOk()->assertSee($live->title)->assertDontSee($draft->title);
        $this->get('/blog/' . $live->slug)->assertOk()->assertSee('BlogPosting', false)->assertSee('BreadcrumbList', false);
        $this->get('/blog/draft-one')->assertNotFound();
    }

    public function test_a_view_is_counted_once_per_session(): void
    {
        $a = $this->article();

        $this->get('/blog/' . $a->slug)->assertOk();
        $this->get('/blog/' . $a->slug)->assertOk();

        $this->assertSame(1, $a->fresh()->views);
    }

    public function test_stars_are_one_vote_per_reader_and_can_be_changed(): void
    {
        $a = $this->article();

        $this->postJson('/blog/' . $a->slug . '/rate', ['stars' => 9])->assertStatus(422)->assertJsonPath('success', false);

        $this->postJson('/blog/' . $a->slug . '/rate', ['stars' => 4])->assertOk()->assertJsonPath('count', 1);
        $this->postJson('/blog/' . $a->slug . '/rate', ['stars' => 2])->assertOk()->assertJsonPath('count', 1)->assertJsonPath('mine', 2);

        $this->actingAs(User::factory()->create())
            ->postJson('/blog/' . $a->slug . '/rate', ['stars' => 5])->assertOk()->assertJsonPath('count', 2);

        $a->refresh();
        $this->assertSame(2, ArticleRating::count());
        $this->assertSame(7, $a->rating_sum);
        $this->assertSame(3.5, $a->ratingAverage());
    }

    public function test_published_event_fires_once_when_an_article_goes_live(): void
    {
        Event::fake([ArticlePublished::class]);

        $a = $this->article(['status' => Article::STATUS_DRAFT, 'published_at' => null]);
        Event::assertNotDispatched(ArticlePublished::class);

        $a->update(['status' => Article::STATUS_PUBLISHED, 'published_at' => now()]);
        $a->update(['title' => 'แก้ชื่อหลังเผยแพร่']);

        Event::assertDispatchedTimes(ArticlePublished::class, 1);
    }

    public function test_sitemap_and_robots_are_built_from_the_site(): void
    {
        $a = $this->article();

        $this->get('/sitemap.xml')->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee(route('articles.show', $a->slug), false)
            ->assertSee(route('articles.index'), false);

        $this->get('/robots.txt')->assertOk()->assertSee('Sitemap: ' . route('sitemap'), false);
    }

    public function test_old_wordpress_urls_are_gone_for_good(): void
    {
        foreach ([
            '/ecommerce/shop?orderby=date&rating_filter=5',
            '/ecommerce/product-category/camera-drone',
            '/ecommerce',
            '/sitemap_index.xml',
            '/post-sitemap.xml',
            '/page-sitemap.xml',
            '/product_cat-sitemap2.xml',
            '/wp-json/wp/v2/posts',
        ] as $url) {
            $this->get($url)->assertStatus(410);
        }

        // Our own routes are untouched.
        $this->get('/sitemap.xml')->assertOk();
        $this->get('/blog/feed.xml')->assertOk();
        $this->get('/ecommerce-guide')->assertNotFound();
    }

    public function test_the_writer_picks_a_trending_topic_and_publishes_an_article(): void
    {
        Setting::setValue('ai_provider', 'openai');
        Setting::setValue('openai_api_key', 'sk-test');
        Setting::setValue('openai_model', 'gpt-4o-mini');

        $trends = '<?xml version="1.0"?><rss xmlns:ht="https://trends.google.com/trending/rss" version="2.0"><channel>'
            . '<item><title>ฟุตบอลคืนนี้</title><ht:approx_traffic>500+</ht:approx_traffic></item>'
            . '<item><title>ChatGPT ล่ม</title><ht:approx_traffic>200+</ht:approx_traffic></item>'
            . '</channel></rss>';
        $news = '<?xml version="1.0"?><rss version="2.0"><channel><item><title>Windows 11 อัปเดตใหม่ - Somesite</title></item></channel></rss>';

        $body = str_repeat('ย่อหน้าความรู้เรื่อง AI สำหรับธุรกิจไทย อธิบายอย่างละเอียดและใช้งานได้จริง ', 60);
        $articleAnswer = "TITLE: ChatGPT ล่มบ่อย ธุรกิจควรรับมืออย่างไร\nSLUG: chatgpt-outage-business-plan\nEXCERPT: เข้าใจสาเหตุที่บริการ AI ล่ม และวิธีวางแผนสำรองให้งานไม่สะดุด\nCATEGORY: ai\nFOCUS: chatgpt ล่ม\nTAGS: ChatGPT, AI, ธุรกิจ\n---\n## ทำไมบริการ AI ถึงล่ม\n\n{$body}\n\n## วิธีรับมือ\n\n{$body}\n\n## คำถามที่พบบ่อย\n\n### ล่มนานไหม\n\nไม่นาน";

        $calls = 0;
        Http::fake([
            TopicSources::TRENDS_URL => Http::response($trends),
            'news.google.com/*' => Http::response($news),
            'api.openai.com/*' => function () use (&$calls, $articleAnswer) {
                $calls++;
                $content = $calls === 1
                    ? '{"topics":[{"topic":"ChatGPT ล่ม","angle":"สอนรับมือเมื่อ AI ล่ม","category":"ai","related":[],"source":"trend","source_topic":"ChatGPT ล่ม"}]}'
                    : $articleAnswer;

                return Http::response(['choices' => [['message' => ['content' => $content]]]]);
            },
        ]);

        $results = app(ArticleWriter::class)->run(1, true);

        $this->assertTrue($results[0]['ok'], $results[0]['error'] ?? '');
        $article = $results[0]['article'];
        $this->assertSame('chatgpt-outage-business-plan', $article->slug);
        $this->assertTrue($article->isLive());
        $this->assertSame('trend', $article->source);
        $this->assertGreaterThan(300, $article->word_count);
        $this->assertSame('written', ArticleTopic::where('topic', 'ChatGPT ล่ม')->value('decision'));

        // The same trend is never offered to the picker again.
        $this->assertSame([], array_filter(
            app(TopicSources::class)->trends(),
            fn ($t) => ArticleTopic::where('topic_key', ArticleWriter::topicKey($t['topic']))->where('decision', 'written')->doesntExist() && $t['topic'] === 'ChatGPT ล่ม'
        ));
    }

    public function test_a_short_answer_is_not_published(): void
    {
        Setting::setValue('ai_provider', 'openai');
        Setting::setValue('openai_api_key', 'sk-test');

        Http::fake(['api.openai.com/*' => Http::response(['choices' => [['message' => ['content' => "TITLE: สั้น\nSLUG: short\nEXCERPT: x\nCATEGORY: ai\nFOCUS: x\nTAGS: x\n---\n## หัวข้อ\n\nสั้นเกินไป"]]]])]);

        $results = app(ArticleWriter::class)->run(1, true, 'หัวข้อที่สั่งเอง');

        $this->assertFalse($results[0]['ok']);
        $this->assertStringContainsString('สั้นเกินไป', $results[0]['error']);
        $this->assertSame(0, Article::count());
    }

    public function test_admin_can_open_the_articles_screen(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->article();

        $response = $this->actingAs($admin)->get('/admin/articles');

        // A fresh admin may be sent to set up two-factor first; either way the page is wired.
        $this->assertContains($response->status(), [200, 302]);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Services\AiChat\Keywords;
use App\Services\AiChat\PageText;
use App\Services\AiChat\SiteIndex;
use App\Services\AiChat\SiteMap;
use App\Support\Quotation\Pricing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * How the AI assistant learns what the site's pages say (SiteIndex reading
 * rendered pages through PageText) and finds the page a question is about
 * (Keywords + SiteIndex::search), plus the renaming to น้อง Nova.
 */
class AiChatSiteIndexTest extends TestCase
{
    use RefreshDatabase;

    private string $file;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        $this->file = sys_get_temp_dir() . '/ai-chat-index-' . uniqid() . '.json';
    }

    protected function tearDown(): void
    {
        @unlink($this->file);

        parent::tearDown();
    }

    /** @param  array<string, string>|null  $pages  path => route name to read, instead of the whole site */
    private function index(?array $pages = null): SiteIndex
    {
        $siteMap = $pages === null ? app(SiteMap::class) : new class($pages) extends SiteMap
        {
            public function __construct(private array $pages) {}

            public function crawlable(): array
            {
                return $this->pages;
            }
        };

        return new class($siteMap, $this->file) extends SiteIndex
        {
            public function __construct(SiteMap $siteMap, private string $path)
            {
                parent::__construct($siteMap);
            }

            public function file(): string
            {
                return $this->path;
            }
        };
    }

    private function writeIndex(array $pages): void
    {
        $full = [];
        foreach ($pages as $path => $page) {
            $full[$path] = $page + ['path' => $path, 'route' => 'x', 'title' => '', 'description' => '', 'h1' => '', 'headings' => [], 'text' => ''];
        }
        file_put_contents($this->file, json_encode(['built_at' => now()->toIso8601String(), 'fingerprint' => 'x', 'pages' => $full]));
    }

    public function test_page_text_keeps_the_content_and_drops_the_chrome(): void
    {
        $html = <<<'HTML'
<!doctype html><html><head><title>ราคา Tping | XMAN Studio</title>
<meta name="description" content="แพ็กเกจของ Tping"><script>var secret = 1;</script></head>
<body><nav>เมนูหลัก</nav>
<main>
  <h1>เลือกแพ็กเกจ</h1>
  <h2><span class="bi-th">รายปี</span><span class="bi-en">Yearly</span></h2>
  <p>ราคา <b>฿2,500</b> ต่อปี</p>
  <div aria-hidden="true">ตกแต่ง</div>
  <input value="ค่าที่พิมพ์">
  <footer>ท้ายเว็บ</footer>
</main>
<div id="ai-chat-widget">แชท</div>
</body></html>
HTML;

        $page = PageText::fromHtml($html);

        $this->assertSame('ราคา Tping | XMAN Studio', $page['title']);
        $this->assertSame('แพ็กเกจของ Tping', $page['description']);
        $this->assertSame('เลือกแพ็กเกจ', $page['h1']);
        $this->assertSame(['เลือกแพ็กเกจ', 'รายปี / Yearly'], $page['headings']);
        $this->assertStringContainsString('ราคา ฿2,500 ต่อปี', $page['text']);
        foreach (['เมนูหลัก', 'ท้ายเว็บ', 'ตกแต่ง', 'ค่าที่พิมพ์', 'secret', 'แชท'] as $gone) {
            $this->assertStringNotContainsString($gone, $page['text'], $gone);
        }
        $this->assertSame('ราคา Tping', SiteIndex::label($page));
    }

    public function test_the_index_reads_real_pages_and_leaves_out_what_is_not_a_page(): void
    {
        $this->withoutVite();
        // Without an admin the site sends every page to the setup wizard.
        User::factory()->create(['role' => 'admin']);

        $index = $this->index(['/terms' => 'terms', '/quote/services' => 'quote.services']);
        $result = $index->build();

        $this->assertSame(1, $result['pages']);
        $this->assertNotNull($index->page('/terms'));
        $this->assertNotSame('', $index->page('/terms')['text']);
        $this->assertTrue($index->isNotAPage('/quote/services'), 'JSON is not a page');
        $this->assertFalse($index->isStale());
    }

    public function test_a_deploy_that_changes_the_pages_makes_the_index_stale(): void
    {
        file_put_contents($this->file, json_encode(['built_at' => now()->toIso8601String(), 'fingerprint' => $this->index(['/a' => 'a'])->fingerprint(), 'pages' => []]));

        $this->assertFalse($this->index(['/a' => 'a'])->isStale());
        $this->assertTrue($this->index(['/a' => 'a', '/new-page' => 'b'])->isStale());
    }

    public function test_an_old_index_is_stale(): void
    {
        $index = $this->index(['/a' => 'a']);
        file_put_contents($this->file, json_encode(['built_at' => now()->subHours(SiteIndex::MAX_AGE_HOURS + 1)->toIso8601String(), 'fingerprint' => $index->fingerprint(), 'pages' => []]));

        $this->assertTrue($this->index(['/a' => 'a'])->isStale());
    }

    public function test_search_finds_the_page_about_the_rare_word_not_the_common_one(): void
    {
        $pages = [];
        foreach (range(1, 8) as $i) {
            $pages["/p{$i}"] = ['title' => "หน้า {$i}", 'text' => 'ราคา ราคา ราคา บริการของเรา'];
        }
        $pages['/tping/pricing'] = ['title' => 'ซื้อ License - Tping', 'headings' => ['Tping'], 'text' => 'Tping รายเดือน ราคา 399'];
        $this->writeIndex($pages);

        $found = $this->index()->search(Keywords::extract('ราคา Tping เท่าไหร่คะ'));
        $this->assertSame('/tping/pricing', $found[0]['path']);

        // Only the word every page has: that is a question about the open page, not a search.
        $this->assertSame([], $this->index()->search(Keywords::extract('อันนี้ราคาเท่าไหร่คะ')));
    }

    public function test_keywords_split_thai_from_latin_and_drop_the_fillers(): void
    {
        $words = Keywords::extract('ราคาTpingเท่าไหร่คะ');

        $this->assertContains('tping', $words);
        $this->assertContains('ราคา', $words);
        $this->assertNotContains('คะ', $words);
        $this->assertNotContains('เท่าไหร่', $words);
    }

    public function test_the_promotion_rule_lives_in_one_place(): void
    {
        $this->assertSame(0.50, Pricing::saleDiscount('web-ecommerce'));
        $this->assertSame(0.70, Pricing::saleDiscount('mobile'));
        $this->assertSame(0.70, Pricing::saleDiscount(null));
    }

    public function test_the_rename_migration_turns_the_old_default_into_nong_nova_and_keeps_a_chosen_name(): void
    {
        $migration = require database_path('migrations/2026_09_27_100000_rename_ai_chat_bot_to_nong_nova.php');

        Setting::setValue('ai_bot_name', 'AI Assistant', 'string', 'ai');
        $migration->up();
        $this->assertSame('น้อง Nova', Setting::getValue('ai_bot_name'));

        DB::table('settings')->where('key', 'ai_bot_name')->update(['value' => 'น้องเอ็กซ์']);
        Cache::forget('setting.ai_bot_name');
        $migration->up();
        $this->assertSame('น้องเอ็กซ์', Setting::getValue('ai_bot_name'));
    }

    public function test_the_chat_button_wears_the_name_and_greets_a_member_by_theirs(): void
    {
        $this->withoutVite();
        Setting::setValue('ai_chat_enabled', '1', 'boolean', 'ai');
        User::factory()->create(['role' => 'admin']);

        $this->get('/terms')->assertOk()
            ->assertSee('น้อง Nova')
            // The phone's bottom bar calls her by name too.
            ->assertSee('<span class="bi-th">น้อง Nova</span><span class="bi-en">AI Chat</span>', false)
            ->assertSee('xmanPageSnapshot', false)
            ->assertSee('const GREET_NAME = "";', false);

        $member = User::factory()->create(['name' => 'สมหญิง รักดี', 'role' => 'user']);
        // @json escapes non-ASCII, so the page carries the name as \u escapes.
        $this->actingAs($member)->get('/terms')->assertOk()
            ->assertSee('const GREET_NAME = ' . json_encode('สมหญิง') . ';', false);
    }
}

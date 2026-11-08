<?php

namespace Tests\Feature;

use App\Events\ArticlePublished;
use App\Listeners\PostArticleToFacebook;
use App\Models\Article;
use App\Models\Setting;
use App\Models\User;
use App\Services\FacebookPageException;
use App\Services\FacebookPageService;
use App\Support\AdminAlerts;
use App\Support\FacebookArticleCaption;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * New knowledge-base articles are shared to the company Facebook page (XmanEnterprise): once,
 * only when live, with a caption that teaches before it links — and every way that goes wrong
 * (token dead, permission missing, Facebook busy) ends in a Thai message an admin can act on.
 */
class FacebookPageAutoPostTest extends TestCase
{
    use RefreshDatabase;

    private const PAGE = '106036940932060';

    private const PAGE_TOKEN = 'EAAPageTokenForTests0123456789abcdefABCDEF';

    private const USER_TOKEN = 'EAAShortUserToken0123456789abcdef';

    private const LONG_USER_TOKEN = 'EAALongLivedUserToken0123456789abcdef';

    private const PERMANENT_PAGE_TOKEN = 'EAAPermanentPageToken0123456789abcdef';

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        Http::preventStrayRequests();
        AdminAlerts::$immediate = false;
    }

    private function connectPage(): void
    {
        Setting::setValue(FacebookPageService::KEY_PAGE_ID, self::PAGE, 'string', 'facebook');
        Setting::setValue(FacebookPageService::KEY_PAGE_NAME, 'XmanEnterprise', 'string', 'facebook');
        Setting::setValue(FacebookPageService::KEY_PAGE_TOKEN, self::PAGE_TOKEN, 'string', 'facebook');
    }

    private function telegramOn(): void
    {
        Setting::setValue('telegram_bot_token', '123456789:AAHdqTcvCH1vGWJxfSeofSAs0K5PALDsaw', 'string', 'telegram');
        Setting::setValue('telegram_chat_id', '-1001234567890', 'string', 'telegram');
        Setting::setValue('telegram_alerts_enabled', '1', 'boolean', 'telegram');
    }

    /** Facebook answers a feed post with $feed; the og: re-scrape and Telegram always succeed. */
    private function fakeGraph(?\Closure $feed = null): void
    {
        Http::fake([
            'graph.facebook.com/v22.0/' . self::PAGE . '/feed' => $feed ?? fn () => Http::response(['id' => self::PAGE . '_777']),
            'graph.facebook.com/v22.0/' => Http::response(['id' => 'https://xman4289.com/blog/x']),
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 1]]),
        ]);
    }

    private function article(array $overrides = []): Article
    {
        return Article::create(array_merge([
            'slug' => 'backup-3-2-1',
            'title' => 'สำรองข้อมูลแบบ 3-2-1 คืออะไร ทำไมธุรกิจเล็กก็ต้องมี',
            'excerpt' => 'ข้อมูลหายครั้งเดียวอาจปิดกิจการได้ กฎ 3-2-1 คือวิธีสำรองข้อมูลที่ง่ายและได้ผลที่สุด',
            'body' => '<h2>กฎ 3-2-1</h2><p>ย่อหน้าแรกของบทความ</p><ul><li>เก็บข้อมูลไว้ 3 ชุด บนสื่อ 2 แบบ และนอกสถานที่ 1 ชุด</li><li>ข้อสอง</li></ul>',
            'category' => 'infra',
            'tags' => ['Backup', 'สำรองข้อมูล', 'Cloud Storage'],
            'status' => Article::STATUS_PUBLISHED,
            'published_at' => now()->subMinute(),
        ], $overrides));
    }

    /** @return list<Request> */
    private function feedPosts(): array
    {
        return Http::recorded()->map(fn ($pair) => $pair[0])
            ->filter(fn (Request $r) => str_ends_with($r->url(), '/' . self::PAGE . '/feed'))->values()->all();
    }

    /** @return list<Request> */
    private function telegramSends(): array
    {
        return Http::recorded()->map(fn ($pair) => $pair[0])
            ->filter(fn (Request $r) => str_contains($r->url(), 'api.telegram.org') && preg_match('~/(sendPhoto|sendMessage)$~', $r->url()))
            ->values()->all();
    }

    // ------------------------------------------------------------------------------ auto-post

    public function test_publishing_an_article_shares_it_to_the_page_once(): void
    {
        $this->connectPage();
        $this->fakeGraph();

        $article = $this->article();

        $posts = $this->feedPosts();
        $this->assertCount(1, $posts);
        $this->assertSame($article->url(), $posts[0]['link']);
        $this->assertStringContainsString($article->title, $posts[0]['message']);
        // The token rides in the header, never the URL or the body (both end up in logs).
        $this->assertSame('Bearer ' . self::PAGE_TOKEN, $posts[0]->header('Authorization')[0]);
        $this->assertStringNotContainsString(self::PAGE_TOKEN, $posts[0]->url() . $posts[0]->body());

        $article->refresh();
        $this->assertSame(self::PAGE . '_777', $article->facebook_post_id);
        $this->assertNotNull($article->facebook_posted_at);
    }

    public function test_editing_or_republishing_never_posts_a_second_time(): void
    {
        $this->connectPage();
        $this->fakeGraph();
        $article = $this->article();

        $article->update(['title' => 'หัวข้อใหม่']);
        $article->update(['status' => Article::STATUS_ARCHIVED]);
        $article->update(['status' => Article::STATUS_PUBLISHED]); // fires ArticlePublished again

        $this->assertCount(1, $this->feedPosts());
    }

    public function test_nothing_is_sent_when_the_switch_is_off_or_the_page_is_not_connected(): void
    {
        $this->fakeGraph();
        $this->article(['slug' => 'not-connected']);

        $this->connectPage();
        Setting::setValue(FacebookPageService::KEY_AUTOPOST, false, 'boolean', 'facebook');
        $this->article(['slug' => 'switched-off']);

        $this->assertCount(0, $this->feedPosts());
        $this->assertNull(Article::where('slug', 'switched-off')->value('facebook_post_id'));
    }

    public function test_drafts_are_not_posted(): void
    {
        $this->connectPage();
        $this->fakeGraph();
        $this->article(['status' => Article::STATUS_DRAFT, 'published_at' => null]);

        $this->assertCount(0, $this->feedPosts());
    }

    public function test_an_article_scheduled_for_later_waits_until_it_goes_live(): void
    {
        Queue::fake();
        $this->connectPage();

        $article = $this->article(['published_at' => now()->addHours(2)]);

        Queue::assertPushed(CallQueuedListener::class, fn (CallQueuedListener $job) => $job->class === PostArticleToFacebook::class);

        // QueueFake drops the delay, so ask the listener what it hands the queue.
        $delay = app(PostArticleToFacebook::class)->withDelay(new ArticlePublished($article));
        $this->assertGreaterThanOrEqual(2 * 3600, $delay);
        $this->assertLessThanOrEqual(2 * 3600 + 60, $delay);
        $this->assertSame(0, app(PostArticleToFacebook::class)->withDelay(new ArticlePublished($this->article(['slug' => 'live-now']))));
    }

    public function test_a_dead_token_saves_nothing_and_tells_the_owner_on_telegram_in_thai(): void
    {
        $this->connectPage();
        $this->telegramOn();
        $this->fakeGraph(fn () => Http::response(['error' => [
            'message' => 'Error validating access token: Session has expired',
            'type' => 'OAuthException', 'code' => 190, 'error_subcode' => 463,
        ]], 400));

        $article = $this->article();

        $this->assertNull($article->fresh()->facebook_post_id);
        $sends = $this->telegramSends();
        $this->assertNotEmpty($sends, 'the owner must hear that the page needs reconnecting');
        $this->assertStringContainsString('Facebook', $sends[0]->body());
        $this->assertStringNotContainsString(self::PAGE_TOKEN, $sends[0]->body());
    }

    public function test_graph_errors_become_thai_messages_that_say_what_to_do(): void
    {
        $expired = FacebookPageService::explain(['code' => 190, 'error_subcode' => 463, 'message' => 'Session has expired'], 400);
        $this->assertStringContainsString('หมดอายุ', $expired->getMessage());
        $this->assertStringContainsString('เชื่อมเพจใหม่', $expired->getMessage());
        $this->assertTrue($expired->needsReconnect);
        $this->assertFalse($expired->retryable);

        $permission = FacebookPageService::explain(['code' => 200, 'message' => 'Requires pages_manage_posts'], 403);
        $this->assertStringContainsString('pages_manage_posts', $permission->getMessage());
        $this->assertTrue($permission->needsReconnect);

        $busy = FacebookPageService::explain(['code' => 4, 'message' => 'Application request limit reached'], 400);
        $this->assertTrue($busy->retryable);
        $this->assertStringContainsString('ลองโพสต์ใหม่เอง', $busy->getMessage());

        $down = FacebookPageService::explain([], 503);
        $this->assertTrue($down->retryable);
        $this->assertStringContainsString('HTTP 503', $down->getMessage());
    }

    // ------------------------------------------------------------------------------ caption

    public function test_the_caption_teaches_one_thing_then_links_with_three_to_five_hashtags(): void
    {
        $this->connectPage();
        Queue::fake();
        $article = $this->article();

        $caption = FacebookArticleCaption::for($article);

        $this->assertStringStartsWith($article->title, $caption);
        $this->assertStringContainsString('กฎ 3-2-1 คือวิธีสำรองข้อมูล', $caption); // the excerpt
        $this->assertStringContainsString('เกร็ดหนึ่งข้อจากบทความ: เก็บข้อมูลไว้ 3 ชุด', $caption); // first list item
        $this->assertStringContainsString($article->url(), $caption);
        $this->assertStringNotContainsString('<', $caption);

        preg_match_all('/#[\p{L}\p{M}\p{N}_]+/u', $caption, $tags);
        $this->assertGreaterThanOrEqual(3, count($tags[0]));
        $this->assertLessThanOrEqual(5, count($tags[0]));
        $this->assertContains('#CloudStorage', $tags[0]);
        $this->assertSame('#XMANStudio', end($tags[0]));
    }

    public function test_an_article_without_tags_still_gets_three_hashtags(): void
    {
        Queue::fake();
        $article = $this->article(['tags' => null, 'category' => 'security']);

        $this->assertSame(['ความปลอดภัยไซเบอร์', 'ความรู้ไอที', 'XMANStudio'], FacebookArticleCaption::hashtags($article));
    }

    // ------------------------------------------------------------------------------ connecting

    public function test_a_user_token_is_traded_for_a_page_token_that_never_expires(): void
    {
        Setting::setValue(FacebookPageService::KEY_PAGE_ID, self::PAGE, 'string', 'facebook');
        Setting::setValue(FacebookPageService::KEY_APP_ID, '664172615253513', 'string', 'facebook');
        Setting::setValue(FacebookPageService::KEY_APP_SECRET, 'app-secret-for-tests', 'string', 'facebook');

        Http::fake(function (Request $r) {
            $auth = $r->header('Authorization')[0] ?? '';
            $path = parse_url($r->url(), PHP_URL_PATH);

            return match (true) {
                $path === '/v22.0/me' => Http::response(['id' => '9001', 'name' => 'Owner']),
                $path === '/v22.0/oauth/access_token' => Http::response(['access_token' => self::LONG_USER_TOKEN]),
                $path === '/v22.0/me/accounts' => Http::response(['data' => [[
                    'id' => self::PAGE, 'name' => 'XmanEnterprise', 'tasks' => ['MANAGE', 'CREATE_CONTENT'],
                    'access_token' => $auth === 'Bearer ' . self::LONG_USER_TOKEN ? self::PERMANENT_PAGE_TOKEN : 'short-lived-page-token',
                ]]]),
                $path === '/v22.0/debug_token' => Http::response(['data' => [
                    'type' => 'PAGE', 'is_valid' => true, 'expires_at' => 0,
                    'scopes' => ['pages_show_list', 'pages_read_engagement', 'pages_manage_posts'],
                ]]),
                default => Http::response(['error' => ['message' => 'unexpected ' . $path]], 400),
            };
        });

        $result = app(FacebookPageService::class)->connect(self::USER_TOKEN);

        $this->assertSame('user', $result['kind']);
        $this->assertSame('XmanEnterprise', $result['page_name']);
        $this->assertNull($result['expires_at']);
        $this->assertSame([], $result['warnings']);
        $this->assertSame(self::PERMANENT_PAGE_TOKEN, Setting::getValue(FacebookPageService::KEY_PAGE_TOKEN));
        // Stored encrypted, not as plain text.
        $this->assertNotSame(self::PERMANENT_PAGE_TOKEN, Setting::where('key', FacebookPageService::KEY_PAGE_TOKEN)->value('value'));
    }

    public function test_an_account_that_cannot_create_content_is_refused_with_the_reason(): void
    {
        Setting::setValue(FacebookPageService::KEY_PAGE_ID, self::PAGE, 'string', 'facebook');
        Http::fake([
            'graph.facebook.com/v22.0/me/accounts*' => Http::response(['data' => [[
                'id' => self::PAGE, 'name' => 'XmanEnterprise', 'tasks' => ['ANALYZE'], 'access_token' => 'x',
            ]]]),
            'graph.facebook.com/v22.0/me*' => Http::response(['id' => '9001', 'name' => 'Owner']),
        ]);

        try {
            app(FacebookPageService::class)->connect(self::USER_TOKEN);
            $this->fail('should refuse');
        } catch (FacebookPageException $e) {
            $this->assertStringContainsString('ไม่มีสิทธิ์สร้างโพสต์', $e->getMessage());
        }
        $this->assertSame('', (string) Setting::getValue(FacebookPageService::KEY_PAGE_TOKEN, ''));
    }

    // ------------------------------------------------------------------------------ manual posting

    public function test_the_command_posts_by_slug_and_will_not_repeat_without_force(): void
    {
        $this->fakeGraph();
        $this->article(); // not connected yet, so nothing was posted
        $this->connectPage();

        $this->artisan('articles:facebook', ['slug' => 'backup-3-2-1'])
            ->expectsOutputToContain('โพสต์แล้ว')->assertSuccessful();
        $this->artisan('articles:facebook', ['slug' => 'backup-3-2-1'])
            ->expectsOutputToContain('เคยโพสต์แล้ว')->assertSuccessful();
        $this->assertCount(1, $this->feedPosts());

        $this->artisan('articles:facebook', ['slug' => 'backup-3-2-1', '--force' => true])->assertSuccessful();
        $this->assertCount(2, $this->feedPosts());

        $this->artisan('articles:facebook', ['slug' => 'no-such-article'])->assertFailed();
    }

    public function test_the_command_can_connect_the_page_from_a_token_file_without_printing_it(): void
    {
        Http::fake(['graph.facebook.com/v22.0/me*' => Http::response(['id' => self::PAGE, 'name' => 'XmanEnterprise'])]);
        $file = tempnam(sys_get_temp_dir(), 'fbt');
        file_put_contents($file, self::PAGE_TOKEN . "\n");

        try {
            $this->artisan('facebook:page-token', ['--page' => self::PAGE, '--file' => $file])
                ->expectsOutputToContain('เชื่อมเพจแล้ว: XmanEnterprise')
                ->doesntExpectOutputToContain(self::PAGE_TOKEN)
                ->assertSuccessful();
        } finally {
            @unlink($file);
        }

        $this->assertSame(self::PAGE_TOKEN, Setting::getValue(FacebookPageService::KEY_PAGE_TOKEN));
    }

    public function test_the_admin_button_posts_an_article_and_only_admins_can_press_it(): void
    {
        $this->fakeGraph();
        $article = $this->article();
        $this->connectPage();

        $this->post(route('admin.articles.facebook', $article))->assertRedirect(route('login'));

        $member = User::factory()->create(['role' => 'user']);
        $this->actingAs($member)->post(route('admin.articles.facebook', $article));
        $this->assertCount(0, $this->feedPosts());

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->from('/admin/facebook-page')
            ->post(route('admin.articles.facebook', $article))
            ->assertRedirect('/admin/facebook-page')
            ->assertSessionHas('success', fn ($m) => str_contains($m, 'facebook.com/' . self::PAGE . '_777'));
        $this->assertCount(1, $this->feedPosts());
    }

    public function test_the_settings_screen_never_shows_the_token(): void
    {
        $this->connectPage();
        Queue::fake();
        $this->article();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('admin.facebook-page.index'))
            ->assertOk()
            ->assertSee('XmanEnterprise')
            ->assertSee('เก็บไว้แล้ว (เข้ารหัส)')
            ->assertDontSee(self::PAGE_TOKEN);
    }

    public function test_changing_the_page_without_a_new_token_is_refused(): void
    {
        $this->connectPage();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->from(route('admin.facebook-page.index'))
            ->put(route('admin.facebook-page.update'), ['page_id' => '123456789012', 'autopost' => '1'])
            ->assertSessionHas('error');

        $this->assertSame(self::PAGE, Setting::getValue(FacebookPageService::KEY_PAGE_ID));
    }
}

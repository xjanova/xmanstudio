<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\GithubSetting;
use App\Models\Product;
use App\Models\ProductVersion;
use App\Services\GithubReleaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * โควตา GitHub API ของเซิร์ฟเวอร์ (2026-09-25)
 *
 * แอปที่ไม่มี token ใช้โควตา 60 ครั้ง/ชม. ของ IP เซิร์ฟเวอร์ร่วมกัน — คืนนั้นโควตาหมด
 * แล้วทุก page view กับ update/check ของแอปยิงซ้ำเพราะความล้มเหลวไม่ถูก cache โควตาจึงหมดทั้งชั่วโมง
 * Chanthra 0.11.0 ไม่ถูก sync · 304 ของคำขอที่ไม่มี token ยังนับโควตา (วัดแล้ว) ETag จึงไม่ช่วย ต้องถามให้น้อยลง:
 *   — GitHub บอกว่าหมด = พักทุกทาง (read-through, cron, ปุ่ม Sync) จนถึงเวลารีเซ็ต · log ครั้งเดียวต่อช่วง
 *   — เหลือไม่เกิน READ_THROUGH_RESERVE = read-through หยุด เก็บไว้ให้ cron
 *   — cron ถามแอปที่ไม่มี token อย่างมากทุก 30 นาที · หลัง sync ไม่ถามซ้ำ · เจอเวอร์ชันใหม่ถามครั้งเดียว
 */
class GithubReleaseQuotaTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        // ห้ามคุยกับ GitHub จริง
        Http::preventStrayRequests();

        // migration ลงแอปจริงไว้หลายตัว (aipray, gpuxmine, winx-tools …) — cron ในเทสต์ต้องเห็นแค่แอปของเทสต์
        GithubSetting::query()->update(['is_active' => false]);

        $this->category = Category::firstOrCreate(['slug' => 'software'], ['name' => 'Software', 'description' => 'x']);
    }

    public function test_a_rate_limited_answer_pauses_every_github_call_until_the_reset(): void
    {
        $a = $this->githubApp('app-a', 'AppA', '1.0.0');
        $b = $this->githubApp('app-b', 'AppB', '2.0.0');
        $reset = now()->addMinutes(30)->getTimestamp();

        Http::fake(['api.github.com/*' => Http::sequence()
            ->push(['message' => 'API rate limit exceeded for 203.0.113.7.'], 403, [
                'x-ratelimit-limit' => '60', 'x-ratelimit-remaining' => '0', 'x-ratelimit-reset' => (string) $reset,
            ])
            ->push($this->release('1.1.0'), 200)]);
        Log::spy();

        $github = app(GithubReleaseService::class);

        // หมดแล้ว: ได้ของใน DB · คำขอถัดไปของแอปเดิม แอปอื่น และ cron ไม่แตะ GitHub อีก
        $this->assertSame('1.0.0', $github->latestVersionFresh($a)->version);
        $this->assertSame('1.0.0', $github->latestVersionFresh($a)->version);
        $this->assertSame('2.0.0', $github->latestVersionFresh($b)->version);
        $this->artisan('products:sync-releases')->assertSuccessful();
        Http::assertSentCount(1);

        // log การพักครั้งเดียว — ไม่ใช่ "GitHub API Error" ทุกคำขอ
        Log::shouldHaveReceived('warning')->withArgs(fn ($message) => str_contains($message, 'quota exhausted'))->once();
        Log::shouldNotHaveReceived('error');

        // พ้นเวลารีเซ็ตแล้ว ถามได้ตามปกติ
        $this->travelTo(Carbon::createFromTimestamp($reset)->addSeconds(10));

        $this->assertSame('1.1.0', $github->latestVersionFresh($a)->version);
        Http::assertSentCount(2);
    }

    public function test_a_secondary_limit_pauses_for_its_retry_after_without_giving_up_the_token(): void
    {
        $paid = $this->githubApp('app-paid', 'AppPaid', '1.0.0', 'ghp_still_valid_token');
        $free = $this->githubApp('app-free', 'AppFree', '3.0.0');

        Http::fake([
            'api.github.com/repos/xjanova/AppPaid/*' => Http::sequence()->push(
                ['message' => 'You have exceeded a secondary rate limit. Please wait a few minutes before you try again.'],
                403,
                ['retry-after' => '120', 'x-ratelimit-remaining' => '4990', 'x-ratelimit-reset' => (string) now()->addHour()->getTimestamp()],
            ),
            'api.github.com/repos/xjanova/AppFree/*' => Http::response($this->release('3.1.0')),
        ]);

        $github = app(GithubReleaseService::class);

        $this->assertSame('1.0.0', $github->latestVersionFresh($paid)->version);

        // secondary limit ไม่ใช่ token เสีย — ไม่ยิงซ้ำแบบไม่ใส่ token (เดิมเสียโควตาร่วมไปอีกครั้ง)
        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request) => $request->hasHeader('Authorization'));
        $this->assertEqualsWithDelta(now()->addSeconds(120)->getTimestamp(), $github->quotaPausedUntil($paid->githubSetting)->getTimestamp(), 2);

        // โควตาของ token ไม่เกี่ยวกับโควตาร่วมของแอปที่ไม่มี token
        $this->assertSame('3.1.0', $github->latestVersionFresh($free)->version);
        Http::assertSentCount(2);
    }

    public function test_page_views_leave_the_last_calls_of_the_hour_to_the_cron(): void
    {
        $a = $this->githubApp('app-a', 'AppA', '1.0.0');
        $b = $this->githubApp('app-b', 'AppB', '2.0.0');
        $reset = (string) now()->addMinutes(40)->getTimestamp();

        Http::fake([
            'api.github.com/repos/xjanova/AppA/*' => Http::response($this->release('1.0.0'), 200, ['x-ratelimit-remaining' => '18', 'x-ratelimit-reset' => $reset]),
            'api.github.com/repos/xjanova/AppB/*' => Http::response($this->release('2.1.0'), 200, ['x-ratelimit-remaining' => '17', 'x-ratelimit-reset' => $reset]),
        ]);

        $github = app(GithubReleaseService::class);

        $github->latestVersionFresh($a);
        // เหลือ 18 ≤ READ_THROUGH_RESERVE — page view ของแอปอื่นใช้ของใน DB
        $this->assertSame('2.0.0', $github->latestVersionFresh($b)->version);
        Http::assertSentCount(1);

        // cron ยังถามได้ (โควตาที่เก็บไว้มีไว้ให้มัน) · AppA เพิ่งถูกถามไป จึงข้าม
        $this->artisan('products:sync-releases')->assertSuccessful();
        Http::assertSentCount(2);
        $this->assertSame('2.1.0', $b->fresh()->latestVersion()->version);
    }

    public function test_the_cron_asks_about_a_tokenless_app_at_most_every_half_hour(): void
    {
        $this->githubApp('app-free', 'AppFree', '1.0.0');
        $this->githubApp('app-paid', 'AppPaid', '1.0.0', 'ghp_still_valid_token');

        Http::fake(['api.github.com/*' => Http::response($this->release('1.0.0'))]);

        $this->artisan('products:sync-releases')->assertSuccessful();
        $this->assertSame(['AppFree' => 1, 'AppPaid' => 1], $this->callsPerRepo());

        // สิบนาทีต่อมา: แอปที่มี token ถามทุกรอบ · แอปที่ไม่มี token รอให้ครบ 30 นาที
        $this->travel(10)->minutes();
        $this->artisan('products:sync-releases')->assertSuccessful();
        $this->assertSame(['AppFree' => 1, 'AppPaid' => 2], $this->callsPerRepo());

        $this->travel(21)->minutes();
        $this->artisan('products:sync-releases')->assertSuccessful();
        $this->assertSame(['AppFree' => 2, 'AppPaid' => 3], $this->callsPerRepo());

        // สั่งมือถามเสมอ
        $this->artisan('products:sync-releases', ['--force' => true])->assertSuccessful();
        $this->assertSame(['AppFree' => 3, 'AppPaid' => 4], $this->callsPerRepo());
    }

    public function test_a_page_view_right_after_a_sync_does_not_ask_github_again(): void
    {
        $a = $this->githubApp('app-a', 'AppA', '1.0.0');

        Http::fake(['api.github.com/*' => Http::response($this->release('1.0.0'))]);

        $this->artisan('products:sync-releases')->assertSuccessful();
        $this->assertSame('1.0.0', app(GithubReleaseService::class)->latestVersionFresh($a)->version);

        Http::assertSentCount(1);
    }

    public function test_a_new_release_found_on_a_page_view_costs_one_call(): void
    {
        $a = $this->githubApp('app-a', 'AppA', '1.0.0');

        Http::fake(['api.github.com/*' => Http::response($this->release('1.1.0'))]);

        $this->assertSame('1.1.0', app(GithubReleaseService::class)->latestVersionFresh($a)->version);

        // เดิมสองครั้ง: read-through ถามหนึ่งครั้ง แล้ว sync ถามซ้ำอีกครั้ง
        Http::assertSentCount(1);
    }

    public function test_other_failures_are_still_retried_on_the_next_request(): void
    {
        $a = $this->githubApp('app-a', 'AppA', '1.0.0');

        Http::fake(['api.github.com/*' => Http::sequence()
            ->push(['message' => 'Server Error'], 500)
            ->push($this->release('1.1.0'))]);

        $github = app(GithubReleaseService::class);

        // GitHub สะดุดแวบเดียวต้องไม่ทำให้หยุดถาม (ห้าม cache ความล้มเหลว — เคส tpix.online)
        $this->assertSame('1.0.0', $github->latestVersionFresh($a)->version);
        $this->assertNull($github->quotaPausedUntil($a->githubSetting));
        $this->assertSame('1.1.0', $github->latestVersionFresh($a)->version);
        Http::assertSentCount(2);
    }

    public function test_the_admin_sync_button_says_until_when_github_is_paused(): void
    {
        $a = $this->githubApp('app-a', 'AppA', '1.0.0');
        $reset = Carbon::parse('2026-09-24 18:30:06', 'UTC');
        $this->travelTo($reset->copy()->subMinutes(20));

        Http::fake(['api.github.com/*' => Http::response(['message' => 'API rate limit exceeded'], 403, [
            'x-ratelimit-remaining' => '0', 'x-ratelimit-reset' => (string) $reset->getTimestamp(),
        ])]);

        $github = app(GithubReleaseService::class);

        foreach ([1, 2] as $attempt) {
            try {
                $github->syncLatestRelease($a);
                $this->fail('sync ต้องบอกว่าทำไม่ได้');
            } catch (\Exception $e) {
                // หน้า admin แสดง "Sync ล้มเหลว: …" ตามด้วยข้อความนี้ — เวลาไทย (รีเซ็ต +5 วินาที)
                $this->assertSame('โควตา GitHub API ของเซิร์ฟเวอร์หมดชั่วคราว ลองใหม่หลัง 01:30 น.', $e->getMessage());
            }
        }

        // ครั้งที่สองไม่ได้ถาม GitHub
        Http::assertSentCount(1);
    }

    // ── helpers ───────────────────────────────────────────────────────

    /** แอปฟรีบน GitHub + เวอร์ชันที่ DB รู้อยู่แล้ว · ไม่ใส่ token = โควตาร่วมของเซิร์ฟเวอร์ แบบ 7 แอปใน production */
    private function githubApp(string $slug, string $repo, string $version, string $token = ''): Product
    {
        $product = Product::updateOrCreate(['slug' => $slug], [
            'category_id' => $this->category->id,
            'name' => $slug,
            'description' => 'x',
            'price' => 0,
            'stock' => 0,
            'requires_license' => false,
            'is_active' => true,
        ]);

        GithubSetting::updateOrCreate(['product_id' => $product->id], [
            'github_owner' => 'xjanova',
            'github_repo' => $repo,
            'github_token' => $token,
            'asset_pattern' => '*',
            'is_active' => true,
            'auto_sync' => true,
        ]);

        ProductVersion::create([
            'product_id' => $product->id,
            'version' => $version,
            'github_release_url' => "https://api.github.com/repos/xjanova/{$repo}/releases/assets/1",
            'download_filename' => "{$repo}-{$version}.zip",
            'file_size' => 1024,
            'is_active' => true,
            'synced_at' => now(),
        ]);

        return $product->fresh();
    }

    private function release(string $version): array
    {
        return [
            'id' => 9001,
            'tag_name' => 'v' . $version,
            'html_url' => "https://github.com/xjanova/x/releases/tag/v{$version}",
            'body' => 'notes',
            'assets' => [[
                'id' => 77,
                'name' => "app-{$version}.zip",
                'size' => 1024,
                'url' => 'https://api.github.com/repos/xjanova/x/releases/assets/77',
                'browser_download_url' => "https://github.com/xjanova/x/releases/download/v{$version}/app-{$version}.zip",
            ]],
        ];
    }

    /** @return array<string,int> repo => จำนวนครั้งที่ถาม release ล่าสุด */
    private function callsPerRepo(): array
    {
        $calls = Http::recorded()
            ->map(fn ($pair) => preg_match('#/repos/xjanova/([^/]+)/releases/latest#', $pair[0]->url(), $m) ? $m[1] : null)
            ->filter()
            ->countBy()
            ->sortKeys()
            ->all();

        return $calls;
    }
}

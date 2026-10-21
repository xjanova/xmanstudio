<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\GithubSetting;
use App\Models\Product;
use App\Models\ProductVersion;
use App\Services\GithubReleaseService;
use App\Support\ReleaseNotes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * release notes ที่ sync มาจาก GitHub ต้องไม่บอกลูกค้าว่า repo อยู่ไหน (กฎเจ้าของ 2026-09-24)
 *
 *   — sync เก็บเฉพาะข้อความที่คนเขียน ไม่มี "**Full Changelog**: …/compare/…" หรือลิงก์ PR
 *   — แถวเก่าที่ไม่ถูก sync ซ้ำ (API รายการเวอร์ชันคืนย้อนหลัง 10 ตัว) ถูกกรองตอนอ่าน และ migration ล้างให้ตอน deploy
 *   — หน้าสินค้า, update/check และหน้า Aipray ไม่มีคำว่า github
 */
class ReleaseNotesForCustomersTest extends TestCase
{
    use RefreshDatabase;

    /** SmsChecker v2.0.188 ใน production: CI แปะ commit message ทั้งก้อน */
    private const CI_NOTES = "**Build #188** — Release APK (signed)\n\n"
        . "Commit: f3cd9954534fb8f385fa07b5bf2badd26316aedb\n"
        . "ci(android): install only platform-tools\n\n"
        . 'Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>';

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        // ห้ามคุยกับ GitHub จริง
        Http::preventStrayRequests();

        $this->category = Category::firstOrCreate(['slug' => 'software'], ['name' => 'Software', 'description' => 'x']);
    }

    public function test_a_synced_release_is_stored_with_only_what_a_person_wrote(): void
    {
        $product = $this->product('some-desktop-app');
        $this->githubSetting($product, 'SomeApp');

        Http::fake([
            'api.github.com/repos/xjanova/SomeApp/releases/latest' => Http::response($this->release('2.0.0',
                "## What's Changed\r\n"
                . "* Faster start-up by @xjanova in https://github.com/xjanova/SomeApp/pull/7\r\n\r\n"
                . '**Full Changelog**: https://github.com/xjanova/SomeApp/compare/v1.9.0...v2.0.0'
            )),
        ]);

        app(GithubReleaseService::class)->syncLatestRelease($product);

        // ค่าที่เก็บใน DB เอง ไม่ใช่แค่ที่อ่านผ่าน model
        $this->assertSame("## What's Changed\n* Faster start-up", DB::table('product_versions')->value('changelog'));
    }

    public function test_a_release_that_is_only_the_full_changelog_line_is_stored_as_nothing(): void
    {
        $product = $this->product('some-desktop-app');
        $this->githubSetting($product, 'SomeApp');

        Http::fake([
            'api.github.com/repos/xjanova/SomeApp/releases/latest' => Http::response($this->release('2.0.0',
                '**Full Changelog**: https://github.com/xjanova/SomeApp/compare/v1.9.0...v2.0.0'
            )),
        ]);

        app(GithubReleaseService::class)->syncLatestRelease($product);

        $this->assertNull(DB::table('product_versions')->value('changelog'));
    }

    public function test_the_product_page_and_the_update_check_never_mention_github(): void
    {
        // สิ่งที่เจ้าของจะเช็คเอง: หน้า /products/gpuxmine กับ update/check ของ gpuxmine
        // migration register_gpuxmine_product สร้างสินค้า + GitHub setting ไว้ในฐานเทสต์แล้ว
        $product = $this->product('gpuxmine');
        $this->githubSetting($product, 'GpuXmine');
        $notes = '**Full Changelog**: https://github.com/xjanova/GpuXmine/compare/v0.1.16...v0.1.17';
        $this->rawVersion($product, '0.1.17', $notes);

        // update/check ถาม GitHub ก่อนว่ามีตัวใหม่ไหม (read-through) — ตัวเดียวกับที่ DB มี
        Http::fake(['api.github.com/repos/xjanova/GpuXmine/releases/latest' => Http::response($this->release('0.1.17', $notes))]);

        $page = $this->get('/products/gpuxmine')->assertOk()->getContent();

        $this->assertStringNotContainsStringIgnoringCase('github', $page);
        // มีแต่บรรทัดนั้น → ไม่เหลืออะไร กล่อง Changelog ต้องหาย ไม่ใช่กล่องเปล่า
        $this->assertStringNotContainsString('>Changelog<', $page);

        $check = $this->getJson('/api/v1/product/gpuxmine/update/check?current_version=0.0.1')
            ->assertOk()
            ->assertJsonPath('changelog', '');

        $this->assertStringNotContainsStringIgnoringCase('github', $check->getContent());
    }

    public function test_versions_that_were_never_synced_again_are_cleaned_when_read(): void
    {
        // API รายการเวอร์ชันคืนย้อนหลังถึง 10 ตัว — ตัวเก่าไม่มีวันผ่าน sync อีก · ข้อความที่ admin พิมพ์เองก็ไม่ผ่าน
        $product = $this->product('smschecker-like');
        $this->githubSetting($product, 'smschecker');
        $old = $this->rawVersion($product, '2.0.188', self::CI_NOTES, active: false);
        $latest = "ดูรายละเอียดที่ [CHANGELOG](https://github.com/xjanova/smschecker/blob/main/CHANGELOG.md)\n\nขอบคุณ @xjanova";
        $this->rawVersion($product, '2.0.189', $latest);

        Http::fake(['api.github.com/repos/xjanova/smschecker/releases/latest' => Http::response($this->release('2.0.189', $latest))]);

        $clean = "**Build #188** — Release APK (signed)\n\nci(android): install only platform-tools";
        $this->assertSame($clean, $old->fresh()->changelog);
        $this->assertStringContainsString('Commit: f3cd99', DB::table('product_versions')->where('id', $old->id)->value('changelog'));

        foreach ([
            '/api/v1/products/smschecker-like/versions',
            '/api/v1/products/smschecker-like/version',
            '/api/v1/product/smschecker-like/update/check?current_version=0.0.1',
        ] as $url) {
            $json = $this->getJson($url)->assertOk()->getContent();

            $this->assertStringNotContainsStringIgnoringCase('github', $json, $url);
            $this->assertStringNotContainsString('xjanova', $json, $url);
            $this->assertStringNotContainsString('f3cd9954534fb8f385fa07b5bf2badd26316aedb', $json, $url);
        }

        $changelogs = collect($this->getJson('/api/v1/products/smschecker-like/versions')->json('versions'))
            ->pluck('changelog', 'version');

        $this->assertSame($clean, $changelogs['2.0.188']);
        $this->assertSame("ดูรายละเอียดที่ CHANGELOG\n\nขอบคุณ", $changelogs['2.0.189']);
    }

    public function test_the_migration_cleans_the_rows_that_leak_and_leaves_the_rest_untouched(): void
    {
        $product = $this->product('some-desktop-app');
        $this->githubSetting($product, 'SomeApp');

        $leaking = $this->rawVersion($product, '0.1.17', '**Full Changelog**: https://github.com/xjanova/SomeApp/compare/v0.1.16...v0.1.17');
        $ci = $this->rawVersion($product, '2.0.188', self::CI_NOTES);
        // ลิงก์เว็บเราเอง + CRLF — ต้องเหมือนเดิมทุก byte และไม่ถูกแตะเลย
        $mine = "📦 ซื้อ License: https://xman4289.com/localvpn/buy\r\nLocal mode: [Ollama](https://ollama.com)\r\n";
        $kept = $this->rawVersion($product, '1.0.39', $mine);
        DB::table('product_versions')->update(['updated_at' => '2026-01-01 00:00:00']);

        $migration = require database_path('migrations/2026_09_24_180000_scrub_github_links_from_release_notes.php');
        $migration->up();

        $raw = fn (ProductVersion $v) => DB::table('product_versions')->where('id', $v->id)->first();

        $this->assertNull($raw($leaking)->changelog);
        $this->assertSame("**Build #188** — Release APK (signed)\n\nci(android): install only platform-tools", $raw($ci)->changelog);
        $this->assertSame($mine, $raw($kept)->changelog);
        $this->assertSame('2026-01-01 00:00:00', (string) $raw($kept)->updated_at);

        // รันซ้ำ (deploy ซ้ำ) ไม่เปลี่ยนอะไร
        $migration->up();
        $this->assertSame($mine, $raw($kept)->changelog);
    }

    public function test_the_aipray_page_shows_the_notes_as_safe_html_without_github(): void
    {
        $product = $this->product('aipray');
        $body = "## Aipray v1.2.4 - สวดมนต์อัจฉริยะ\r\n\r\n"
            . "### ดาวน์โหลด APK\r\n"
            . "- **aipray-universal.apk** — ใช้ได้กับทุกอุปกรณ์ (แนะนำ)\r\n"
            . "- [ลองกดดู](javascript:alert(1))\r\n\r\n"
            . "<script>alert('xss')</script>\r\n\r\n"
            . '**Full Changelog**: https://github.com/xjanova/Aipray/compare/v1.2.3...v1.2.4';

        // ตัวเดียวกันทั้งในหน้าแบบถาม GitHub สด ๆ และแบบอ่านจาก DB — เทสต์นี้ไม่ขึ้นกับว่า controller ดึงจากไหน
        $this->rawVersion($product, '1.2.4', $body);
        Http::fake([
            'api.github.com/repos/xjanova/Aipray/releases/latest' => Http::response($this->release('1.2.4', $body, 'aipray')),
        ]);

        $html = $this->get(route('aipray.show'))->assertOk()->getContent();

        $this->assertStringNotContainsStringIgnoringCase('github', $html);
        $this->assertStringNotContainsString("<script>alert('xss')</script>", $html);
        $this->assertStringNotContainsString('javascript:alert', $html);
        // markdown กลายเป็น HTML จริง ไม่ใช่เครื่องหมาย ## ติดกันเป็นก้อน
        $this->assertStringContainsString('<h3>ดาวน์โหลด APK</h3>', $html);
        $this->assertStringContainsString('<strong>aipray-universal.apk</strong>', $html);
    }

    public function test_a_newly_added_github_account_is_recognised_at_once(): void
    {
        // ลิงก์ไป GitHub หายเสมอ แต่ชื่อบัญชีที่เขียนเป็นข้อความ (@otherstudio) หายเฉพาะบัญชีที่เรารู้จัก
        $product = $this->product('some-desktop-app');
        $this->githubSetting($product, 'SomeApp');
        $version = $this->rawVersion($product, '1.0.0', 'ขอบคุณ @otherstudio ที่ช่วยทดสอบ');

        // รายชื่อบัญชีถูก cache ไว้แล้ว (ยังไม่มี otherstudio)
        $this->assertSame(['xjanova'], ReleaseNotes::studioAccounts());
        $this->assertSame('ขอบคุณ @otherstudio ที่ช่วยทดสอบ', $version->fresh()->changelog);

        // เพิ่มสินค้าที่ sync จากบัญชี otherstudio → cache ถูกล้าง → ชื่อนั้นหายทันที ไม่ต้องรอ 10 นาที
        $this->githubSetting($this->product('other-app'), 'OtherApp', 'otherstudio');

        $this->assertSame('ขอบคุณ ที่ช่วยทดสอบ', $version->fresh()->changelog);
    }

    // ── helpers ───────────────────────────────────────────────────────

    /** บางตัว (gpuxmine, aipray) migration สร้างไว้แล้ว — ใช้แถวเดิม */
    private function product(string $slug): Product
    {
        return Product::updateOrCreate(['slug' => $slug], [
            'category_id' => $this->category->id,
            'name' => $slug,
            'description' => 'x',
            'price' => 0,
            'stock' => 0,
            'requires_license' => false,
            'is_active' => true,
        ]);
    }

    private function githubSetting(Product $product, string $repo, string $owner = 'xjanova'): GithubSetting
    {
        return GithubSetting::updateOrCreate(['product_id' => $product->id], [
            'github_owner' => $owner,
            'github_repo' => $repo,
            'github_token' => '',
            'asset_pattern' => '*',
            'is_active' => true,
            'auto_sync' => true,
        ]);
    }

    /** แถวแบบที่ sync ไว้ก่อนมีตัวกรอง — body ของ GitHub ทั้งดุ้น */
    private function rawVersion(Product $product, string $version, string $changelog, bool $active = true): ProductVersion
    {
        return ProductVersion::create([
            'product_id' => $product->id,
            'version' => $version,
            'github_release_url' => "https://api.github.com/repos/xjanova/x/releases/assets/{$version}",
            'download_filename' => "App-{$version}.zip",
            'file_size' => 1024,
            'changelog' => $changelog,
            'is_active' => $active,
            'synced_at' => now(),
        ]);
    }

    private function release(string $version, string $body, string $asset = 'app'): array
    {
        return [
            'id' => 9001,
            'tag_name' => 'v' . $version,
            'html_url' => "https://github.com/xjanova/x/releases/tag/v{$version}",
            'body' => $body,
            'assets' => [[
                'id' => 77,
                'name' => "{$asset}-{$version}.zip",
                'size' => 1024,
                'url' => 'https://api.github.com/repos/xjanova/x/releases/assets/77',
                'browser_download_url' => "https://github.com/xjanova/x/releases/download/v{$version}/{$asset}-{$version}.zip",
            ]],
        ];
    }
}

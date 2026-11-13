<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\GithubSetting;
use App\Models\Product;
use App\Models\ProductVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * CluadeX has no portable zip any more (owner 2026-11-13): the download button and update/check send the installer.
 * The file is whatever the product's asset_pattern matches, so the migration moves the pattern — and the version already
 * synced — from the zip to CluadeX-win-Setup.exe. Nothing here asks GitHub.
 */
class CluadeXDownloadsTheInstallerTest extends TestCase
{
    use RefreshDatabase;

    private const SLUG = 'cluadex-ai-coding-assistant';

    private const MIGRATION = 'migrations/2026_11_13_100000_cluadex_downloads_the_installer.php';

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Http::preventStrayRequests();
        Http::fake();

        $category = Category::firstOrCreate(['slug' => 'software'], ['name' => 'Software', 'description' => 'x']);
        $this->product = Product::firstOrCreate(['slug' => self::SLUG], [
            'category_id' => $category->id, 'name' => 'CluadeX', 'description' => 'x', 'price' => 0,
            'requires_license' => true, 'is_active' => true,
        ]);
        $this->product->update(['is_active' => true, 'is_coming_soon' => false, 'coming_soon_until' => null]);

        GithubSetting::updateOrCreate(['product_id' => $this->product->id], [
            'github_owner' => 'xjanova', 'github_repo' => 'cluadeX', 'github_token' => '',
            'asset_pattern' => 'CluadeX-win-Portable.zip', 'is_active' => true, 'auto_sync' => true,
        ]);
    }

    public function test_the_pattern_and_the_synced_version_move_from_the_zip_to_the_installer(): void
    {
        $version = $this->syncedWithTheZip('3.0.324', $this->releaseFiles());
        $older = $this->syncedWithTheZip('3.0.322', $this->releaseFiles(), active: false);

        $this->migration()->up();
        $this->migration()->up();   // running it again changes nothing

        $this->assertSame('CluadeX-win-Setup.exe', GithubSetting::where('product_id', $this->product->id)->sole()->asset_pattern);

        $version->refresh();
        $this->assertSame('CluadeX-win-Setup.exe', $version->download_filename);
        $this->assertSame(170866967, (int) $version->file_size);
        $this->assertSame(str_repeat('b', 64), $version->sha256);
        $this->assertSame('https://github.com/xjanova/cluadeX/releases/download/v3.0.324/CluadeX-win-Setup.exe', $version->download_url);
        $this->assertSame('https://api.github.com/repos/xjanova/cluadeX/releases/assets/22', $version->github_release_url);
        // the list of the release's files is left as it was synced
        $this->assertCount(3, $version->assets);

        // a version that is no longer the one offered is not rewritten
        $this->assertSame('CluadeX-win-Portable.zip', $older->refresh()->download_filename);
    }

    public function test_a_version_synced_without_its_file_list_is_left_for_the_next_sync(): void
    {
        $version = $this->syncedWithTheZip('3.0.100', null);

        $this->migration()->up();

        $this->assertSame('CluadeX-win-Setup.exe', GithubSetting::where('product_id', $this->product->id)->sole()->asset_pattern);
        $version->refresh();
        $this->assertSame('CluadeX-win-Portable.zip', $version->download_filename);
        $this->assertSame(str_repeat('a', 64), $version->sha256);
    }

    public function test_an_installer_with_no_checksum_does_not_keep_the_zips(): void
    {
        $files = $this->releaseFiles();
        $files[1]['sha256'] = null;
        $version = $this->syncedWithTheZip('3.0.324', $files);

        $this->migration()->up();

        $version->refresh();
        $this->assertSame('CluadeX-win-Setup.exe', $version->download_filename);
        $this->assertNull($version->sha256);
    }

    public function test_the_page_then_tells_people_to_run_the_installer_not_to_unzip(): void
    {
        $this->syncedWithTheZip('3.0.324', $this->releaseFiles());

        $before = $this->get('/products/' . self::SLUG)->assertOk()->getContent();
        $this->assertStringContainsString('แตกไฟล์แล้วเปิด CluadeX.exe', $before);

        $this->migration()->up();

        $after = $this->get('/products/' . self::SLUG)->assertOk()->getContent();
        $this->assertStringContainsString('เปิดตัวติดตั้ง', $after);
        $this->assertStringNotContainsString('แตกไฟล์แล้วเปิด CluadeX.exe', $after);
        $this->assertStringNotContainsStringIgnoringCase('portable', $after);
    }

    public function test_a_site_without_the_product_is_left_alone(): void
    {
        GithubSetting::where('product_id', $this->product->id)->delete();
        $this->product->forceDelete();

        $this->migration()->up();

        $this->assertSame(0, GithubSetting::where('asset_pattern', 'CluadeX-win-Setup.exe')->count());
    }

    private function migration(): object
    {
        return require database_path(self::MIGRATION);
    }

    /** What a 3.0.324 release holds, as GithubReleaseService::releaseAssets stores it. */
    private function releaseFiles(): array
    {
        $base = 'https://github.com/xjanova/cluadeX/releases/download/v3.0.324/';
        $api = 'https://api.github.com/repos/xjanova/cluadeX/releases/assets/';

        return [
            ['name' => 'CluadeX-win-Portable.zip', 'size' => 165781251, 'sha256' => str_repeat('a', 64), 'download_url' => $base . 'CluadeX-win-Portable.zip', 'api_url' => $api . '21'],
            ['name' => 'CluadeX-win-Setup.exe', 'size' => 170866967, 'sha256' => str_repeat('b', 64), 'download_url' => $base . 'CluadeX-win-Setup.exe', 'api_url' => $api . '22'],
            ['name' => 'releases.win.json', 'size' => 1959, 'sha256' => str_repeat('c', 64), 'download_url' => $base . 'releases.win.json', 'api_url' => $api . '23'],
        ];
    }

    private function syncedWithTheZip(string $version, ?array $assets, bool $active = true): ProductVersion
    {
        return ProductVersion::create([
            'product_id' => $this->product->id,
            'version' => $version,
            'github_release_id' => 1,
            'github_release_url' => 'https://api.github.com/repos/xjanova/cluadeX/releases/assets/21',
            'download_url' => "https://github.com/xjanova/cluadeX/releases/download/v{$version}/CluadeX-win-Portable.zip",
            'download_filename' => 'CluadeX-win-Portable.zip',
            'file_size' => 165781251,
            'sha256' => str_repeat('a', 64),
            'assets' => $assets,
            'is_active' => $active,
            'synced_at' => now(),
        ]);
    }
}

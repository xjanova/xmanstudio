<?php

namespace Tests\Feature;

use App\Http\Controllers\BrainXDownloadController;
use App\Services\BrainXInstaller;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

/**
 * The free BrainX app downloads from xman4289.com/brainx/download: no login and no key, and the
 * browser never meets GitHub — no redirect, and no github.com in a header or on an error page.
 * The owner's rule (2026-09-24): customers download apps only from xman4289.com and must not
 * learn the repository.
 */
class BrainXDownloadTest extends TestCase
{
    use RefreshDatabase;

    private const LATEST = 'https://github.com/xjanova/BrainX/releases/latest/download/BrainX-win-Setup.exe';

    private const FILE = 'https://github.com/xjanova/BrainX/releases/download/v2.0.408/BrainX-win-Setup.exe';

    private const SIZE = 260677138;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        // A test that reaches GitHub for real fails right here instead of pulling 260 MB.
        Http::preventStrayRequests();
    }

    public function test_anyone_gets_the_latest_installer_as_a_file_from_this_site(): void
    {
        $this->fakeGitHub();

        $response = $this->get('/brainx/download');

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/octet-stream')
            ->assertHeader('Content-Disposition', 'attachment; filename="BrainX-win-Setup.exe"')
            ->assertHeader('Content-Length', (string) self::SIZE)
            ->assertHeader('Accept-Ranges', 'none')
            ->assertHeaderMissing('Location');

        $this->assertInstanceOf(StreamedResponse::class, $response->baseResponse);
        $this->assertNoGitHub($response);
    }

    public function test_the_customer_gets_the_bytes_and_the_slot_is_given_back(): void
    {
        $this->stubInstaller(stream: function () {
            echo 'MZ!!';

            return 4;
        });

        $this->assertSame('MZ!!', $this->get('/brainx/download')->streamedContent());

        $this->assertEverySlotIsFree();
    }

    public function test_a_download_that_fails_midway_gives_its_slot_back_too(): void
    {
        $this->stubInstaller(stream: fn () => throw new \RuntimeException('GitHub went away'));
        $level = ob_get_level();

        try {
            $this->get('/brainx/download')->streamedContent();
            $this->fail('The stream should have failed');
        } catch (\RuntimeException) {
            // streamedContent() leaves its capture buffer open when the stream throws.
            while (ob_get_level() > $level) {
                ob_end_clean();
            }
        }

        $this->assertEverySlotIsFree();
    }

    public function test_when_every_slot_is_taken_the_customer_is_asked_to_come_back_shortly(): void
    {
        $this->fakeGitHub();
        $this->withoutMiddleware(ThrottleRequests::class);

        // Streams that have started and not finished: their callbacks have not run yet.
        for ($i = 0; $i < BrainXDownloadController::MAX_CONCURRENT; $i++) {
            $this->get('/brainx/download')->assertOk();
        }

        $response = $this->get('/brainx/download');

        $response->assertStatus(503)
            ->assertHeader('Retry-After', '60')
            ->assertSee('มีคนดาวน์โหลดพร้อมกันเต็มแล้ว');
        $this->assertNoGitHub($response);
    }

    public function test_a_head_request_answers_with_the_headers_and_holds_no_slot(): void
    {
        $this->fakeGitHub();
        $this->withoutMiddleware(ThrottleRequests::class);

        for ($i = 0; $i < BrainXDownloadController::MAX_CONCURRENT + 2; $i++) {
            $response = $this->call('HEAD', '/brainx/download');

            $response->assertOk()
                ->assertHeader('Content-Disposition', 'attachment; filename="BrainX-win-Setup.exe"')
                ->assertHeader('Content-Length', (string) self::SIZE)
                ->assertHeaderMissing('Location');
            $this->assertNoGitHub($response);
        }

        $this->assertEverySlotIsFree();
    }

    public function test_the_release_that_was_found_is_remembered_for_a_while(): void
    {
        $this->fakeGitHub();

        $this->call('HEAD', '/brainx/download')->assertOk();
        $this->call('HEAD', '/brainx/download')->assertOk();

        // One look-up: where "latest" points, then how big that file is.
        Http::assertSentCount(2);
    }

    public function test_when_github_cannot_be_reached_the_customer_gets_a_page_not_a_500(): void
    {
        Http::fake(['*' => Http::failedConnection('cURL error 28: Operation timed out for ' . self::LATEST)]);

        $response = $this->get('/brainx/download');

        $response->assertStatus(503)
            ->assertHeader('Retry-After', '60')
            ->assertSee('ดาวน์โหลดไม่สำเร็จชั่วคราว');
        $this->assertNoGitHub($response);
    }

    public function test_an_app_or_script_asking_for_json_gets_json(): void
    {
        Http::fake(['*' => Http::response('', 500)]);

        $this->getJson('/brainx/download')
            ->assertStatus(503)
            ->assertJson(['success' => false]);
    }

    public function test_a_failure_is_not_remembered(): void
    {
        Http::fake([
            self::LATEST => Http::sequence()
                ->push('', 502)
                ->push('', 302, ['Location' => self::FILE]),
            self::FILE => Http::response('', 200, ['Content-Length' => (string) self::SIZE]),
        ]);

        $this->call('HEAD', '/brainx/download')->assertStatus(503);
        $this->call('HEAD', '/brainx/download')->assertOk();
    }

    public function test_a_latest_release_without_the_installer_is_not_served(): void
    {
        // GitHub answers 404 when the latest release has no BrainX-win-Setup.exe.
        Http::fake([self::LATEST => Http::response('Not Found', 404)]);

        $this->get('/brainx/download')->assertStatus(503);
    }

    public function test_only_a_github_release_file_is_ever_fetched(): void
    {
        // Anything else is refused before it is asked for — preventStrayRequests fails the test otherwise.
        Http::fake([self::LATEST => Http::response('', 302, ['Location' => 'https://files.example.com/BrainX-win-Setup.exe'])]);

        $this->get('/brainx/download')->assertStatus(503);

        Http::assertSentCount(1);
    }

    public function test_one_address_may_start_six_downloads_an_hour(): void
    {
        $this->fakeGitHub();

        for ($i = 0; $i < 6; $i++) {
            $this->call('HEAD', '/brainx/download')->assertOk();
        }

        $this->call('HEAD', '/brainx/download')->assertStatus(429);

        $this->travel(61)->minutes();

        $this->call('HEAD', '/brainx/download')->assertOk();
    }

    public function test_the_sales_page_offers_the_free_app_from_this_site_and_nothing_links_to_github(): void
    {
        $page = File::get(base_path('sites/product.xman4289.com/brainx.html'));

        $this->assertMatchesRegularExpression('#<article class="plan plan-free[^"]*">(?:(?!</article>).)*href="https://xman4289\.com/brainx/download"#s', $page);
        $this->assertSame('/brainx/download', parse_url(route('brainx.download'), PHP_URL_PATH));

        foreach (File::allFiles(base_path('sites')) as $file) {
            if (in_array($file->getExtension(), ['html', 'js', 'css'], true)) {
                $this->assertStringNotContainsStringIgnoringCase('github.com', $file->getContents(), $file->getRelativePathname());
            }
        }
    }

    private function fakeGitHub(): void
    {
        Http::fake([
            self::LATEST => Http::response('', 302, ['Location' => self::FILE]),
            self::FILE => Http::response('', 200, ['Content-Length' => (string) self::SIZE]),
        ]);
    }

    /** @param  \Closure(): int  $stream */
    private function stubInstaller(\Closure $stream): void
    {
        $this->app->instance(BrainXInstaller::class, new class($stream) extends BrainXInstaller
        {
            public function __construct(private \Closure $send) {}

            public function latest(): ?array
            {
                return ['tag' => 'v2.0.408', 'url' => 'https://github.com/xjanova/BrainX/releases/download/v2.0.408/BrainX-win-Setup.exe', 'size' => 4];
            }

            public function stream(array $installer): int
            {
                return ($this->send)();
            }
        });
    }

    /** Every download slot can be taken — none is still held by a request that has finished. */
    private function assertEverySlotIsFree(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class);

        for ($i = 0; $i < BrainXDownloadController::MAX_CONCURRENT; $i++) {
            $this->get('/brainx/download')->assertOk();
        }
    }

    private function assertNoGitHub(TestResponse $response): void
    {
        $this->assertStringNotContainsStringIgnoringCase('github', (string) $response->baseResponse->headers);
        $this->assertStringNotContainsStringIgnoringCase('github', (string) $response->baseResponse->getContent());
    }
}

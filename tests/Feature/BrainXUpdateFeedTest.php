<?php

namespace Tests\Feature;

use App\Services\BrainXUpdateFeed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

/**
 * The BrainX desktop app updates itself from https://xman4289.com/brainx/download/ — a Velopack
 * source that is this site, never GitHub (the owner's rule, 2026-09-24). The feed and the CI's
 * signature over it arrive byte for byte and always from the same release; a package is streamed
 * through this server only when that feed lists it.
 */
class BrainXUpdateFeedTest extends TestCase
{
    use RefreshDatabase;

    private const LATEST_FEED = 'https://github.com/xjanova/BrainX/releases/latest/download/releases.win.json';

    private const LATEST_SIG = 'https://github.com/xjanova/BrainX/releases/latest/download/releases.win.json.sig';

    private const TAG = 'v2.0.492';

    private const OLD_TAG = 'v2.0.491';

    /** What Velopack 1.1.1 asks for: the base URL + releases.{channel}.json + its own query. */
    private const FEED_URL = '/brainx/download/releases.win.json?arch=x64&os=win&rid=win-x64&id=BrainX&localVersion=2.0.491';

    private const SIG_URL = '/brainx/download/releases.win.json.sig';

    private const FULL = 'BrainX-2.0.492-full.nupkg';

    private const DELTA = 'BrainX-2.0.492-delta.nupkg';

    /** Not how json_encode() would write it — spacing, \/ and \u escapes — so any re-encoding shows. */
    private const FEED = <<<'JSON'
        { "Assets": [ {"PackageId":"BrainX","Version":"2.0.492","Type":"Full","FileName":"BrainX-2.0.492-full.nupkg","SHA1":"a9993e364706816aba3e25717850c26c9cd0d89d","SHA256":"ba7816bf8f01cfea414140de5dae2223b00361a396177a9cb410ff61f20015ad","Size":25,"NotesMarkdown":"## ใหม่\n- faster \/ smaller","NotesHTML":"<h2>ใหม่</h2>"},
          {"PackageId":"BrainX","Version":"2.0.492","Type":"Delta","FileName":"BrainX-2.0.492-delta.nupkg","SHA1":"0","SHA256":"0","Size":9},
          {"PackageId":"BrainX","Version":"2.0.492","Type":"Full","FileName":"https://files.example.com/BrainX-2.0.492-mirror.nupkg","Size":25},
          {"PackageId":"BrainX","Version":"2.0.492","Type":"Full","FileName":"../BrainX-2.0.492-up.nupkg","Size":25} ] }
        JSON;

    private const OLD_FEED = '{"Assets":[{"PackageId":"BrainX","Version":"2.0.491","Type":"Full","FileName":"BrainX-2.0.491-full.nupkg","Size":25}]}';

    private const SIG = "MEUCIQDx3Q+u9yJ1n6mXl0p8xS0T2b5wP0ZQy7m0o5w9S8Kj0AIgB2oXg1Kx6Hn3lV/9pQ8zYcR4tE5uW7iO1aS2dF3gH4k=\n";

    private const OLD_SIG = "MEQCIBOldOldOldOldOldOldOldOldOldOldOldOldOldAiBOldOldOldOldOldOldOldOldOldOldOldOldOld==\n";

    /** 25 bytes, as the feed says the full package weighs. */
    private const FULL_BYTES = 'NUPKG:BrainX-2.0.492-full';

    private const SLOTS = 2;

    protected function setUp(): void
    {
        parent::setUp();

        // Anything not faked — the real GitHub included — fails the test instead of going out.
        Http::preventStrayRequests();

        config(['downloads.max_concurrent_streams' => self::SLOTS]);

        $this->assertSame(25, strlen(self::FULL_BYTES));
    }

    // ── the feed and its signature ───────────────────────────────────

    public function test_the_feed_is_served_byte_for_byte_whatever_velopack_puts_in_the_query(): void
    {
        $this->fakeGitHub();

        $response = $this->get(self::FEED_URL);

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/json')
            ->assertHeader('Content-Length', (string) strlen(self::FEED))
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertHeaderMissing('Location');
        $this->assertSame(self::FEED, $response->getContent());
        $this->assertNoGitHub($response);

        // An app's poll starts no session and sets no cookie.
        $this->assertSame([], $response->headers->getCookies());
    }

    public function test_the_signature_is_served_byte_for_byte(): void
    {
        $this->fakeGitHub();

        $response = $this->get(self::SIG_URL);

        $response->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertHeader('Content-Length', (string) strlen(self::SIG));
        $this->assertSame(self::SIG, $response->getContent());
        $this->assertNoGitHub($response);
    }

    public function test_the_feed_and_the_signature_always_come_from_the_same_release(): void
    {
        Http::fake([
            self::LATEST_FEED => Http::sequence()
                ->push('', 302, ['Location' => $this->releaseFile(self::OLD_TAG, 'releases.win.json')])
                ->push('', 302, ['Location' => $this->releaseFile(self::TAG, 'releases.win.json')]),
            // "latest" for the signature already points at a newer release: never asked
            self::LATEST_SIG => Http::response('', 302, ['Location' => $this->releaseFile(self::TAG, 'releases.win.json.sig')]),
        ] + $this->release(self::OLD_TAG, self::OLD_FEED, self::OLD_SIG) + $this->release(self::TAG, self::FEED, self::SIG));

        $this->assertSame(self::OLD_FEED, $this->get(self::FEED_URL)->getContent());
        $this->assertSame(self::OLD_SIG, $this->get(self::SIG_URL)->getContent());

        // Five minutes on, the next release is out: the pair moves together.
        $this->travel(BrainXUpdateFeed::FRESH_MINUTES + 1)->minutes();

        $this->assertSame(self::SIG, $this->get(self::SIG_URL)->getContent());
        $this->assertSame(self::FEED, $this->get(self::FEED_URL)->getContent());

        Http::assertNotSent(fn (ClientRequest $request) => str_contains($request->url(), 'latest/download/releases.win.json.sig'));
        Http::assertSent(fn (ClientRequest $request) => $request->url() === $this->releaseFile(self::OLD_TAG, 'releases.win.json.sig'));
        Http::assertSent(fn (ClientRequest $request) => $request->url() === $this->releaseFile(self::TAG, 'releases.win.json.sig'));
    }

    public function test_the_pair_is_remembered_for_five_minutes(): void
    {
        $this->fakeGitHub();

        for ($i = 0; $i < 3; $i++) {
            $this->get(self::FEED_URL)->assertOk();
            $this->get(self::SIG_URL)->assertOk();
        }

        // One look-up: where "latest" points, then the feed and the signature of that release.
        $this->assertSame(1, $this->sentTo(self::LATEST_FEED));
        $this->assertSame(1, $this->sentTo($this->releaseFile(self::TAG, 'releases.win.json')));
        $this->assertSame(1, $this->sentTo($this->releaseFile(self::TAG, 'releases.win.json.sig')));

        $this->travel(BrainXUpdateFeed::FRESH_MINUTES + 1)->minutes();
        $this->get(self::FEED_URL)->assertOk();

        $this->assertSame(2, $this->sentTo(self::LATEST_FEED));
    }

    public function test_while_github_is_down_the_last_good_pair_is_served(): void
    {
        Http::fake([
            self::LATEST_FEED => Http::sequence()
                ->push('', 302, ['Location' => $this->releaseFile(self::TAG, 'releases.win.json')])
                ->pushFailedConnection('cURL error 28: Operation timed out for ' . self::LATEST_FEED)
                ->push('', 502),
        ] + $this->release(self::TAG, self::FEED, self::SIG));

        $this->get(self::FEED_URL)->assertOk();

        $this->travel(BrainXUpdateFeed::FRESH_MINUTES + 1)->minutes();

        $feed = $this->get(self::FEED_URL);
        $feed->assertOk();
        $this->assertSame(self::FEED, $feed->getContent());
        $this->assertNoGitHub($feed);
        $this->assertSame(self::SIG, $this->get(self::SIG_URL)->getContent());

        // …and every poll does not pile onto GitHub while it is down: it waits a minute
        $this->assertSame(2, $this->sentTo(self::LATEST_FEED));

        $this->travel(BrainXUpdateFeed::BACKOFF_SECONDS + 1)->seconds();
        $this->assertSame(self::FEED, $this->get(self::FEED_URL)->getContent());
        $this->assertSame(3, $this->sentTo(self::LATEST_FEED));
    }

    public function test_with_nothing_fetched_yet_and_github_down_the_app_is_told_to_retry(): void
    {
        Http::fake(['*' => Http::failedConnection('cURL error 28: Operation timed out for ' . self::LATEST_FEED)]);

        foreach ([self::FEED_URL, self::SIG_URL, '/brainx/download/' . self::FULL] as $url) {
            $response = $this->get($url);

            $response->assertStatus(503)
                ->assertHeader('Retry-After', '60')
                ->assertJson(['success' => false]);
            $this->assertNoGitHub($response);
        }
    }

    public function test_a_release_without_its_signature_is_not_served(): void
    {
        Http::fake([
            self::LATEST_FEED => Http::response('', 302, ['Location' => $this->releaseFile(self::TAG, 'releases.win.json')]),
            $this->releaseFile(self::TAG, 'releases.win.json') => Http::response(self::FEED, 200),
            $this->releaseFile(self::TAG, 'releases.win.json.sig') => Http::response('Not Found', 404),
        ]);

        $this->get(self::FEED_URL)->assertStatus(503);
        $this->get(self::SIG_URL)->assertStatus(503);
    }

    public function test_a_feed_that_names_github_is_never_handed_out(): void
    {
        // e.g. release notes ending in GitHub's "Full Changelog" link — signed, so it cannot be edited
        $feed = str_replace('faster', 'Full Changelog: https://github.com/xjanova/BrainX/compare/v2.0.491...v2.0.492', self::FEED);
        $this->fakeGitHub(feed: $feed);

        foreach ([self::FEED_URL, self::SIG_URL] as $url) {
            $response = $this->get($url);

            $response->assertStatus(503);
            $this->assertNoGitHub($response);
        }
    }

    public function test_only_a_github_release_is_ever_asked_for_the_feed(): void
    {
        Http::fake([self::LATEST_FEED => Http::response('', 302, ['Location' => 'https://files.example.com/releases.win.json'])]);

        $this->get(self::FEED_URL)->assertStatus(503);

        Http::assertSentCount(1);
    }

    // ── packages ─────────────────────────────────────────────────────

    public function test_a_package_the_feed_lists_is_streamed_from_that_release_through_this_site(): void
    {
        $this->fakeGitHub();

        $response = $this->get('/brainx/download/' . self::FULL);

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/octet-stream')
            ->assertHeader('Content-Length', '25')
            ->assertHeader('Accept-Ranges', 'none')
            ->assertHeaderMissing('Location');
        $this->assertInstanceOf(StreamedResponse::class, $response->baseResponse);
        $this->assertSame(self::FULL_BYTES, $response->streamedContent());
        $this->assertNoGitHub($response, self::FULL_BYTES);

        Http::assertSent(fn (ClientRequest $request) => $request->url() === $this->releaseFile(self::TAG, self::FULL));

        // and the download gave its place in the pool back
        $this->assertEverySlotIsFree();
    }

    public function test_head_on_a_package_answers_from_the_feed_without_fetching_it(): void
    {
        $this->fakeGitHub();

        $response = $this->call('HEAD', '/brainx/download/' . self::DELTA);

        $response->assertOk()->assertHeader('Content-Length', '9');
        $this->assertNoGitHub($response);
        Http::assertNotSent(fn (ClientRequest $request) => str_contains($request->url(), '.nupkg'));
    }

    public function test_a_name_the_feed_does_not_list_is_404_and_never_fetched(): void
    {
        $this->fakeGitHub();

        foreach ([
            'BrainX-2.0.493-full.nupkg',           // not in this release
            'brainx-2.0.492-full.nupkg',            // not the listed name
            'BrainX-win-Setup.exe',                  // the installer lives at /brainx/download
            'BrainX-2.0.492-mirror.nupkg',          // listed only as an absolute URL
            'BrainX-2.0.492-up.nupkg',              // listed only as a path
            'releases.win.jsonx',
            'RELEASES',
        ] as $name) {
            $response = $this->get('/brainx/download/' . $name);

            $response->assertNotFound()->assertJson(['success' => false]);
            $this->assertNoGitHub($response);
        }

        // only the look-up of the feed itself went out
        Http::assertNotSent(fn (ClientRequest $request) => preg_match('#\.nupkg|\.exe|RELEASES|jsonx#', $request->url()) === 1);
        $this->assertSame(1, $this->sentTo(self::LATEST_FEED));
    }

    public function test_path_tricks_are_404_and_never_fetched(): void
    {
        $this->fakeGitHub();
        $this->withoutMiddleware(ThrottleRequests::class);

        foreach ([
            '/brainx/download/..',
            '/brainx/download/.env',
            '/brainx/download/..%2F..%2F.env',
            '/brainx/download/%2E%2E%2Fweb.php',
            '/brainx/download/..%5C..%5Cweb.config',
            '/brainx/download/x/BrainX-2.0.492-full.nupkg',
            '/brainx/download/BrainX-2.0.492-full.nupkg%00.exe',
            '/brainx/download/BrainX..2.0.492-full.nupkg',
            '/brainx/download/' . str_repeat('a', 201),
        ] as $url) {
            $response = $this->get($url);

            $this->assertSame(404, $response->getStatusCode(), $url);
            $this->assertStringNotContainsStringIgnoringCase('github', (string) $response->baseResponse->headers, $url);
        }

        Http::assertNotSent(fn (ClientRequest $request) => str_contains($request->url(), '.nupkg') || str_contains($request->url(), '.env'));
    }

    public function test_when_every_download_place_is_taken_the_app_is_told_to_retry(): void
    {
        $this->fakeGitHub();

        // places held by other downloads (the installer and every app share one pool)
        for ($i = 0; $i < self::SLOTS; $i++) {
            $this->assertTrue(Cache::lock("downloads:stream-slot:{$i}", 60)->get());
        }

        $response = $this->get('/brainx/download/' . self::FULL);

        $response->assertStatus(503)
            ->assertHeader('Retry-After', '60')
            ->assertJson(['success' => false]);
        $this->assertNoGitHub($response);
        Http::assertNotSent(fn (ClientRequest $request) => str_contains($request->url(), '.nupkg'));
    }

    public function test_a_package_github_will_not_give_is_a_retry_not_a_broken_file(): void
    {
        $this->fakeGitHub(package: Http::response('Not Found', 404));

        $response = $this->get('/brainx/download/' . self::FULL);

        $response->assertStatus(502)->assertHeader('Retry-After', '60')->assertJson(['success' => false]);
        $this->assertNoGitHub($response);
    }

    // ── routes and limits ────────────────────────────────────────────

    public function test_the_installer_route_is_untouched_and_not_shadowed(): void
    {
        $this->fakeGitHub();

        $this->assertSame('/brainx/download', parse_url(route('brainx.download'), PHP_URL_PATH));
        $this->assertSame('/brainx/download/releases.win.json', parse_url(route('brainx.update.feed'), PHP_URL_PATH));
        $this->assertSame('/brainx/download/releases.win.json.sig', parse_url(route('brainx.update.signature'), PHP_URL_PATH));
        $this->assertSame('/brainx/download/' . self::FULL, parse_url(route('brainx.update.package', self::FULL), PHP_URL_PATH));

        // polls never spend the installer's 6 an hour…
        for ($i = 0; $i < 10; $i++) {
            $this->get(self::FEED_URL)->assertOk();
        }

        for ($i = 0; $i < 6; $i++) {
            $this->call('HEAD', '/brainx/download')
                ->assertOk()
                ->assertHeader('Content-Disposition', 'attachment; filename="BrainX-win-Setup.exe"');
        }

        // …and the installer keeps its own limit
        $this->call('HEAD', '/brainx/download')->assertStatus(429);
        $this->get(self::FEED_URL)->assertOk();
    }

    public function test_feed_and_signature_allow_sixty_polls_a_minute_each(): void
    {
        $this->fakeGitHub();

        for ($i = 0; $i < 60; $i++) {
            $this->get(self::FEED_URL)->assertOk();
        }

        $refused = $this->get(self::FEED_URL);
        $refused->assertStatus(429);
        $this->assertNoGitHub($refused);

        $this->get(self::SIG_URL)->assertOk();

        $this->travel(61)->seconds();

        $this->get(self::FEED_URL)->assertOk();
    }

    public function test_one_address_may_start_twenty_package_downloads_an_hour(): void
    {
        $this->fakeGitHub();

        for ($i = 0; $i < 20; $i++) {
            $this->call('HEAD', '/brainx/download/' . self::FULL)->assertOk();
        }

        $refused = $this->call('HEAD', '/brainx/download/' . self::FULL);
        $refused->assertStatus(429);
        $this->assertNoGitHub($refused);

        $this->get(self::FEED_URL)->assertOk();

        $this->travel(61)->minutes();

        $this->call('HEAD', '/brainx/download/' . self::FULL)->assertOk();
    }

    public function test_apache_keeps_the_length_of_the_feed_and_the_packages(): void
    {
        // public_html/.htaccess turns gzip off and trusts PHP's Content-Length for these URLs —
        // the signature covers the exact bytes, and Velopack checks each package's size.
        $lines = file(base_path('public_html/.htaccess'), FILE_IGNORE_NEW_LINES);
        $at = collect($lines)->search(fn ($line) => str_starts_with(trim($line), 'RewriteCond %{THE_REQUEST}'));
        [, , $pattern] = preg_split('/\s+/', trim($lines[$at]));

        foreach ([
            'GET ' . self::FEED_URL,
            'GET /brainx/download/releases.win.json',
            'GET ' . self::SIG_URL,
            'GET /brainx/download/' . self::FULL,
            'HEAD /brainx/download/' . self::DELTA,
            'GET /brainx/download',
        ] as $line) {
            $this->assertMatchesRegularExpression('#' . str_replace('#', '\#', $pattern) . '#i', $line . ' HTTP/1.1');
        }
    }

    // ── helpers ──────────────────────────────────────────────────────

    /** GitHub as it answers: "latest" → the tag's file → (for each file) its CDN copy. */
    private function fakeGitHub(string $feed = self::FEED, string $sig = self::SIG, mixed $package = null): void
    {
        Http::fake([
            self::LATEST_FEED => Http::response('', 302, ['Location' => $this->releaseFile(self::TAG, 'releases.win.json')]),
            // the installer's own look-up, for the tests of /brainx/download
            'https://github.com/xjanova/BrainX/releases/latest/download/BrainX-win-Setup.exe' => Http::response('', 302, [
                'Location' => $this->releaseFile(self::TAG, 'BrainX-win-Setup.exe'),
            ]),
            $this->releaseFile(self::TAG, 'BrainX-win-Setup.exe') => Http::response('', 200, ['Content-Length' => '260677138']),
            $this->releaseFile(self::TAG, self::FULL) => $package ?? Http::response('', 302, [
                'Location' => 'https://release-assets.githubusercontent.com/github-production-release-asset/1/full?sp=r&sig=abc',
            ]),
            'https://release-assets.githubusercontent.com/github-production-release-asset/1/full*' => Http::response(self::FULL_BYTES, 200, [
                'Content-Type' => 'application/octet-stream',
                'Content-Length' => (string) strlen(self::FULL_BYTES),
                'ETag' => '"0x8DCB7C0FFEE"',
                'x-github-request-id' => 'ABCD:1234',
            ]),
        ] + $this->release(self::TAG, $feed, $sig));
    }

    /** A release's feed and signature, each behind a hop to GitHub's file host. */
    private function release(string $tag, string $feed, string $sig): array
    {
        $cdn = "https://release-assets.githubusercontent.com/github-production-release-asset/{$tag}";

        return [
            $this->releaseFile($tag, 'releases.win.json') => Http::response('', 302, ['Location' => "{$cdn}/feed?sig=x"]),
            $this->releaseFile($tag, 'releases.win.json.sig') => Http::response('', 302, ['Location' => "{$cdn}/sig?sig=x"]),
            "{$cdn}/feed*" => Http::response($feed, 200, ['Content-Type' => 'application/octet-stream', 'x-github-request-id' => 'F:1']),
            "{$cdn}/sig*" => Http::response($sig, 200, ['Content-Type' => 'application/octet-stream', 'x-github-request-id' => 'S:1']),
        ];
    }

    private function releaseFile(string $tag, string $file): string
    {
        return "https://github.com/xjanova/BrainX/releases/download/{$tag}/{$file}";
    }

    private function sentTo(string $url): int
    {
        return Http::recorded(fn (ClientRequest $request) => $request->url() === $url)->count();
    }

    /** Every download place can be taken — none is still held by a request that has finished. */
    private function assertEverySlotIsFree(): void
    {
        $locks = [];

        for ($i = 0; $i < self::SLOTS; $i++) {
            $locks[$i] = Cache::lock("downloads:stream-slot:{$i}", 60);
            $this->assertTrue($locks[$i]->get(), "download place {$i} is still held");
        }

        foreach ($locks as $lock) {
            $lock->release();
        }
    }

    private function assertNoGitHub(TestResponse $response, ?string $streamed = null): void
    {
        $this->assertStringNotContainsStringIgnoringCase('github', (string) $response->baseResponse->headers);
        $this->assertStringNotContainsStringIgnoringCase('github', (string) ($streamed ?? $response->baseResponse->getContent()));
    }
}

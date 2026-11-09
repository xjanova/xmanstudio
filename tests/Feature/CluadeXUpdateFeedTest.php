<?php

namespace Tests\Feature;

use App\Services\CluadeXUpdateFeed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

/**
 * The CluadeX desktop app updates itself from https://xman4289.com/cluadex/download/ — never from
 * GitHub (owner 2026-11-09: "อย่ามีอะไร โยงไปถึง github เด็ดขาด ไม่ต้องการให้รู้ repo"). The feed is
 * unsigned, so its release notes are cleaned of every GitHub trace before an app sees them; the
 * packages it lists stream through this site.
 */
class CluadeXUpdateFeedTest extends TestCase
{
    use RefreshDatabase;

    private const LATEST_FEED = 'https://github.com/xjanova/cluadeX/releases/latest/download/releases.win.json';

    private const TAG = 'v3.0.278';

    private const FEED_URL = '/cluadex/download/releases.win.json?arch=x64&os=win&rid=win-x64&id=CluadeX&localVersion=3.0.277';

    private const FULL = 'CluadeX-3.0.278-full.nupkg';

    private const DELTA = 'CluadeX-3.0.278-delta.nupkg';

    private const FULL_BYTES = 'NUPKG:CluadeX-3.0.278-full';

    private const FEED = <<<'JSON'
        {"Assets":[
          {"PackageId":"CluadeX","Version":"3.0.278","Type":"Full","FileName":"CluadeX-3.0.278-full.nupkg","SHA1":"a9993e364706816aba3e25717850c26c9cd0d89d","SHA256":"ba7816bf8f01cfea414140de5dae2223b00361a396177a9cb410ff61f20015ad","Size":26,
           "NotesMarkdown":"### มีอะไรใหม่\n- แจ้งปัญหาจากในแอปได้\n- แก้แอปค้างตอนส่งข้อความ ([#104](https://github.com/xjanova/cluadeX/pull/104))\n\n**Full Changelog**: https://github.com/xjanova/cluadeX/compare/v3.0.277...v3.0.278",
           "NotesHTML":"<a href=\"https://github.com/xjanova/cluadeX\">repo</a>"},
          {"PackageId":"CluadeX","Version":"3.0.278","Type":"Delta","FileName":"CluadeX-3.0.278-delta.nupkg","SHA1":"0","SHA256":"0","Size":9}
        ]}
        JSON;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
        config(['downloads.max_concurrent_streams' => 2]);
        $this->assertSame(26, strlen(self::FULL_BYTES));
    }

    public function test_the_feed_comes_from_this_site_with_every_github_trace_removed_from_the_notes(): void
    {
        $this->fakeGitHub();

        $response = $this->get(self::FEED_URL);

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/json')
            ->assertHeader('Content-Length', (string) strlen($response->getContent()))
            ->assertHeaderMissing('Location');
        $this->assertNoGitHub($response);
        $this->assertSame([], $response->headers->getCookies());

        $feed = json_decode($response->getContent(), true);
        $full = $feed['Assets'][0];

        // what Velopack checks is exactly as the release has it
        $this->assertSame('CluadeX-3.0.278-full.nupkg', $full['FileName']);
        $this->assertSame(26, $full['Size']);
        $this->assertSame('ba7816bf8f01cfea414140de5dae2223b00361a396177a9cb410ff61f20015ad', $full['SHA256']);
        $this->assertSame('a9993e364706816aba3e25717850c26c9cd0d89d', $full['SHA1']);

        // the notes keep what a person wrote, in Thai, and lose the links
        $this->assertStringContainsString('แจ้งปัญหาจากในแอปได้', $full['NotesMarkdown']);
        $this->assertStringContainsString('แก้แอปค้างตอนส่งข้อความ', $full['NotesMarkdown']);
        $this->assertStringNotContainsStringIgnoringCase('changelog', $full['NotesMarkdown']);
        $this->assertSame('', $full['NotesHTML']);
    }

    public function test_a_package_the_feed_lists_is_streamed_through_this_site(): void
    {
        $this->fakeGitHub();

        $response = $this->get('/cluadex/download/' . self::FULL);

        $response->assertOk()
            ->assertHeader('Content-Length', '26')
            ->assertHeaderMissing('Location');
        $this->assertInstanceOf(StreamedResponse::class, $response->baseResponse);
        $this->assertSame(self::FULL_BYTES, $response->streamedContent());
        $this->assertNoGitHub($response, self::FULL_BYTES);
    }

    public function test_head_on_a_package_answers_from_the_feed_without_fetching_it(): void
    {
        $this->fakeGitHub();

        $this->call('HEAD', '/cluadex/download/' . self::DELTA)
            ->assertOk()
            ->assertHeader('Content-Length', '9');

        Http::assertNotSent(fn (ClientRequest $request) => str_contains($request->url(), '.nupkg'));
    }

    public function test_a_name_the_feed_does_not_list_is_404_and_never_fetched(): void
    {
        $this->fakeGitHub();

        foreach (['CluadeX-9.9.9-full.nupkg', 'CluadeX-win-Setup.exe', 'releases.win.json.sig'] as $file) {
            $response = $this->get('/cluadex/download/' . $file);
            $response->assertNotFound();
            $this->assertNoGitHub($response);
        }

        Http::assertNotSent(fn (ClientRequest $request) => str_contains($request->url(), '9.9.9') || str_contains($request->url(), 'Setup.exe'));
    }

    public function test_with_github_down_and_nothing_fetched_the_app_is_told_to_retry(): void
    {
        Http::fake(['*' => Http::failedConnection('cURL error 28: Operation timed out for ' . self::LATEST_FEED)]);

        foreach ([self::FEED_URL, '/cluadex/download/' . self::FULL] as $url) {
            $response = $this->get($url);
            $response->assertStatus(503)->assertHeader('Retry-After', '60');
            $this->assertNoGitHub($response);
        }
    }

    public function test_the_feed_is_remembered_and_served_while_github_is_down(): void
    {
        $this->fakeGitHub();
        $this->get(self::FEED_URL)->assertOk();

        $this->travel(CluadeXUpdateFeed::FRESH_MINUTES + 1)->minutes();
        Http::fake(['*' => Http::failedConnection('down')]);

        $this->get(self::FEED_URL)->assertOk();
    }

    public function test_a_feed_with_an_absolute_github_package_url_is_never_served(): void
    {
        $feed = str_replace('"FileName":"CluadeX-3.0.278-delta.nupkg"', '"FileName":"https://github.com/xjanova/cluadeX/releases/download/v3.0.278/x.nupkg"', self::FEED);
        $this->fakeGitHub($feed);

        $response = $this->get(self::FEED_URL);
        $response->assertStatus(503);
        $this->assertNoGitHub($response);
    }

    public function test_the_installer_page_is_still_its_own_route(): void
    {
        $this->assertSame(url('/cluadex/download'), route('cluadex.download'));
        $this->assertSame(url('/cluadex/download/releases.win.json'), route('cluadex.update.feed'));
    }

    // ── helpers ──────────────────────────────────────────────────────

    private function fakeGitHub(string $feed = self::FEED): void
    {
        $cdn = 'https://release-assets.githubusercontent.com/github-production-release-asset/' . self::TAG;

        Http::fake([
            self::LATEST_FEED => Http::response('', 302, ['Location' => $this->releaseFile('releases.win.json')]),
            $this->releaseFile('releases.win.json') => Http::response('', 302, ['Location' => "{$cdn}/feed?sig=x"]),
            "{$cdn}/feed*" => Http::response($feed, 200, ['Content-Type' => 'application/octet-stream']),
            $this->releaseFile(self::FULL) => Http::response('', 302, ['Location' => "{$cdn}/full?sp=r&sig=abc"]),
            "{$cdn}/full*" => Http::response(self::FULL_BYTES, 200, [
                'Content-Type' => 'application/octet-stream',
                'Content-Length' => (string) strlen(self::FULL_BYTES),
                'x-github-request-id' => 'ABCD:1234',
            ]),
        ]);
    }

    private function releaseFile(string $file): string
    {
        return 'https://github.com/xjanova/cluadeX/releases/download/' . self::TAG . '/' . $file;
    }

    private function assertNoGitHub(TestResponse $response, ?string $streamed = null): void
    {
        $this->assertStringNotContainsStringIgnoringCase('github', (string) $response->baseResponse->headers);
        $this->assertStringNotContainsStringIgnoringCase('github', (string) ($streamed ?? $response->baseResponse->getContent()));
        $this->assertStringNotContainsStringIgnoringCase('xjanova', (string) ($streamed ?? $response->baseResponse->getContent()));
    }
}

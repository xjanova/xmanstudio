<?php

namespace App\Services\AiChat;

use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * What every public page of the site says, so the assistant can answer from
 * the pages themselves: their titles, headings and text.
 *
 * A lot of what the site tells visitors lives in Blade templates, not in the
 * database — the landing pages of the apps, the plans on their pricing pages,
 * the about, team and terms pages. The only way to know it as a visitor sees
 * it is to read the rendered page, so `ai-chat:index` renders each public page
 * inside this process (as a guest, with a throw-away session) and keeps what
 * PageText finds in storage/app/ai-chat/site-index.json.
 *
 * The scheduler runs it every ten minutes with --if-stale: it only reads the
 * site again when a deploy changed the release number or the set of pages,
 * or when the copy is older than MAX_AGE_HOURS. A deploy clears the cache, not
 * this file, so the assistant never starts from nothing.
 */
class SiteIndex
{
    public const FILE = 'app/ai-chat/site-index.json';

    public const MAX_AGE_HOURS = 6;

    /** Not "bot", "crawler" or "spider": UniverseHome would serve it the classic page. */
    public const USER_AGENT = 'XMANStudio-NovaReader/1.0';

    /** @var array{built_at?: string, fingerprint?: string, pages?: array<string, array<string, mixed>>, not_pages?: array<int, string>}|null */
    private ?array $data = null;

    /** @var array<string, int>|null how many pages carry each description */
    private ?array $descriptionCounts = null;

    /** @var array<string, array{title: string, headings: string, body: string, all: string}>|null */
    private ?array $haystacks = null;

    public function __construct(private SiteMap $siteMap) {}

    public function file(): string
    {
        return storage_path(self::FILE);
    }

    /** @return array<string, array{path: string, route: string, title: string, description: string, h1: string, headings: array<int, string>, text: string}> */
    public function pages(): array
    {
        return $this->load()['pages'] ?? [];
    }

    /** @return array{path: string, route: string, title: string, description: string, h1: string, headings: array<int, string>, text: string}|null */
    public function page(string $path): ?array
    {
        $path = '/' . trim($path, '/');

        return $this->pages()[$path] ?? null;
    }

    /**
     * A page's own description, or '' when it is the site-wide default that
     * many pages fall back to — that one says nothing about the page.
     *
     * @param  array{description?: string}|null  $page
     */
    public function descriptionOf(?array $page): string
    {
        $description = trim((string) ($page['description'] ?? ''));

        return $description === '' || $this->isBoilerplate($description) ? '' : $description;
    }

    /** Text that three or more pages share word for word: a default, not a description. */
    public function isBoilerplate(string $description): bool
    {
        $this->descriptionCounts ??= array_count_values(array_filter(array_map(
            fn (array $page) => trim((string) $page['description']),
            $this->pages()
        )));

        return ($this->descriptionCounts[trim($description)] ?? 0) >= 3;
    }

    /** Whether the last read found this address to be something other than a page. */
    public function isNotAPage(string $path): bool
    {
        return in_array($path, $this->load()['not_pages'] ?? [], true);
    }

    /**
     * What to call a page in a list: its <title> without the site name tacked
     * on ("Tping — พิมพ์อัตโนมัติ | XMAN Studio"), else its first heading.
     *
     * @param  array{title?: string, h1?: string}  $page
     */
    public static function label(array $page, string $fallback = ''): string
    {
        $title = (string) preg_replace('/\s*[|–—-]\s*[^|–—-]*XMAN\s*Studio[^|–—-]*$/iu', '', (string) ($page['title'] ?? ''));
        $title = trim($title);

        if ($title === '' || preg_match('/^XMAN\s*Studio$/iu', $title)) {
            $title = (string) ($page['h1'] ?? '');
        }

        return PageText::clean($title !== '' ? $title : $fallback, 80);
    }

    public function builtAt(): ?Carbon
    {
        $at = $this->load()['built_at'] ?? null;

        return $at ? Carbon::parse($at) : null;
    }

    public function isStale(): bool
    {
        $data = $this->load();
        $builtAt = $this->builtAt();

        return $data === []
            || ($data['fingerprint'] ?? null) !== $this->fingerprint()
            || $builtAt === null
            || $builtAt->lt(now()->subHours(self::MAX_AGE_HOURS));
    }

    /**
     * Changes when a deploy changes what the site serves: the release number
     * (bumped by every release) and the set of public pages.
     */
    public function fingerprint(): string
    {
        $version = is_file(base_path('VERSION')) ? trim((string) file_get_contents(base_path('VERSION'))) : '';

        return md5($version . '|' . implode(',', array_keys($this->siteMap->crawlable())));
    }

    /**
     * Read every public page again and replace the stored copy.
     *
     * @return array{pages: int, skipped: array<string, string>, failed: array<string, string>}
     */
    public function build(): array
    {
        $kernel = app(HttpKernel::class);
        $base = rtrim((string) config('app.url'), '/');
        $pages = [];
        $skipped = [];
        $failed = [];

        // Each page gets a throw-away session (none of them lands in Redis)
        // and is read as a guest, whatever this process was doing before.
        $sessionDriver = config('session.driver');
        config(['session.driver' => 'array']);
        Auth::forgetGuards();

        try {
            foreach ($this->siteMap->crawlable() as $path => $routeName) {
                try {
                    $request = Request::create($base . $path, 'GET', server: [
                        'HTTP_USER_AGENT' => self::USER_AGENT,
                        'HTTP_ACCEPT' => 'text/html',
                    ]);
                    $response = $kernel->handle($request);
                    $kernel->terminate($request, $response);

                    $type = (string) $response->headers->get('Content-Type', '');
                    if ($response->getStatusCode() !== 200 || ! str_contains($type, 'text/html')) {
                        $skipped[$path] = $response->getStatusCode() . ' ' . strtok($type, ';');

                        continue;
                    }

                    $text = PageText::fromHtml((string) $response->getContent());
                    if ($text['title'] === '' && $text['text'] === '') {
                        $skipped[$path] = 'empty';

                        continue;
                    }

                    $pages[$path] = ['path' => $path, 'route' => $routeName] + $text;
                } catch (Throwable $e) {
                    $failed[$path] = class_basename($e);
                }
            }
        } finally {
            config(['session.driver' => $sessionDriver]);
            Auth::forgetGuards();
        }

        $this->write([
            'built_at' => now()->toIso8601String(),
            'fingerprint' => $this->fingerprint(),
            'pages' => $pages,
            // Read and found not to be a page (a redirect, JSON, an error): left out of the
            // site map, unlike a route added since, which is listed before it is read.
            'not_pages' => array_keys($skipped + $failed),
        ]);

        if ($failed !== []) {
            Log::warning('[AI chat] site index: pages that could not be read', $failed);
        }

        return ['pages' => count($pages), 'skipped' => $skipped, 'failed' => $failed];
    }

    /**
     * The words that point somewhere in particular. A word on a third of the
     * site ("ราคา", "xman") names no page: "อันนี้ราคาเท่าไหร่" is about the
     * open page, not whichever page says ราคา most. Without an index to judge
     * by, every word is kept.
     *
     * @param  array<int, string>  $keywords
     * @return array<int, string>
     */
    public function specific(array $keywords): array
    {
        $haystacks = $this->haystacks();
        $total = count($haystacks);

        if ($total < 6) {
            return $keywords;
        }

        return array_values(array_filter($keywords, function (string $word) use ($haystacks, $total) {
            $holders = 0;
            foreach ($haystacks as $h) {
                if (str_contains($h['all'], $word)) {
                    $holders++;
                }
            }

            return $holders <= $total / 3;
        }));
    }

    /**
     * The pages that are about these words, best first.
     *
     * A page must carry one of the words in its title or a heading — a word
     * that only turns up in the body ("ราคาคุ้มค่า" on the rental page) is not
     * what the page is about. Each word counts for more the fewer pages use it,
     * and a page in the same part of the site as the one the visitor has open
     * (/tping/install-guide beside /tping/pricing) comes first on a tie.
     *
     * @param  array<int, string>  $keywords
     * @param  array<int, string>  $except  paths to leave out (the page the visitor is on)
     * @return array<int, array<string, mixed>>
     */
    public function search(array $keywords, int $limit = 2, array $except = [], ?string $near = null): array
    {
        $haystacks = array_diff_key($this->haystacks(), array_flip($except));

        if ($keywords === [] || $haystacks === []) {
            return [];
        }

        $total = count($haystacks);
        $section = $near !== null ? strtok(trim($near, '/'), '/') : false;
        $scores = [];
        $named = [];

        foreach ($keywords as $word) {
            $holders = array_filter($haystacks, fn (array $h) => str_contains($h['all'], $word));
            if ($holders === []) {
                continue;
            }

            $rarity = log(1 + $total / count($holders));

            foreach ($holders as $path => $h) {
                $inTitle = str_contains($h['title'], $word);
                $inHeadings = str_contains($h['headings'], $word);
                $named[$path] = ($named[$path] ?? false) || $inTitle || $inHeadings;

                $weight = ($inTitle ? 3 : 0) + ($inHeadings ? 2 : 0) + min(3, substr_count($h['body'], $word));
                $scores[$path] = ($scores[$path] ?? 0) + $weight * $rarity;
            }
        }

        foreach ($scores as $path => $score) {
            if (empty($named[$path])) {
                unset($scores[$path]);
            } elseif ($section !== false && $section !== '' && strtok(trim($path, '/'), '/') === $section) {
                $scores[$path] = $score * 1.5;
            }
        }

        arsort($scores);

        $pages = $this->pages();
        $best = [];
        foreach (array_slice($scores, 0, $limit, true) as $path => $score) {
            if ($score > 0) {
                $best[] = $pages[$path];
            }
        }

        return $best;
    }

    /** @return array<string, array{title: string, headings: string, body: string, all: string}> lowercased text of each page, by path */
    private function haystacks(): array
    {
        if ($this->haystacks !== null) {
            return $this->haystacks;
        }

        $haystacks = [];
        foreach ($this->pages() as $path => $page) {
            $title = mb_strtolower($page['title'] . ' ' . $page['h1'] . ' ' . $path);
            $headings = mb_strtolower(implode(' ', $page['headings']));
            $body = mb_strtolower($page['description'] . ' ' . $page['text']);
            $haystacks[$path] = ['title' => $title, 'headings' => $headings, 'body' => $body, 'all' => $title . ' ' . $headings . ' ' . $body];
        }

        return $this->haystacks = $haystacks;
    }

    /** @return array<string, mixed> */
    private function load(): array
    {
        if ($this->data !== null) {
            return $this->data;
        }

        try {
            $raw = is_file($this->file()) ? file_get_contents($this->file()) : false;
            $data = $raw !== false ? json_decode($raw, true) : null;
        } catch (Throwable) {
            $data = null;
        }

        return $this->data = is_array($data) ? $data : [];
    }

    /** @param  array<string, mixed>  $data */
    private function write(array $data): void
    {
        File::ensureDirectoryExists(dirname($this->file()));

        // Write beside, then swap: a chat reading mid-write sees the old copy or the new one, never half.
        $temp = $this->file() . '.' . getmypid() . '.tmp';
        File::put($temp, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE));
        File::move($temp, $this->file());

        $this->data = $data;
        $this->descriptionCounts = null;
        $this->haystacks = null;
    }
}

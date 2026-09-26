<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Services\ThemeService;
use App\Support\UniverseHome;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Vite;
use Tests\TestCase;

/**
 * The 3D XMAN Universe home page, and who gets it instead of the theme's.
 *
 * The universe is served to ordinary browsers; the theme's own home page goes
 * to anyone who asked for it (?view=classic, or the cookie the browser check
 * and the "Classic view" links write), to crawlers, and to everyone once an
 * admin switches it off. Whatever the classic page links to, the universe must
 * link to as well — it is the front page most visitors see.
 */
class UniverseHomeTest extends TestCase
{
    use RefreshDatabase;

    private const UNIVERSE_MARK = 'id="xu-config"';

    private const DESKTOP_CHROME = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36';

    protected function setUp(): void
    {
        parent::setUp();

        // Settings and the theme are cached under fixed keys that outlive a test's database.
        Cache::flush();
        ThemeService::setTheme('nova');
        Cache::forget('site_theme');

        // Without an admin the home page redirects to the setup wizard.
        User::factory()->create(['role' => 'admin']);
    }

    private function home(string $query = '', ?string $userAgent = self::DESKTOP_CHROME)
    {
        return $this->withHeader('User-Agent', $userAgent)->get('/' . $query);
    }

    public function test_an_ordinary_browser_gets_the_universe(): void
    {
        $this->home()
            ->assertOk()
            ->assertSee(self::UNIVERSE_MARK, false)
            ->assertSee('data-station="core"', false)
            // The browser check runs before anything else in <head>.
            ->assertSee('__xuFallback', false)
            ->assertDontSee('nova-body', false);
    }

    public function test_view_classic_gets_the_theme_home_page(): void
    {
        $this->home('?view=classic')
            ->assertOk()
            ->assertDontSee(self::UNIVERSE_MARK, false)
            ->assertSee('nova-body', false);
    }

    public function test_a_visitor_who_chose_classic_keeps_getting_it(): void
    {
        foreach ([UniverseHome::MODE_CLASSIC, UniverseHome::MODE_LITE] as $mode) {
            $this->withUnencryptedCookie(UniverseHome::COOKIE, $mode)
                ->withHeader('User-Agent', self::DESKTOP_CHROME)
                ->get('/')
                ->assertOk()
                ->assertDontSee(self::UNIVERSE_MARK, false)
                ->assertSee('nova-body', false);
        }
    }

    public function test_a_visitor_who_chose_the_universe_gets_it(): void
    {
        $this->withUnencryptedCookie(UniverseHome::COOKIE, UniverseHome::MODE_UNIVERSE)
            ->withHeader('User-Agent', self::DESKTOP_CHROME)
            ->get('/')
            ->assertOk()
            ->assertSee(self::UNIVERSE_MARK, false);
    }

    public function test_crawlers_index_the_theme_home_page(): void
    {
        $bots = [
            'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
            'Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)',
            'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)',
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/140.0.0.0 Safari/537.36',
        ];
        foreach ($bots as $ua) {
            $this->home('', $ua)->assertOk()->assertDontSee(self::UNIVERSE_MARK, false);
        }
    }

    public function test_a_phone_with_bot_in_its_model_name_is_not_a_crawler(): void
    {
        $this->assertFalse(UniverseHome::isCrawler('Mozilla/5.0 (Linux; Android 12; CUBOT X50) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Mobile Safari/537.36'));
        $this->assertTrue(UniverseHome::isCrawler('Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)'));
    }

    public function test_until_the_build_has_the_universe_everyone_gets_the_theme_home_page(): void
    {
        // A deploy puts the new views live minutes before `npm run build` writes the
        // manifest that knows the universe's entries, and a failed build keeps the old
        // manifest for good. @vite would throw on every hit: the home page must fall back.
        $manifest = json_decode(file_get_contents(public_path('build/manifest.json')), true);
        unset($manifest['resources/css/universe.css'], $manifest['resources/js/universe/main.js']);
        $name = 'manifest-before-universe.json';
        file_put_contents(public_path('build/' . $name), json_encode($manifest));

        try {
            Vite::useManifestFilename($name);

            $this->home()
                ->assertOk()
                ->assertDontSee(self::UNIVERSE_MARK, false)
                ->assertSee('nova-body', false);
        } finally {
            @unlink(public_path('build/' . $name));
        }
    }

    public function test_switched_off_everyone_gets_the_theme_home_page_without_the_3d_pill(): void
    {
        UniverseHome::setEnabled(false);

        $this->home()
            ->assertOk()
            ->assertDontSee(self::UNIVERSE_MARK, false)
            ->assertSee('nova-body', false)
            ->assertDontSee('id="xu-switch"', false);
    }

    public function test_the_theme_home_page_offers_the_way_back_to_3d(): void
    {
        $this->home('?view=classic')->assertSee('id="xu-switch"', false);

        // Every theme's home page carries it, not just Nova's.
        foreach (['classic', 'premium', 'retro'] as $theme) {
            ThemeService::setTheme($theme);
            Cache::forget('site_theme');
            $this->home('?view=classic')->assertOk()->assertSee('id="xu-switch"', false);
        }
    }

    public function test_the_universe_links_everywhere_the_classic_home_page_does(): void
    {
        $this->assertLinkParity();
    }

    public function test_the_universe_links_everywhere_the_classic_home_page_does_when_signed_in(): void
    {
        $this->actingAs(User::factory()->create());
        $this->assertLinkParity();
    }

    public function test_with_the_ai_assistant_on_the_guide_takes_questions(): void
    {
        Setting::setValue('ai_chat_enabled', '1', 'boolean', 'ai');
        Setting::setValue('ai_bot_name', 'น้องเอ็กซ์', 'string', 'ai');

        $html = $this->home()
            ->assertOk()
            ->assertSee('id="xu-guide-ask"', false)
            ->assertSee('id="xu-chat"', false)
            ->assertSee('น้องเอ็กซ์')
            ->getContent();

        $this->assertSame(route('public.ai-chat'), $this->config($html)['chat']);
    }

    public function test_with_the_ai_assistant_off_the_guide_only_talks(): void
    {
        $html = $this->home()
            ->assertOk()
            ->assertDontSee('id="xu-guide-ask"', false)
            ->assertDontSee('id="xu-chat"', false)
            ->getContent();

        $config = $this->config($html);
        $this->assertNull($config['chat']);
        $this->assertSame(UniverseHome::classicUrl(), $config['classicUrl']);
    }

    public function test_an_admin_can_switch_the_universe_off_and_on(): void
    {
        $admin = User::where('role', 'admin')->first();

        $this->actingAs($admin)
            ->put(route('admin.theme.universe.update'), ['home_universe' => '0'])
            ->assertRedirect(route('admin.theme.index'));
        $this->assertFalse(UniverseHome::enabled());

        $this->actingAs($admin)
            ->put(route('admin.theme.universe.update'), ['home_universe' => '1'])
            ->assertRedirect(route('admin.theme.index'));
        $this->assertTrue(UniverseHome::enabled());

        $this->actingAs($admin)->get(route('admin.theme.index'))->assertOk()->assertSee('XMAN Universe');
    }

    public function test_a_customer_cannot_switch_the_universe(): void
    {
        $this->actingAs(User::factory()->create())
            ->put(route('admin.theme.universe.update'), ['home_universe' => '0']);

        $this->assertTrue(UniverseHome::enabled());
    }

    private function assertLinkParity(): void
    {
        $classic = $this->links($this->home('?view=classic')->assertOk()->getContent());
        $universe = $this->links($this->home()->assertOk()->getContent());

        $this->assertNotEmpty($classic);
        $missing = array_values(array_diff($classic, $universe));
        $this->assertSame([], $missing, 'The universe home is missing links the classic home has: ' . implode(', ', $missing));
    }

    /** @return array<string, mixed> what the page hands main.js (#xu-config) */
    private function config(string $html): array
    {
        $this->assertSame(1, preg_match('#<script type="application/json" id="xu-config">(.*?)</script>#s', $html, $m));

        return json_decode($m[1], true, 512, JSON_THROW_ON_ERROR);
    }

    /** @return array<int, string> every navigable href on the page */
    private function links(string $html): array
    {
        preg_match_all('/<a\s[^>]*href="([^"]+)"/i', $html, $m);

        return array_values(array_unique(array_filter(
            array_map(fn ($href) => html_entity_decode($href), $m[1]),
            fn ($href) => ! str_starts_with($href, '#') && ! str_contains($href, 'view=classic'),
        )));
    }
}

<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Services\ThemeService;
use App\Support\ContactLinks;
use App\Support\HomeContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * ลิงก์ในเมนูทุกชุดต้องพาไปหน้าที่ตรงกับชื่อ และ XgamesHub อยู่ในเมนูหลักทุกชุด (เจ้าของ 2026-10-05)
 *
 *   — เมนูหลักมีสองชุด: เมนู mega/มือถือ/footer ของหน้าเว็บ และ HomeContent::menu() ของ Nova กับวงแหวน 3D
 *   — ลิงก์ในเมนูไม่วิ่งผ่าน redirect เก่า (/support → /quote) และไม่มี "#" ที่กดแล้วไม่ไปไหน
 *   — ปุ่มโซเชียลใน footer ใช้ช่องทางที่ตั้งไว้ที่ /admin/contact-settings ไม่เขียนตายตัว
 */
class MenuLinksTest extends TestCase
{
    use RefreshDatabase;

    private const GAMES = 'https://games.example.test';

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->withoutVite();
        Http::preventStrayRequests();
        config(['app.xgameshub_url' => self::GAMES]);

        // With no admin at all, every page redirects to the setup wizard.
        User::factory()->create(['role' => 'admin']);
    }

    public function test_xgameshub_is_in_every_menu_of_the_classic_theme(): void
    {
        ThemeService::setTheme('classic');
        $html = $this->get('/services')->assertOk()->getContent();

        $this->assertContains(self::GAMES, $this->hrefs($html, "//*[contains(@class,'xm-mega__grid')]//a"), 'mega menu');
        $this->assertContains(self::GAMES, $this->hrefs($html, "//*[@id='mobileMenu']//a"), 'mobile menu');
        $this->assertContains(self::GAMES, $this->hrefs($html, '//footer//a'), 'footer');
    }

    public function test_xgameshub_is_in_every_menu_of_the_premium_theme(): void
    {
        ThemeService::setTheme('premium');
        $html = $this->get('/services')->assertOk()->getContent();

        $this->assertContains(self::GAMES, $this->hrefs($html, "//a[contains(@class,'premium-nav-link')]"), 'top bar');
        $this->assertContains(self::GAMES, $this->hrefs($html, "//*[@id='mobileMenu']//a"), 'mobile menu');
        $this->assertContains(self::GAMES, $this->hrefs($html, '//footer//a'), 'footer');
    }

    public function test_xgameshub_is_in_the_nova_and_retro_home_menus(): void
    {
        ThemeService::setTheme('nova');
        $nova = $this->get('/?view=classic')->assertOk()->getContent();
        $this->assertContains(self::GAMES, $this->hrefs($nova, "//nav[@id='nova-nav']//a"), 'Nova star menu');
        $this->assertContains(self::GAMES, $this->hrefs($nova, "//footer[contains(@class,'nova-footer')]//a"), 'Nova footer');

        ThemeService::setTheme('retro');
        $retro = $this->get('/?view=classic')->assertOk()->getContent();
        $this->assertContains(self::GAMES, $this->hrefs($retro, "//*[contains(@class,'retro-nav-links')]//a"), 'Retro top bar');
    }

    public function test_xgameshub_is_on_the_universe_ring_with_its_own_tile(): void
    {
        $item = collect(HomeContent::menu())->firstWhere('href', self::GAMES);

        $this->assertNotNull($item, 'HomeContent::menu() feeds the Nova star menu, the 3D ring and the chat bot');
        $this->assertFileExists(public_path('artwork/menu/labelled/' . $item['art'] . '.webp'));

        $ring = view('partials.universe.menu', ['classicUrl' => '/?view=classic'])->render();
        $this->assertContains(self::GAMES, $this->hrefs($ring, "//*[@id='xu-ring']//a"));
    }

    public function test_no_menu_link_goes_through_an_old_redirect_or_nowhere(): void
    {
        $pages = [['classic', '/services'], ['premium', '/services'], ['nova', '/?view=classic'], ['retro', '/?view=classic']];

        foreach ($pages as [$theme, $page]) {
            ThemeService::setTheme($theme);
            foreach ([null, User::factory()->create()] as $user) {
                $this->app['auth']->forgetGuards();
                $html = ($user ? $this->actingAs($user) : $this)->get($page)->assertOk()->getContent();
                $hrefs = $this->hrefs($html, "//nav//a | //header//a | //footer//a | //*[@id='mobileMenu']//a | //*[@id='mobileBottomNav']//a");

                $this->assertNotEmpty($hrefs);
                foreach ($hrefs as $href) {
                    $path = parse_url($href, PHP_URL_PATH);
                    $this->assertNotContains(trim($href), ['', '#'], "$theme $page: a menu link that goes nowhere");
                    $this->assertNotContains($path, ['/support', '/support/tracking'], "$theme $page: $href only redirects");
                }
            }
        }
    }

    public function test_the_user_menu_opens_the_admin_dashboard_and_the_customer_licence_pages(): void
    {
        ThemeService::setTheme('classic');
        $html = $this->actingAs(User::factory()->create(['role' => 'admin']))->get('/services')->assertOk()->getContent();

        $this->assertContains(route('admin.dashboard'), $this->hrefs($html, '//nav//a'));
        $this->assertStringNotContainsString('href="/admin/rentals"', $html);
    }

    public function test_footer_social_buttons_follow_the_contact_settings(): void
    {
        Setting::setValue('contact_facebook_url', 'https://www.facebook.com/example-page');
        Setting::setValue('contact_youtube_url', 'https://www.youtube.com/@ExampleStudio');
        Setting::setValue('contact_line_id', '@abc123');

        foreach (['classic', 'premium'] as $theme) {
            ThemeService::setTheme($theme);
            $footer = $this->hrefs($this->get('/services')->assertOk()->getContent(), '//footer//a');

            $this->assertContains('https://www.facebook.com/example-page', $footer, $theme);
            $this->assertContains('https://www.youtube.com/@ExampleStudio', $footer, $theme);
            $this->assertContains('https://line.me/R/ti/p/@abc123', $footer, $theme);
            $this->assertNotContains('https://youtube.com/@metal-xproject', $footer, "$theme: the studio channel, not the music one");
        }
    }

    public function test_a_channel_without_an_address_is_left_out(): void
    {
        Setting::setValue('contact_facebook_url', 'javascript:alert(1)');
        Setting::setValue('contact_youtube_url', '');
        Setting::setValue('contact_line_id', '');
        Setting::setValue('contact_line_url', '');

        $this->assertSame([], ContactLinks::socials());
    }

    public function test_a_line_link_comes_from_the_url_or_the_id(): void
    {
        Setting::setValue('contact_line_id', 'someone');
        $this->assertSame('https://line.me/ti/p/~someone', ContactLinks::lineUrl());

        Setting::setValue('contact_line_url', 'https://lin.ee/abcd');
        $this->assertSame('https://lin.ee/abcd', ContactLinks::lineUrl());
    }

    // ── helpers ───────────────────────────────────────────────────────

    /** @return array<int, string> */
    private function hrefs(string $html, string $xpath): array
    {
        $dom = new \DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8"?>' . $html);
        libxml_clear_errors();

        $hrefs = [];
        foreach ((new \DOMXPath($dom))->query($xpath) as $a) {
            if ($a instanceof \DOMElement && $a->hasAttribute('href')) {
                $hrefs[] = $a->getAttribute('href');
            }
        }

        return $hrefs;
    }
}

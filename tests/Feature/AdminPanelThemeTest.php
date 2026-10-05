<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Services\ThemeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * หลังบ้านแอดมินใช้ธีมที่แอดมินเลือกเอง ไม่เปลี่ยนตามธีมของเว็บไซต์ (เจ้าของ 2026-10-05)
 *
 *   — ค่าเริ่มต้นคือ Classic ไม่ว่าเว็บจะใช้ธีมไหน · เดิมเว็บเป็น Premium หลังบ้านแอดมินก็มืดตาม
 *     ส่วน Nova/Retro ก็ติด Classic เปลี่ยนไม่ได้
 *   — เลือกได้ที่ /admin/theme ระหว่าง Classic กับ Premium (สองธีมที่มี layout แอดมิน)
 *   — แยกจากธีมหลังบ้านสมาชิก ตั้งอันหนึ่งแล้วอีกอันไม่เปลี่ยนตาม
 */
class AdminPanelThemeTest extends TestCase
{
    use RefreshDatabase;

    private const CLASSIC = '<body class="bg-gray-100 overflow-hidden">';

    private const PREMIUM = '<body class="bg-gray-900 overflow-hidden">';

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->withoutVite();
        Http::preventStrayRequests();
    }

    public static function siteThemes(): array
    {
        return array_map(fn (string $theme) => [$theme], array_combine(
            array_keys(ThemeService::THEMES),
            array_keys(ThemeService::THEMES),
        ));
    }

    /** @dataProvider siteThemes */
    public function test_the_admin_panel_is_classic_by_default_whatever_the_site_theme(string $siteTheme): void
    {
        ThemeService::setTheme($siteTheme);

        $this->assertAdminPanelIs(self::CLASSIC);
    }

    public function test_an_admin_can_put_the_admin_panel_on_premium_without_touching_the_other_themes(): void
    {
        ThemeService::setTheme('nova');

        $this->actingAs($this->admin())
            ->put(route('admin.theme.admin.update'), ['admin_theme' => 'premium'])
            ->assertRedirect(route('admin.theme.index'))
            ->assertSessionHas('success');

        $this->assertSame('premium', Setting::getValue('admin_theme'));
        $this->assertSame('nova', ThemeService::getSiteDefaultTheme());
        $this->assertSame('classic', ThemeService::getCustomerTheme());
        $this->assertAdminPanelIs(self::PREMIUM);
    }

    public function test_changing_the_site_theme_leaves_the_admin_panel_alone(): void
    {
        ThemeService::setAdminTheme('premium');

        $this->actingAs($this->admin())
            ->put(route('admin.theme.update'), ['theme' => 'classic'])
            ->assertRedirect(route('admin.theme.index'));

        $this->assertSame('classic', ThemeService::getSiteDefaultTheme());
        $this->assertAdminPanelIs(self::PREMIUM);
    }

    public function test_changing_the_member_area_theme_leaves_the_admin_panel_alone(): void
    {
        $this->actingAs($this->admin())
            ->put(route('admin.theme.customer.update'), ['customer_theme' => 'premium'])
            ->assertRedirect(route('admin.theme.index'));

        $this->assertNull(Setting::getValue('admin_theme'));
        $this->assertAdminPanelIs(self::CLASSIC);
    }

    public function test_a_theme_without_an_admin_layout_is_refused(): void
    {
        $this->actingAs($this->admin())
            ->from(route('admin.theme.index'))
            ->put(route('admin.theme.admin.update'), ['admin_theme' => 'nova'])
            ->assertSessionHasErrors('admin_theme');

        $this->assertNull(Setting::getValue('admin_theme'));
        $this->assertAdminPanelIs(self::CLASSIC);
    }

    public function test_only_an_admin_can_change_it(): void
    {
        $this->actingAs(User::factory()->create())
            ->put(route('admin.theme.admin.update'), ['admin_theme' => 'premium'])
            ->assertForbidden();

        $this->assertNull(Setting::getValue('admin_theme'));
    }

    public function test_a_stored_theme_that_is_no_longer_offered_falls_back_to_classic(): void
    {
        // แก้ในฐานข้อมูลตรง ๆ หรือค่าที่เหลือจากธีมที่ถูกถอดออก — หน้าต้องไม่พัง
        Setting::setValue('admin_theme', 'nova');
        Cache::forget('admin_theme');

        $this->assertSame('classic', ThemeService::getAdminTheme());
        $this->assertAdminPanelIs(self::CLASSIC);
    }

    public function test_the_admin_page_shows_the_admin_panel_choice(): void
    {
        ThemeService::setAdminTheme('premium');

        $html = $this->actingAs($this->admin())->get(route('admin.theme.index'))->assertOk()->getContent();

        $this->assertStringContainsString('ธีมหลังบ้านแอดมิน', $html);
        $this->assertStringContainsString('action="' . route('admin.theme.admin.update') . '"', $html);
        $this->assertMatchesRegularExpression('#name="admin_theme" value="premium"\s+class="sr-only peer"\s+checked>#', $html);
        $this->assertDoesNotMatchRegularExpression('#name="admin_theme" value="classic"\s+class="sr-only peer"\s+checked>#', $html);
        // หมายเหตุเก่าบอกว่าธีมเว็บไซต์มีผลกับหน้าแอดมินด้วย — ตอนนี้แยกกันแล้ว
        $this->assertStringNotContainsString('มีผลกับหน้าเว็บและหน้าแอดมิน', $html);
    }

    // ── helpers ───────────────────────────────────────────────────────

    /** หน้าหนึ่งในหลังบ้านแอดมิน ใช้ layout ตามที่คาด — ดูจาก <body> ที่ต่างกันของสอง layout */
    private function assertAdminPanelIs(string $body): void
    {
        $html = $this->actingAs($this->admin())
            ->get(route('admin.theme.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString($body, $html);
        $this->assertStringNotContainsString($body === self::CLASSIC ? self::PREMIUM : self::CLASSIC, $html);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }
}

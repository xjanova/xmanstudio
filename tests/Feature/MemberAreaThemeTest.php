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
 * หลังบ้านสมาชิก (/my-account) ใช้ธีมที่แอดมินตั้งแยกไว้ ไม่เปลี่ยนตามธีมของเว็บไซต์ (เจ้าของ 2026-09-24)
 *
 *   — ค่าเริ่มต้นคือ Classic ไม่ว่าเว็บจะใช้ธีมไหน · เดิมเว็บเป็น Premium เมื่อไหร่ หลังบ้านของลูกค้าทุกคนก็มืดตาม
 *   — แอดมินเลือกได้เองที่ /admin/theme ระหว่าง Classic กับ Premium (สองธีมที่มี layout หลังบ้านสมาชิก)
 *   — ทุกคนเห็นเหมือนกัน ไม่มีธีมรายคน
 */
class MemberAreaThemeTest extends TestCase
{
    use RefreshDatabase;

    private const CLASSIC = '<body class="bg-gray-50 overflow-hidden">';

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
    public function test_the_member_area_is_classic_by_default_whatever_the_site_theme(string $siteTheme): void
    {
        ThemeService::setTheme($siteTheme);

        $this->assertMemberAreaIs(self::CLASSIC);
    }

    public function test_an_admin_can_put_the_member_area_on_premium_without_touching_the_site_theme(): void
    {
        ThemeService::setTheme('nova');

        $this->actingAs($this->admin())
            ->put(route('admin.theme.customer.update'), ['customer_theme' => 'premium'])
            ->assertRedirect(route('admin.theme.index'))
            ->assertSessionHas('success');

        $this->assertSame('premium', Setting::getValue('customer_theme'));
        $this->assertSame('nova', ThemeService::getSiteDefaultTheme());
        $this->assertMemberAreaIs(self::PREMIUM);
    }

    public function test_changing_the_site_theme_leaves_the_member_area_alone(): void
    {
        ThemeService::setCustomerTheme('premium');

        $this->actingAs($this->admin())
            ->put(route('admin.theme.update'), ['theme' => 'classic'])
            ->assertRedirect(route('admin.theme.index'));

        $this->assertSame('classic', ThemeService::getSiteDefaultTheme());
        $this->assertMemberAreaIs(self::PREMIUM);
    }

    public function test_a_theme_without_a_member_area_layout_is_refused(): void
    {
        $this->actingAs($this->admin())
            ->from(route('admin.theme.index'))
            ->put(route('admin.theme.customer.update'), ['customer_theme' => 'nova'])
            ->assertSessionHasErrors('customer_theme');

        $this->assertNull(Setting::getValue('customer_theme'));
        $this->assertMemberAreaIs(self::CLASSIC);
    }

    public function test_only_an_admin_can_change_it(): void
    {
        $this->actingAs(User::factory()->create())
            ->put(route('admin.theme.customer.update'), ['customer_theme' => 'premium'])
            ->assertForbidden();

        $this->assertNull(Setting::getValue('customer_theme'));
    }

    public function test_a_stored_theme_that_is_no_longer_offered_falls_back_to_classic(): void
    {
        // แก้ในฐานข้อมูลตรง ๆ หรือค่าที่เหลือจากธีมที่ถูกถอดออก — หน้าต้องไม่พัง
        Setting::setValue('customer_theme', 'nova');
        Cache::forget('customer_theme');

        $this->assertSame('classic', ThemeService::getCustomerTheme());
        $this->assertMemberAreaIs(self::CLASSIC);
    }

    public function test_the_admin_page_shows_the_member_area_choice_and_no_per_user_theme(): void
    {
        ThemeService::setCustomerTheme('premium');

        $html = $this->actingAs($this->admin())->get(route('admin.theme.index'))->assertOk()->getContent();

        $this->assertStringContainsString('ธีมหลังบ้านสมาชิก', $html);
        $this->assertStringContainsString('action="' . route('admin.theme.customer.update') . '"', $html);
        $this->assertMatchesRegularExpression('#name="customer_theme" value="premium"\s+class="sr-only peer"\s+checked>#', $html);
        $this->assertDoesNotMatchRegularExpression('#name="customer_theme" value="classic"\s+class="sr-only peer"\s+checked>#', $html);
        // หมายเหตุเก่าบอกว่าผู้ใช้ตั้งธีมส่วนตัวได้ — ระบบถอดธีมรายคนออกไปแล้ว
        $this->assertStringNotContainsString('ผู้ใช้ที่ตั้งค่าธีมส่วนตัวแล้วจะเห็นธีมของตัวเอง', $html);
    }

    // ── helpers ───────────────────────────────────────────────────────

    /** หน้าหนึ่งในหลังบ้านสมาชิก ใช้ layout ตามที่คาด — ดูจาก <body> ที่ต่างกันของสอง layout */
    private function assertMemberAreaIs(string $body): void
    {
        $html = $this->actingAs(User::factory()->create())
            ->get(route('customer.downloads'))
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

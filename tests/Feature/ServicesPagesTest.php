<?php

namespace Tests\Feature;

use App\Models\QuotationCategory;
use App\Models\QuotationOption;
use App\Models\Service;
use App\Models\User;
use App\Support\Quotation\Pricing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * หน้าบริการทั้งสามหน้า (/services, /services/{หมวด}/{บริการ}, /services/{slug}) แสดงทุกอย่างจาก DB
 *
 *   — ราคาบนหน้าคือราคาหลังหักส่วนลดจาก Pricing::saleDiscount() ตัวเดียวกับที่ตัวคิดราคาคิดเงิน
 *   — หมวดแบบ addon (ของแถมในตัวคิดราคา) ไม่ขึ้นเป็นบริการ
 *   — /services/{slug} เคยตอบ 500 เพราะเรียก route('services') ที่ไม่มีอยู่ (ชื่อจริงคือ services.index)
 */
class ServicesPagesTest extends TestCase
{
    use RefreshDatabase;

    private QuotationCategory $category;

    private QuotationOption $option;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Http::preventStrayRequests();
        // With no admin at all, every page redirects to the setup wizard.
        User::factory()->create(['role' => 'admin']);

        $this->category = QuotationCategory::create([
            'key' => 'web_development', 'type' => QuotationCategory::TYPE_SERVICE, 'name' => 'Web Development', 'name_th' => 'พัฒนาเว็บไซต์',
            'icon' => '🌐', 'description_th' => 'เว็บไซต์ทุกรูปแบบ', 'order' => 1, 'is_active' => true,
        ]);
        $this->option = QuotationOption::create([
            'quotation_category_id' => $this->category->id, 'key' => 'web_landing', 'name' => 'Landing Page', 'name_th' => 'แลนดิ้งเพจ',
            'description_th' => 'หน้าเดียวจบ', 'price' => 30000, 'order' => 1, 'is_active' => true,
            'features_th' => ['ออกแบบตามแบรนด์', 'รองรับมือถือ'], 'steps_th' => ['คุยความต้องการ', 'ส่งมอบงาน'],
            'long_description_th' => 'รายละเอียดยาวของแลนดิ้งเพจ',
        ]);
        QuotationOption::create([
            'quotation_category_id' => $this->category->id, 'key' => 'web_corporate', 'name' => 'Corporate Site', 'name_th' => 'เว็บองค์กร',
            'price' => 80000, 'order' => 2, 'is_active' => true,
        ]);
        QuotationCategory::create([
            'key' => 'test_addon', 'type' => QuotationCategory::TYPE_ADDON, 'name' => 'Hosting', 'name_th' => 'โฮสติ้งของแถม', 'order' => 2, 'is_active' => true,
        ]);
    }

    private function salePrice(float $price): string
    {
        return number_format($price * (1 - Pricing::saleDiscount('web_development')), 0);
    }

    public function test_the_services_list_shows_each_service_category_its_options_and_sale_prices(): void
    {
        $html = $this->get('/services')->assertOk()->getContent();

        $this->assertStringContainsString('พัฒนาเว็บไซต์', $html);
        $this->assertStringContainsString('แลนดิ้งเพจ', $html);
        $this->assertStringContainsString('id="category-web_development"', $html);
        $this->assertStringContainsString(route('service.detail', ['web_development', 'web_landing']), $html);
        $this->assertStringContainsString($this->salePrice(30000) . '</span>', $html);
        $this->assertStringContainsString('SALE ' . (int) round(Pricing::saleDiscount('web_development') * 100) . '%', $html);
        $this->assertStringNotContainsString('โฮสติ้งของแถม', $html, 'add-on groups are not services');
        $this->assertStringContainsString('"@type":"ItemList"', $html);
    }

    public function test_the_services_list_says_so_when_there_is_no_service(): void
    {
        QuotationCategory::query()->update(['is_active' => false]);

        $this->get('/services')->assertOk()->assertSee('ยังไม่มีบริการ');
    }

    public function test_a_service_page_shows_its_price_details_features_steps_and_related_services(): void
    {
        $this->get(route('service.detail', ['web_development', 'web_landing']))
            ->assertOk()
            ->assertSee('แลนดิ้งเพจ')
            ->assertSee('Landing Page')
            ->assertSee(number_format(30000) . ' ฿')
            ->assertSee($this->salePrice(30000))
            ->assertSee('รายละเอียดยาวของแลนดิ้งเพจ')
            ->assertSee('รองรับมือถือ')
            ->assertSee('ส่งมอบงาน')
            ->assertSee('เว็บองค์กร')
            ->assertSee(route('quote.index'), false)
            ->assertSee(route('services.index') . '#category-web_development', false)
            ->assertSee('"@type":"Service"', false);
    }

    public function test_an_unknown_or_inactive_service_is_not_found(): void
    {
        $this->get('/services/web_development/nope')->assertNotFound();
        $this->get('/services/test_addon/anything')->assertNotFound();

        $this->option->update(['is_active' => false]);
        $this->get(route('service.detail', ['web_development', 'web_landing']))->assertNotFound();
    }

    public function test_a_service_from_the_services_table_renders_with_its_starting_price(): void
    {
        Service::create([
            'slug' => 'test-iot', 'name' => 'IoT Solutions', 'name_th' => 'โซลูชัน IoT', 'description' => 'IoT', 'description_th' => 'ระบบ IoT ครบวงจร',
            'icon' => '⚡', 'features_th' => ['เซ็นเซอร์', 'แดชบอร์ด'], 'starting_price' => 45000, 'price_unit' => 'โปรเจกต์', 'is_active' => true,
        ]);
        Service::create(['slug' => 'test-web', 'name' => 'Web', 'name_th' => 'เว็บไซต์', 'description' => 'Web', 'is_active' => true]);

        $this->get(route('services.show', 'test-iot'))
            ->assertOk()
            ->assertSee('โซลูชัน IoT')
            ->assertSee('ระบบ IoT ครบวงจร')
            ->assertSee('45,000')
            ->assertSee('บาท/โปรเจกต์')
            ->assertSee('แดชบอร์ด')
            ->assertSee('ขอใบเสนอราคา')
            ->assertSee(route('services.show', 'test-web'), false);
    }

    public function test_a_coming_soon_service_cannot_be_ordered_yet(): void
    {
        Service::create([
            'slug' => 'test-ai', 'name' => 'AI', 'description' => 'AI', 'is_active' => true,
            'is_coming_soon' => true, 'coming_soon_until' => now()->addMonth(),
        ]);

        $this->get(route('services.show', 'test-ai'))
            ->assertOk()
            ->assertSee('Coming Soon')
            ->assertSee('ติดต่อสอบถาม')
            ->assertDontSee('ขอใบเสนอราคา');
    }
}

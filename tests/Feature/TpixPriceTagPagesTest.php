<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\FakesTpixChain;
use Tests\TestCase;

/**
 * Every page that prints a product, plan or package price prints its TPIX price next to it (owner,
 * 2026-10-13: "โชว์ในหน้าสินค้าทุกประเภท ทุกแพลตฟอร์ม") — and still renders when there is none.
 */
class TpixPriceTagPagesTest extends TestCase
{
    use FakesTpixChain;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Cache::flush();
        $this->withoutVite();
        $this->fakeTpixChain();
        $this->enableTpixPayments();
        $this->seedTwap();

        // Without an admin every page redirects to the setup wizard
        User::factory()->create(['role' => 'admin']);

        // Product rows production has that a fresh database may not (some migrations only update them)
        $category = Category::firstOrCreate(['slug' => 'software'], ['name' => 'Software', 'description' => 'x']);
        $rows = [
            'winx-tools' => 'WinX Tools',
            'cluadex-ai-coding-assistant' => 'CluadeX',
            'sms-payment-checker' => 'SMS Payment Checker',
            'skidrow-killer' => 'Skidrow Killer',
            'brainx' => 'BrainX',
            'chanthra-studio' => 'Chanthra Studio',
        ];
        foreach ($rows as $slug => $name) {
            Product::firstOrCreate(['slug' => $slug], [
                'category_id' => $category->id, 'name' => $name, 'description' => 'x', 'price' => 990,
                'requires_license' => true, 'is_active' => true,
            ])->update(['is_active' => true, 'is_coming_soon' => false]);
        }
    }

    /** @return array<string, array{0: string}> */
    public static function pricedPages(): array
    {
        return [
            'products' => ['/products'],
            'Anti X' => ['/products/anti-x'],
            'LocalVPN product' => ['/products/localvpn'],
            'WinX Tools' => ['/products/winx-tools'],
            'CluadeX product' => ['/products/cluadex-ai-coding-assistant'],
            'SMS Payment Checker product' => ['/products/sms-payment-checker'],
            'Skidrow Killer product' => ['/products/skidrow-killer'],
            'BrainX product' => ['/products/brainx'],
            'Tping' => ['/tping'],
            'Tping pricing' => ['/tping/pricing'],
            'SMS Checker' => ['/smschecker'],
            'SMS Checker pricing' => ['/smschecker/pricing'],
            'LocalVPN pricing' => ['/localvpn/pricing'],
            'AutoTradeX pricing' => ['/autotradex/pricing'],
            'CluadeX pricing' => ['/cluadex/pricing'],
            'Chanthra Studio' => ['/chanthra-studio'],
            'Chanthra Studio pricing' => ['/chanthra-studio/pricing'],
            'DGX Spark campaign' => ['/dgx-spark'],
            'xDreamer AI credits' => ['/xdreamer'],
        ];
    }

    #[DataProvider('pricedPages')]
    public function test_a_priced_page_shows_the_tpix_price(string $path): void
    {
        $this->get($path)->assertOk()->assertSee('data-tpix-price', false)->assertSee('TPIX');
    }

    #[DataProvider('pricedPages')]
    public function test_a_priced_page_still_renders_without_a_tpix_price(string $path): void
    {
        $this->rpcDown = true;

        $this->get($path)->assertOk()->assertDontSee('data-tpix-price', false);
    }

    /**
     * Pages whose prices come from data a fresh database does not have (TLDs, VPS plans, AI credit
     * packages, rental packages): with TPIX on they must still render.
     *
     * @return array<string, array{0: string}>
     */
    public static function otherPricedPages(): array
    {
        return [
            'home' => ['/'],
            'services' => ['/services'],
            'domains' => ['/domains'],
            'domain pricing' => ['/domains/pricing'],
            'VPS' => ['/vps'],
            'rentals' => ['/rental'],
            'cart' => ['/cart'],
        ];
    }

    #[DataProvider('otherPricedPages')]
    public function test_a_page_priced_from_other_data_renders_with_tpix_on(string $path): void
    {
        $this->assertLessThan(500, $this->get($path)->getStatusCode());
    }
}

<?php

namespace Tests\Feature;

use App\Models\ProjectOrder;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * /quote/track/search needs no login, so it may only list what the searcher
 * can prove is theirs. It used to LIKE-match the quote number, which made
 * "QT-2" a list of every customer's name, service and total.
 */
class QuoteTrackingSearchPrivacyTest extends TestCase
{
    use RefreshDatabase;

    private function quotation(array $overrides = []): Quotation
    {
        return Quotation::create(array_merge([
            'quote_number' => 'QT-20260213-AB12',
            'customer_name' => 'สมชาย ใจดี',
            'customer_email' => 'somchai@example.co.th',
            'customer_phone' => '081-234-5678',
            'service_type' => 'web',
            'service_name' => 'เว็บไซต์องค์กรลับ',
            'service_options' => [['name' => 'หน้าแรก', 'price' => 100000]],
            'subtotal' => 100000,
            'vat' => 7000,
            'grand_total' => 107000,
            'status' => 'sent',
            'valid_until' => now()->addDays(20),
            'public_token' => Quotation::newPublicToken(),
            'version' => 1,
        ], $overrides));
    }

    private function search(string $query)
    {
        return $this->get(route('quote.track.search', ['query' => $query]));
    }

    public function test_part_of_a_quote_number_lists_nothing(): void
    {
        $quotation = $this->quotation();
        ProjectOrder::create([
            'quotation_id' => $quotation->id,
            'project_number' => 'PRJ-20260213-CD34',
            'project_name' => 'โครงการของสมชาย',
            'project_type' => 'web',
            'total_price' => 107000,
            'status' => 'in_progress',
        ]);

        foreach (['QT-2', 'QT-20260213', 'AB12', 'PRJ-2026'] as $partial) {
            $this->search($partial)
                ->assertOk()
                ->assertViewHas('quotations', fn ($q) => $q->isEmpty())
                ->assertViewHas('projects', fn ($p) => $p->isEmpty())
                ->assertDontSee('สมชาย ใจดี', false)
                ->assertDontSee('โครงการของสมชาย', false);
        }
    }

    public function test_the_whole_quote_number_finds_it_in_any_case(): void
    {
        $quotation = $this->quotation();

        $this->search('qt-20260213-ab12')
            ->assertOk()
            ->assertViewHas('quotations', fn ($q) => $q->pluck('id')->all() === [$quotation->id])
            ->assertSee('QT-20260213-AB12', false)
            ->assertSee('เว็บไซต์องค์กรลับ', false);
    }

    public function test_the_whole_quote_number_also_brings_its_project(): void
    {
        $quotation = $this->quotation();
        $project = ProjectOrder::create([
            'quotation_id' => $quotation->id,
            'project_name' => 'โครงการของสมชาย',
            'project_type' => 'web',
            'total_price' => 107000,
            'status' => 'in_progress',
        ]);

        $this->search('QT-20260213-AB12')
            ->assertViewHas('projects', fn ($p) => $p->pluck('id')->all() === [$project->id]);
    }

    public function test_the_whole_project_number_finds_only_that_project(): void
    {
        $mine = ProjectOrder::create([
            'project_number' => 'PRJ-20260213-CD34',
            'project_name' => 'โครงการของฉัน',
            'project_type' => 'web',
            'total_price' => 1000,
            'status' => 'in_progress',
        ]);
        ProjectOrder::create([
            'project_number' => 'PRJ-20260213-CD35',
            'project_name' => 'โครงการของคนอื่น',
            'project_type' => 'web',
            'total_price' => 1000,
            'status' => 'in_progress',
        ]);

        $this->search('prj-20260213-cd34')
            ->assertOk()
            ->assertViewHas('projects', fn ($p) => $p->pluck('id')->all() === [$mine->id])
            ->assertDontSee('โครงการของคนอื่น', false);
    }

    public function test_an_email_without_signing_in_goes_to_login_and_lists_nothing(): void
    {
        $this->quotation();

        $this->search('somchai@example.co.th')
            ->assertRedirect(route('login'));
    }

    public function test_someone_elses_email_lists_nothing_even_when_signed_in(): void
    {
        $this->quotation();
        $stranger = User::factory()->create(['email' => 'stranger@example.com']);

        $this->actingAs($stranger)
            ->search('somchai@example.co.th')
            ->assertOk()
            ->assertViewHas('quotations', fn ($q) => $q->isEmpty())
            ->assertViewHas('projects', fn ($p) => $p->isEmpty())
            ->assertDontSee('สมชาย ใจดี', false);
    }

    public function test_your_own_email_lists_your_quotations_when_signed_in(): void
    {
        $owner = User::factory()->create(['email' => 'somchai@example.co.th']);
        $mine = $this->quotation();
        $this->quotation(['quote_number' => 'QT-20260213-ZZ99', 'customer_email' => 'other@example.com', 'customer_name' => 'คนอื่น']);

        $this->actingAs($owner)
            ->search('SOMCHAI@example.co.th')
            ->assertOk()
            ->assertViewHas('quotations', fn ($q) => $q->pluck('id')->all() === [$mine->id])
            ->assertDontSee('QT-20260213-ZZ99', false);
    }

    public function test_an_unverified_account_under_a_customers_email_does_not_see_their_quotations(): void
    {
        // Registration does not confirm the address, so anyone can open an
        // account under a customer's email before the customer does.
        $impostor = User::factory()->unverified()->create(['email' => 'somchai@example.co.th']);
        $this->quotation();

        $this->actingAs($impostor)
            ->search('somchai@example.co.th')
            ->assertOk()
            ->assertViewHas('quotations', fn ($q) => $q->isEmpty())
            ->assertDontSee('สมชาย ใจดี', false);
    }

    public function test_the_search_is_rate_limited(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->search("QT-20260213-X{$i}00")->assertOk();
        }

        $this->search('QT-20260213-X999')->assertStatus(429);
    }

    public function test_the_public_tracking_page_takes_the_whole_project_number_in_any_case(): void
    {
        ProjectOrder::create([
            'project_number' => 'PRJ-20260213-CD34',
            'project_name' => 'โครงการของฉัน',
            'project_type' => 'web',
            'total_price' => 1000,
            'status' => 'in_progress',
        ]);

        $this->get(route('tracking.search', ['query' => 'prj-20260213-cd34']))
            ->assertOk()
            ->assertViewHas('project', fn ($p) => $p?->project_number === 'PRJ-20260213-CD34');

        $this->get(route('tracking.search', ['query' => 'PRJ-20260213']))
            ->assertOk()
            ->assertViewHas('project', null);
    }
}

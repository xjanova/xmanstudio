<?php

namespace Tests\Feature;

use App\Mail\QuotationMail;
use App\Models\Quotation;
use App\Support\Quotation\VatMode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * The whole path a quotation now takes: built on /quote, e-mailed, opened by
 * the customer, answered.
 *
 * None of the last three existed before — sent_at, viewed_at and accepted_at
 * were columns nothing wrote to.
 */
class QuotationFlowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    protected function payload(array $overrides = []): array
    {
        return array_merge([
            'customer_name' => 'สมชาย ใจดี',
            'customer_company' => 'บริษัท ตัวอย่าง จำกัด',
            'customer_email' => 'somchai@example.co.th',
            'customer_phone' => '081-234-5678',
            'service_type' => 'web',
            'service_options' => ['web_landing'],
            'timeline' => 'normal',
            'action_type' => 'quotation',
        ], $overrides);
    }

    protected function submit(array $overrides = []): Quotation
    {
        $this->postJson('/quote/submit', $this->payload($overrides))->assertOk();

        return Quotation::latest('id')->firstOrFail();
    }

    public function test_the_builder_page_loads_with_outcomes_to_choose_from(): void
    {
        $this->get('/quote')
            ->assertOk()
            ->assertSee('สั่งงาน', false)
            ->assertSee('quoteBuilder', false);
    }

    public function test_the_old_address_still_works_and_points_at_the_new_one(): void
    {
        // Links in e-mail we already sent, and whatever Google holds.
        $this->get('/support')->assertRedirect('/quote')->assertStatus(301);
        $this->get('/support/tracking')->assertRedirect('/quote/track')->assertStatus(301);
    }

    public function test_the_services_json_endpoint_answers_at_both_addresses(): void
    {
        // A JSON caller may not follow a redirect, so the old path answers in
        // place rather than 301-ing.
        $this->getJson('/quotation/services')->assertOk()->assertJsonStructure(['services']);
        $this->getJson('/quote/services')->assertOk()->assertJsonStructure(['services']);
    }

    public function test_submitting_stores_the_tax_treatment_and_a_private_token(): void
    {
        Mail::fake();

        $quotation = $this->submit(['vat_mode' => VatMode::INCLUSIVE, 'withholding_pct' => 3]);

        $this->assertSame(VatMode::INCLUSIVE, $quotation->vat_mode);
        $this->assertSame('7.00', $quotation->vat_rate);
        $this->assertNotNull($quotation->amount_before_vat);
        $this->assertSame('3.00', $quotation->withholding_pct);

        // Under "price includes tax" the quoted figure IS the total.
        $this->assertEqualsWithDelta(
            (float) $quotation->amount_before_vat + (float) $quotation->vat,
            (float) $quotation->grand_total,
            0.001,
            'base + vat must equal the total that was quoted'
        );

        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $quotation->public_token);
        $this->assertNotSame($quotation->quote_number, $quotation->public_token);
    }

    public function test_an_absent_vat_mode_keeps_the_old_behaviour(): void
    {
        Mail::fake();

        $quotation = $this->submit();

        $this->assertSame(VatMode::EXCLUSIVE, $quotation->vat_mode);
    }

    public function test_the_customer_is_e_mailed_the_quotation(): void
    {
        Mail::fake();

        $quotation = $this->submit();

        Mail::assertSent(QuotationMail::class, fn (QuotationMail $m) => $m->hasTo('somchai@example.co.th')
            && $m->quotation->is($quotation));
    }

    public function test_the_public_token_is_never_exposed_in_json(): void
    {
        Mail::fake();

        $quotation = $this->submit();

        // Anyone holding this can read the customer's prices.
        $this->assertArrayNotHasKey('public_token', $quotation->toArray());
    }

    public function test_opening_the_document_records_the_first_view_only(): void
    {
        Mail::fake();
        $quotation = $this->submit();

        $this->get('/quote/d/' . $quotation->public_token)->assertOk()->assertSee($quotation->quote_number);

        $first = $quotation->fresh()->viewed_at;
        $this->assertNotNull($first);

        $this->travel(5)->minutes();
        $this->get('/quote/d/' . $quotation->public_token)->assertOk();

        $this->assertEquals($first, $quotation->fresh()->viewed_at, 're-reading must not move the timestamp');
    }

    public function test_a_token_that_is_not_ours_is_a_404(): void
    {
        $this->get('/quote/d/' . str_repeat('a', 64))->assertNotFound();
        // Wrong shape entirely — rejected before it reaches the query.
        $this->get('/quote/d/short')->assertNotFound();
        $this->get('/quote/d/' . str_repeat('Z', 64))->assertNotFound();
    }

    public function test_the_customer_can_accept(): void
    {
        Mail::fake();
        $quotation = $this->submit();

        $this->post('/quote/d/' . $quotation->public_token . '/respond', ['action' => 'accept'])
            ->assertSessionHas('quote_success');

        $quotation->refresh();
        $this->assertSame('accepted', $quotation->status);
        $this->assertNotNull($quotation->accepted_at);
    }

    public function test_asking_to_renegotiate_keeps_what_the_customer_wrote(): void
    {
        Mail::fake();
        $quotation = $this->submit();

        $this->post('/quote/d/' . $quotation->public_token . '/respond', [
            'action' => 'negotiate',
            'message' => 'ขอตัด SEO ออกก่อน แล้วงบอยู่ที่ 180,000 ได้ไหมครับ',
        ])->assertSessionHas('quote_success');

        $this->assertStringContainsString('180,000', $quotation->fresh()->customer_notes);
    }

    public function test_renegotiating_without_saying_why_is_refused(): void
    {
        Mail::fake();
        $quotation = $this->submit();

        // A bare "let's negotiate" leaves the team nothing to act on.
        $this->post('/quote/d/' . $quotation->public_token . '/respond', ['action' => 'negotiate'])
            ->assertSessionHas('quote_error');

        $this->assertNull($quotation->fresh()->customer_notes);
    }

    public function test_a_quotation_cannot_be_answered_twice(): void
    {
        Mail::fake();
        $quotation = $this->submit();
        $token = $quotation->public_token;

        $this->post("/quote/d/{$token}/respond", ['action' => 'accept'])->assertSessionHas('quote_success');
        $this->post("/quote/d/{$token}/respond", ['action' => 'decline'])->assertSessionHas('quote_error');

        $this->assertSame('accepted', $quotation->fresh()->status, 'the first answer stands');
    }

    public function test_an_expired_quotation_can_no_longer_be_accepted(): void
    {
        Mail::fake();
        $quotation = $this->submit();
        $quotation->update(['valid_until' => now()->subDay()]);

        $this->post('/quote/d/' . $quotation->public_token . '/respond', ['action' => 'accept'])
            ->assertSessionHas('quote_error');

        $this->assertNotSame('accepted', $quotation->fresh()->status);
    }

    public function test_the_document_pdf_downloads(): void
    {
        Mail::fake();
        $quotation = $this->submit();

        $response = $this->get('/quote/d/' . $quotation->public_token . '/pdf');

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
        // dompdf's download() returns a plain response, not a streamed one.
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_a_mail_failure_does_not_lose_the_quotation(): void
    {
        // The row and the team's Telegram card are already there; the worst
        // case is a customer who opens the link instead of an attachment.
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('smtp down'));

        $response = $this->postJson('/quote/submit', $this->payload());

        $response->assertOk()->assertJson(['success' => true, 'mailed' => false]);
        $this->assertSame(1, Quotation::count());
    }
}

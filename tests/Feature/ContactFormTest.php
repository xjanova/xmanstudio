<?php

namespace Tests\Feature;

use App\Mail\ContactMessageMail;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ContactFormTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, string>
     */
    protected function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'สมชาย ใจดี',
            'email' => 'somchai@example.com',
            'phone' => '099-000-0000',
            'subject' => 'สอบถามการทำเว็บไซต์',
            'message' => 'อยากได้เว็บไซต์บริษัท มีประมาณ 10 หน้า ต้องการใบเสนอราคาครับ',
        ], $overrides);
    }

    public function test_contact_page_is_displayed(): void
    {
        $this->get('/contact')
            ->assertOk()
            ->assertSee('ติดต่อ', false);
    }

    public function test_it_emails_the_configured_contact_address(): void
    {
        Mail::fake();
        Setting::setValue('contact_email', 'team@xman4289.com');

        $this->post('/contact', $this->validPayload())
            ->assertRedirect(route('contact.show'))
            ->assertSessionHas('contact_success');

        Mail::assertSent(ContactMessageMail::class, function (ContactMessageMail $mail) {
            // Reply-To carries the visitor so the team can answer with one click,
            // while the envelope still comes from our own domain.
            return $mail->hasTo('team@xman4289.com')
                && $mail->hasReplyTo('somchai@example.com')
                && $mail->subjectLine === 'สอบถามการทำเว็บไซต์';
        });
    }

    public function test_it_falls_back_to_the_app_from_address_when_no_contact_email_is_set(): void
    {
        Mail::fake();
        Setting::setValue('contact_email', '');
        config(['mail.from.address' => 'noreply@xman4289.com']);

        $this->post('/contact', $this->validPayload())
            ->assertSessionHas('contact_success');

        Mail::assertSent(ContactMessageMail::class, fn ($mail) => $mail->hasTo('noreply@xman4289.com'));
    }

    public function test_it_rejects_a_submission_that_fills_the_honeypot(): void
    {
        Mail::fake();
        Setting::setValue('contact_email', 'team@xman4289.com');

        $this->post('/contact', $this->validPayload(['website' => 'http://spam.example']))
            ->assertSessionHasErrors('website');

        Mail::assertNothingSent();
    }

    public function test_it_requires_the_core_fields(): void
    {
        Mail::fake();

        $this->post('/contact', [])
            ->assertSessionHasErrors(['name', 'email', 'subject', 'message']);

        Mail::assertNothingSent();
    }

    public function test_it_rejects_a_message_that_is_too_short(): void
    {
        Mail::fake();

        $this->post('/contact', $this->validPayload(['message' => 'สั้นไป']))
            ->assertSessionHasErrors('message');

        Mail::assertNothingSent();
    }

    public function test_it_keeps_the_lead_when_no_mailbox_can_be_resolved(): void
    {
        Mail::fake();
        Setting::setValue('contact_email', '');
        config(['mail.from.address' => '']);

        $this->post('/contact', $this->validPayload())
            ->assertSessionHas('contact_error');

        Mail::assertNothingSent();
    }
}

<?php

namespace Tests\Feature;

use App\Mail\ContactMessageMail;
use App\Models\Setting;
use App\Support\ContactLinks;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Tests\TestCase;

/**
 * Site mail goes out from a no-reply address with no mailbox behind it, so a customer
 * answering an order e-mail used to get a bounce. Replies must land in the support inbox.
 */
class MailReplyToTest extends TestCase
{
    use RefreshDatabase;

    public function test_mail_without_its_own_reply_to_answers_to_the_contact_address(): void
    {
        Setting::setValue('contact_email', 'team@xman4289.com');

        Mail::raw('Your order is confirmed', fn ($m) => $m->to('customer@example.com')->subject('Order #1'));

        $this->assertSame(['team@xman4289.com'], $this->replyTo($this->lastSent()));
    }

    public function test_it_falls_back_to_the_support_mailbox(): void
    {
        Setting::setValue('contact_email', '');

        Mail::raw('Your order is confirmed', fn ($m) => $m->to('customer@example.com')->subject('Order #1'));

        $this->assertSame([ContactLinks::DEFAULT_EMAIL], $this->replyTo($this->lastSent()));
    }

    public function test_a_message_that_names_its_own_reply_to_keeps_only_that(): void
    {
        Setting::setValue('contact_email', 'team@xman4289.com');

        // The contact form answers the visitor; adding support as well would copy every reply back to us.
        Mail::to('team@xman4289.com')->send(new ContactMessageMail(
            name: 'สมชาย ใจดี',
            email: 'somchai@example.com',
            phone: null,
            subjectLine: 'สอบถาม',
            body: 'อยากได้เว็บไซต์บริษัทครับ',
        ));

        $this->assertSame(['somchai@example.com'], $this->replyTo($this->lastSent()));
    }

    private function lastSent(): Email
    {
        return Mail::mailer()->getSymfonyTransport()->messages()->last()->getOriginalMessage();
    }

    /**
     * @return array<int, string>
     */
    private function replyTo(Email $email): array
    {
        return array_map(fn (Address $address) => $address->getAddress(), $email->getReplyTo());
    }
}

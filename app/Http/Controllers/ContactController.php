<?php

namespace App\Http\Controllers;

use App\Mail\ContactMessageMail;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Public "contact us" form.
 *
 * Separate from /support on purpose: /support is the quotation builder where a
 * visitor prices a project, while this is a plain message that lands in the
 * team's inbox.
 */
class ContactController extends Controller
{
    /**
     * Show the contact form.
     */
    public function show()
    {
        return view('contact.index', [
            'contact' => $this->contactDetails(),
        ]);
    }

    /**
     * Validate the form and email it to the team.
     */
    public function send(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:40',
            'subject' => 'required|string|max:200',
            'message' => 'required|string|min:10|max:5000',
            // Honeypot: a real person never sees this field, bots fill everything.
            'website' => 'nullable|prohibited',
        ], [
            'website.prohibited' => 'ไม่สามารถส่งข้อความได้ / Unable to send this message.',
        ]);

        $recipient = $this->recipient();

        if (! $recipient) {
            // Never drop the lead just because no inbox is configured — record it
            // so it can be recovered, and point the visitor at a channel that works.
            Log::error('Contact form has no recipient configured', [
                'from' => $validated['email'],
                'subject' => $validated['subject'],
                'message' => $validated['message'],
            ]);

            return back()
                ->withInput()
                ->with('contact_error', 'ระบบอีเมลยังไม่พร้อมใช้งาน กรุณาติดต่อผ่านช่องทางอื่นด้านล่าง / Email is unavailable right now, please use one of the channels below.');
        }

        try {
            Mail::to($recipient)->send(new ContactMessageMail(
                name: $validated['name'],
                email: $validated['email'],
                phone: $validated['phone'] ?? null,
                subjectLine: $validated['subject'],
                body: $validated['message'],
            ));
        } catch (\Throwable $e) {
            // Log the detail for us, show the visitor a plain message — an SMTP
            // stack trace on screen tells an attacker about the mail stack.
            Log::error('Contact form send failed: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'from' => $validated['email'],
                'subject' => $validated['subject'],
                'message' => $validated['message'],
            ]);

            return back()
                ->withInput()
                ->with('contact_error', 'ส่งข้อความไม่สำเร็จ กรุณาลองใหม่อีกครั้ง หรือติดต่อผ่านช่องทางด้านล่าง / Could not send your message, please try again or use a channel below.');
        }

        return redirect()
            ->route('contact.show')
            ->with('contact_success', 'ส่งข้อความเรียบร้อยแล้ว ทีมงานจะติดต่อกลับโดยเร็วที่สุด / Message sent, our team will get back to you shortly.');
    }

    /**
     * Inbox the form delivers to.
     *
     * Falls back to the app's own from-address so the form keeps working before
     * an admin fills in the contact email.
     */
    protected function recipient(): ?string
    {
        $recipient = trim((string) Setting::getValue('contact_email', ''));

        if ($recipient === '') {
            $recipient = (string) config('mail.from.address', '');
        }

        return filter_var($recipient, FILTER_VALIDATE_EMAIL) ? $recipient : null;
    }

    /**
     * Public contact channels shown alongside the form.
     *
     * @return array<string, string>
     */
    protected function contactDetails(): array
    {
        return [
            'email' => trim((string) Setting::getValue('contact_email', '')),
            'phone' => trim((string) Setting::getValue('contact_phone', '')),
            'phone_name' => trim((string) Setting::getValue('contact_phone_name', '')),
            'line_id' => trim((string) Setting::getValue('contact_line_id', '')),
            'line_url' => trim((string) Setting::getValue('contact_line_url', '')),
            'facebook_name' => trim((string) Setting::getValue('contact_facebook_name', '')),
            'facebook_url' => trim((string) Setting::getValue('contact_facebook_url', '')),
            'youtube_name' => trim((string) Setting::getValue('contact_youtube_name', '')),
            'youtube_url' => trim((string) Setting::getValue('contact_youtube_url', '')),
            'address' => trim((string) Setting::getValue('contact_address', '')),
        ];
    }
}

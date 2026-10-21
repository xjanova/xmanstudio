<?php

namespace App\Http\Controllers;

use App\Mail\ContactMessageMail;
use App\Models\Setting;
use App\Support\Alerts\BusinessAlerts;
use App\Support\ContactLinks;
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

        // The form stores nothing, so the Telegram card is sent whatever happens to the e-mail — if
        // the mail fails, the card is the only place the message still exists.
        $alert = fn (bool $mailed) => BusinessAlerts::contactMessage($validated, $mailed, $request->ip());

        try {
            // The address an admin set, or the support mailbox. The fallback used to be the
            // no-reply sender, which has no mailbox, so those messages bounced.
            Mail::to(ContactLinks::email())->send(new ContactMessageMail(
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
            $alert(false);

            return back()
                ->withInput()
                ->with('contact_error', 'ส่งข้อความไม่สำเร็จ กรุณาลองใหม่อีกครั้ง หรือติดต่อผ่านช่องทางด้านล่าง / Could not send your message, please try again or use a channel below.');
        }

        $alert(true);

        return redirect()
            ->route('contact.show')
            ->with('contact_success', 'ส่งข้อความเรียบร้อยแล้ว ทีมงานจะติดต่อกลับโดยเร็วที่สุด / Message sent, our team will get back to you shortly.');
    }

    /**
     * Public contact channels shown alongside the form.
     *
     * @return array<string, string>
     */
    protected function contactDetails(): array
    {
        return [
            'email' => ContactLinks::email(),
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

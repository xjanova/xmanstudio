<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Who is issuing a document — the letterhead every PDF prints at the top.
 *
 * Read from the settings table (edited at /admin/contact-settings), never
 * hardcoded. The quotation, the instalment invoice and the order receipt all
 * print the same block, so it lives here once.
 */
class Letterhead
{
    /**
     * @return array<string, mixed>
     */
    public static function info(): array
    {
        // A fake tax number on a tax document is worse than none, so an empty
        // value means the line is left off entirely.
        // The real wordmark the admin uploaded, not a drawn letter. DomPDF
        // cannot fetch a URL reliably, so the document gets an absolute file
        // path and the web page gets the public one; either may be absent, and
        // the templates fall back to type when it is.
        $logo = Setting::getValue('site_logo');
        $logoPath = $logo ? storage_path('app/public/' . $logo) : null;
        $logoUsable = $logoPath && is_file($logoPath) && is_readable($logoPath);

        return [
            'logo_path' => $logoUsable ? $logoPath : null,
            'logo_url' => $logo ? asset('storage/' . $logo) : null,
            'name' => Setting::getValue('company_name', 'XMAN STUDIO'),
            'tagline' => 'IT Solutions & Software Development',
            'address' => trim((string) Setting::getValue('contact_address', '')),
            'email' => trim((string) Setting::getValue('contact_email', ''))
                ?: trim((string) Setting::getValue('company_email', '')),
            'phone' => trim((string) Setting::getValue('contact_phone', ''))
                ?: trim((string) Setting::getValue('company_phone', '')),
            'website' => parse_url(config('app.url'), PHP_URL_HOST) ?: 'xman4289.com',
            'line' => trim((string) Setting::getValue('contact_line_id', '')),
            'tax_id' => trim((string) Setting::getValue('company_tax_id', '')),
        ];
    }
}

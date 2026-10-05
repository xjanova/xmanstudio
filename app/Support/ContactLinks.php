<?php

namespace App\Support;

use App\Models\Setting;

/**
 * The studio's social channels, as set at /admin/contact-settings.
 *
 * The footers used to hard-code these: one pointed YouTube at the Metal-X music channel
 * instead of the studio's, and the LINE and Twitter icons linked to "#". Reading the
 * settings means an admin fixes a channel in one place and every footer follows.
 */
class ContactLinks
{
    /**
     * Channels that have an address, in display order.
     *
     * @return array<int, array{key: string, label: string, url: string}>
     */
    public static function socials(): array
    {
        $links = [
            ['key' => 'facebook', 'label' => 'Facebook', 'url' => self::setting('contact_facebook_url')],
            ['key' => 'youtube', 'label' => 'YouTube', 'url' => self::setting('contact_youtube_url')],
            ['key' => 'line', 'label' => 'LINE', 'url' => self::lineUrl()],
        ];

        return array_values(array_filter($links, fn (array $link) => $link['url'] !== ''));
    }

    /**
     * The LINE add-friend link: the URL an admin entered, or one built from the LINE ID.
     * An official account ID starts with "@"; a personal ID is opened with "~".
     */
    public static function lineUrl(): string
    {
        $url = self::setting('contact_line_url');
        if ($url !== '') {
            return $url;
        }

        $id = preg_replace('/\s+/', '', self::setting('contact_line_id'));
        if ($id === '' || $id === '@') {
            return '';
        }

        return str_starts_with($id, '@')
            ? 'https://line.me/R/ti/p/@' . rawurlencode(substr($id, 1))
            : 'https://line.me/ti/p/~' . rawurlencode($id);
    }

    private static function setting(string $key): string
    {
        $value = trim((string) Setting::getValue($key, ''));

        // Only real web addresses become links; a stray "javascript:" never reaches an href.
        if ($value !== '' && $key !== 'contact_line_id' && ! preg_match('#^https?://#i', $value)) {
            return '';
        }

        return $value;
    }
}

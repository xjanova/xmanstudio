<?php

namespace App\Services\Articles;

use App\Models\Setting;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Pictures for knowledge-base articles (docs/ARTICLES.md): a cover and up to two illustrations,
 * drawn with OpenAI's image API on the same key that writes the articles.
 *
 * The cheapest model and quality by default (gpt-image-1-mini, "low": about 400 output tokens,
 * well under a baht a picture) — they are editorial illustrations, not photos. Every picture is
 * optional: when one cannot be drawn the article goes out without it (and the cover falls back to
 * the title card OgImageController draws), and the reason is logged.
 */
class ArticleImages
{
    public const DEFAULT_MODEL = 'gpt-image-1-mini';

    public const DEFAULT_QUALITY = 'low';

    /** Every article gets at least this many figures (ArticleWriter tops up what the writer left). */
    public const MIN_INLINE = 2;

    public const MAX_INLINE = 3;

    /** A picture the model returns larger than this is not what we asked for. */
    private const MAX_BYTES = 6 * 1024 * 1024;

    /**
     * One look for every picture on the blog, whatever the subject. No text in the picture:
     * image models misspell, and Thai comes out as noise.
     */
    private const STYLE = 'Modern flat 3D editorial illustration, deep navy background with purple and cyan neon accents, soft lighting, clean composition, professional tech magazine style. No text, no letters, no words, no logos, no watermark.';

    /** A placeholder the writer leaves in the Markdown where an illustration belongs. */
    public const PLACEHOLDER = '/^[ \t]*\[\[IMG:\s*(.+?)\s*\|\s*(.+?)\s*\]\][ \t]*$/mu';

    /** A picture line this class wrote; group 1 is its path on the public disk. */
    public const OWN_PICTURE = '/^!\[[^\]]*\]\((?:https?:\/\/[^\/)\s]+)?\/storage\/(articles\/[^)\s]+\.webp)\)[ \t]*$/mu';

    public static function enabled(): bool
    {
        return (bool) Setting::getValue('articles_images_enabled', true) && self::hasKey();
    }

    public static function hasKey(): bool
    {
        return self::apiKey() !== '';
    }

    public static function model(): string
    {
        return (string) (Setting::getValue('articles_image_model') ?: self::DEFAULT_MODEL);
    }

    public static function quality(): string
    {
        $q = (string) (Setting::getValue('articles_image_quality') ?: self::DEFAULT_QUALITY);

        return in_array($q, ['low', 'medium', 'high'], true) ? $q : self::DEFAULT_QUALITY;
    }

    private static function apiKey(): string
    {
        return (string) (Setting::get('openai_api_key') ?: Setting::get('ai_openai_key') ?: '');
    }

    /**
     * Draw the cover and fill the illustration placeholders in $markdown.
     * Placeholders that cannot be drawn (or past MAX_INLINE) are removed.
     *
     * @return array{markdown:string, cover:?string, drawn:int, failed:int}
     */
    public function illustrate(string $markdown, ?string $coverSubject, string $slug): array
    {
        $drawn = 0;
        $failed = 0;
        $cover = null;

        if (self::enabled() && $coverSubject) {
            $cover = $this->draw($coverSubject, $slug, 'cover');
            $cover ? $drawn++ : $failed++;
        }

        $inline = 0;
        $markdown = preg_replace_callback(self::PLACEHOLDER, function ($m) use ($slug, &$inline, &$drawn, &$failed) {
            if (! self::enabled() || $inline >= self::MAX_INLINE) {
                return '';
            }
            $inline++;

            $path = $this->draw($m[1], $slug, 'figure-' . $inline);
            if (! $path) {
                $failed++;

                return '';
            }
            $drawn++;

            $alt = str_replace(['[', ']', "\n"], ['(', ')', ' '], Str::limit(trim($m[2]), 140, ''));

            return '![' . $alt . '](' . Storage::disk('public')->url($path) . ')';
        }, $markdown) ?? $markdown;

        // A placeholder the writer left mid-paragraph is not matched above; never show it to readers.
        $markdown = preg_replace('/\[\[IMG:[^\]]*\]\]/u', '', $markdown) ?? $markdown;

        return ['markdown' => self::tidy($markdown), 'cover' => $cover, 'drawn' => $drawn, 'failed' => $failed];
    }

    /**
     * Draw one picture of $subject and keep it under storage/app/public/articles/{slug}/.
     * Returns the public-disk path, or null (logged) when it could not be drawn.
     */
    public function draw(string $subject, string $slug, string $name): ?string
    {
        $subject = trim(Str::limit(preg_replace('/\s+/u', ' ', $subject) ?? $subject, 600, ''));
        if ($subject === '' || self::apiKey() === '') {
            return null;
        }

        try {
            $response = Http::withToken(self::apiKey())
                ->timeout(150)
                ->retry(2, 3000, fn ($e) => $e instanceof ConnectionException
                    || ($e instanceof RequestException && $e->response->serverError()), throw: false)
                ->post('https://api.openai.com/v1/images/generations', [
                    'model' => self::model(),
                    'prompt' => $subject . "\n\nStyle: " . self::STYLE,
                    'size' => '1536x1024',
                    'quality' => self::quality(),
                    'output_format' => 'webp',
                    'output_compression' => 80,
                    'n' => 1,
                ]);

            if (! $response->successful()) {
                // The provider's own reason (billing, safety, model access) — never the key.
                Log::warning('Articles: picture not drawn: HTTP ' . $response->status() . ' ' . Str::limit((string) $response->json('error.message', $response->body()), 300), ['slug' => $slug, 'name' => $name]);

                return null;
            }

            $bytes = base64_decode((string) $response->json('data.0.b64_json'), true);
            if (! self::isWebp($bytes)) {
                Log::warning('Articles: picture not drawn: the answer was not a WebP image', ['slug' => $slug, 'name' => $name]);

                return null;
            }

            $path = 'articles/' . Str::slug($slug) . '/' . $name . '-' . Str::lower(Str::random(6)) . '.webp';
            Storage::disk('public')->put($path, $bytes);

            return $path;
        } catch (\Throwable $e) {
            Log::warning('Articles: picture not drawn: ' . $e->getMessage(), ['slug' => $slug, 'name' => $name]);

            return null;
        }
    }

    /** Only a real WebP of sane size is stored and served — never whatever bytes came back. */
    public static function isWebp(string|false $bytes): bool
    {
        return is_string($bytes)
            && strlen($bytes) > 64
            && strlen($bytes) <= self::MAX_BYTES
            && str_starts_with($bytes, 'RIFF')
            && substr($bytes, 8, 4) === 'WEBP';
    }

    /** Removed placeholders leave blank lines behind; keep paragraphs one blank line apart. */
    private static function tidy(string $markdown): string
    {
        return trim(preg_replace("/\n{3,}/", "\n\n", $markdown) ?? $markdown);
    }
}

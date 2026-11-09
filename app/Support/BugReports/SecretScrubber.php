<?php

namespace App\Support\BugReports;

/**
 * The server's second pass over a report from an app's "แจ้งปัญหา" window.
 *
 * The app already removes keys, tokens, licence keys and the user's name from what it sends; this
 * catches what slipped through (a key pasted into the user's own words, a new log line format), so
 * a secret never sits in the database or on an admin's screen. Masks, never drops the line: the
 * team still sees that a key was there.
 */
final class SecretScrubber
{
    public const MASK = '[REDACTED]';

    /** Pattern => replacement. Order matters: the specific key formats run before the generic ones. */
    private const PATTERNS = [
        // Anthropic / OpenAI / OpenRouter style keys
        '/\bsk-(?:ant-|proj-|or-v1-)?[A-Za-z0-9_\-]{16,}/' => self::MASK,
        // Google API keys
        '/\bAIza[0-9A-Za-z_\-]{30,}/' => self::MASK,
        // GitHub tokens
        '/\b(?:ghp|gho|ghu|ghs|ghr|github_pat)_[A-Za-z0-9_]{20,}/' => self::MASK,
        // xAI / Groq / Hugging Face / Slack
        '/\b(?:xai|gsk|hf)_[A-Za-z0-9]{20,}/' => self::MASK,
        '/\bxox[abposr]-[A-Za-z0-9\-]{10,}/' => self::MASK,
        // AWS access key id
        '/\bAKIA[0-9A-Z]{16}\b/' => self::MASK,
        // JSON Web Tokens
        '/\beyJ[A-Za-z0-9_\-]{8,}\.[A-Za-z0-9_\-]{8,}\.[A-Za-z0-9_\-]{8,}/' => self::MASK,
        // Authorization: Bearer xxx / Basic xxx
        '/\b(Bearer|Basic)\s+[A-Za-z0-9._~+\/=\-]{12,}/i' => '$1 ' . self::MASK,
        // key=value / "key": "value" for names that are always secret
        '/((?:api[_-]?key|apikey|access[_-]?token|refresh[_-]?token|auth[_-]?token|secret|password|passwd|pwd|license[_-]?key|licence[_-]?key|device[_-]?code)["\']?\s*[:=]\s*["\']?)[^\s"\',;&]{4,}/i' => '$1' . self::MASK,
        // query-string secrets: ?key=…&token=…
        '/([?&](?:key|token|access_token|api_key|sig|signature)=)[^&\s"\']+/i' => '$1' . self::MASK,
        // xmanstudio licence keys (XXXX-XXXX-XXXX-XXXX[-XXXX]) and FREE-/TRIAL- keys
        '/\b(?:FREE|TRIAL|DEMO)-[A-Z0-9]{4,}(?:-[A-Z0-9]{4,}){1,5}\b/' => self::MASK,
        '/\b[A-Z0-9]{4,5}(?:-[A-Z0-9]{4,5}){3,4}\b/' => self::MASK,
        // private key blocks
        '/-----BEGIN [A-Z ]*PRIVATE KEY-----.*?-----END [A-Z ]*PRIVATE KEY-----/s' => self::MASK,
    ];

    public static function text(?string $text): ?string
    {
        if ($text === null || $text === '') {
            return $text;
        }

        return preg_replace(array_keys(self::PATTERNS), array_values(self::PATTERNS), $text) ?? $text;
    }

    /** Every string inside a decoded JSON value, keys untouched. */
    public static function value(mixed $value): mixed
    {
        if (is_string($value)) {
            return self::text($value);
        }
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $value[$k] = self::value($v);
            }
        }

        return $value;
    }
}

<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validation rule to prevent malicious URLs
 */
class NoMaliciousUrls implements ValidationRule
{
    protected $allowLocalhost;

    public function __construct(bool $allowLocalhost = false)
    {
        $this->allowLocalhost = $allowLocalhost;
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || empty($value)) {
            return;
        }

        // Check for dangerous protocols
        $dangerous_protocols = [
            'javascript:',
            'data:',
            'vbscript:',
            'file:',
            'about:',
        ];

        $lowercase_value = strtolower($value);

        foreach ($dangerous_protocols as $protocol) {
            if (strpos($lowercase_value, $protocol) === 0) {
                $fail("The {$attribute} field contains a dangerous URL protocol.");

                return;
            }
        }

        // If it's a full URL, validate it
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            $parsed = parse_url($value);

            // Only allow http and https
            if (isset($parsed['scheme']) && ! in_array($parsed['scheme'], ['http', 'https'])) {
                $fail("The {$attribute} field must be an HTTP or HTTPS URL.");

                return;
            }

            // Check for localhost/private IPs (SSRF protection)
            if (! $this->allowLocalhost && isset($parsed['host'])) {
                $host = $parsed['host'];

                // Remove brackets from IPv6 addresses
                $host_clean = trim($host, '[]');

                // Block localhost
                if (in_array($host_clean, ['localhost', '127.0.0.1', '::1', '0.0.0.0']) ||
                    in_array($host, ['localhost', '127.0.0.1', '::1', '0.0.0.0'])) {
                    $fail("The {$attribute} field cannot point to localhost.");

                    return;
                }

                // Block private IP ranges
                if (filter_var($host_clean, FILTER_VALIDATE_IP)) {
                    if (! filter_var(
                        $host_clean,
                        FILTER_VALIDATE_IP,
                        FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
                    )) {
                        $fail("The {$attribute} field cannot point to private IP addresses.");

                        return;
                    }
                }

                // Block link-local addresses
                if (preg_match('/^(169\.254\.|fe80:)/i', $host_clean)) {
                    $fail("The {$attribute} field cannot point to link-local addresses.");

                    return;
                }
            }
        }

        // Check for common malicious URL patterns
        $malicious_patterns = [
            // URL shorteners that could hide malicious content
            '/bit\.ly|tinyurl\.com|goo\.gl|t\.co|ow\.ly/i',

            // Common phishing patterns
            '/paypa1\.com|g00gle\.com|amaz0n\.com/i', // Typosquatting

            // Multiple @ symbols (credential stealing attempt)
            '/@.*@/i',

            // HTML entities in URL (obfuscation)
            '/&#/i',
        ];

        foreach ($malicious_patterns as $pattern) {
            if (preg_match($pattern, $value)) {
                $fail("The {$attribute} field contains a potentially malicious URL pattern.");

                return;
            }
        }

        // Check for excessive URL encoding (obfuscation) - count total occurrences
        if (preg_match_all('/%[0-9a-f]{2}/i', $value, $matches) >= 10) {
            $fail("The {$attribute} field contains a potentially malicious URL pattern.");

            return;
        }
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return 'The :attribute field contains a potentially malicious or unsafe URL.';
    }
}

<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validation rule to prevent script tags and other dangerous HTML
 */
class NoScriptTags implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            return;
        }

        $dangerous_patterns = [
            // Script tags
            '/<script[^>]*>.*?<\/script>/is',
            '/<script[^>]*>/i',

            // Event handlers
            '/on\w+\s*=\s*["\'][^"\']*["\']/i',
            '/on\w+\s*=\s*\S+/i',

            // JavaScript protocol
            '/javascript\s*:/i',
            '/vbscript\s*:/i',
            '/data\s*:.*?base64/i',

            // iframe and embed tags
            '/<iframe[^>]*>/i',
            '/<embed[^>]*>/i',
            '/<object[^>]*>/i',

            // Meta refresh
            '/<meta[^>]*http-equiv\s*=\s*["\']?refresh/i',

            // Link with javascript
            '/<link[^>]*href\s*=\s*["\']?javascript:/i',

            // Form action with javascript
            '/<form[^>]*action\s*=\s*["\']?javascript:/i',

            // Import statement
            '/@import/i',

            // Expression (old IE)
            '/expression\s*\(/i',
        ];

        foreach ($dangerous_patterns as $pattern) {
            if (preg_match($pattern, $value)) {
                $fail("The {$attribute} field contains potentially dangerous content.");
                return;
            }
        }
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return 'The :attribute field contains potentially dangerous HTML or scripts.';
    }
}

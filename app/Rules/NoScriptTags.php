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
        if (! is_string($value)) {
            return;
        }

        $dangerous_patterns = [
            // Script tags (including obfuscated variants)
            '/<script[^>]*>.*?<\/script>/is',
            '/<script[^>]*>/i',
            '/<scr.*?ipt>/i', // Obfuscated script tag
            '/<<.*?script/i', // Double angle bracket obfuscation
            '/script.*?>.*?alert/is', // Script with alert

            // Event handlers
            '/on\w+\s*=\s*["\'][^"\']*["\']/i',
            '/on\w+\s*=\s*\S+/i',

            // Dangerous protocols
            '/javascript\s*:/i',
            '/vbscript\s*:/i',
            '/data\s*:/i',

            // iframe and embed tags
            '/<iframe[^>]*>/i',
            '/<embed[^>]*>/i',
            '/<object[^>]*>/i',

            // Meta refresh
            '/<meta[^>]*http-equiv\s*=\s*["\']?refresh/i',

            // Base tag (can redirect all relative URLs)
            '/<base[^>]*>/i',

            // Link tags (can load external stylesheets or execute javascript)
            '/<link[^>]*>/i',

            // Style tags (can contain malicious CSS)
            '/<style[^>]*>/i',

            // Form tags (can be used for phishing)
            '/<form[^>]*>/i',

            // Import statement
            '/@import/i',

            // Expression (old IE)
            '/expression\s*\(/i',

            // HTML entity encoded tags (&#60; = <, &#62; = >)
            '/&#\d+;.*?&#\d+;/i',
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

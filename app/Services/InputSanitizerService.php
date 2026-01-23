<?php

namespace App\Services;

/**
 * InputSanitizerService
 *
 * Protects against prompt injection, XSS, and other malicious input
 * in user-generated content that will be sent to AI models.
 */
class InputSanitizerService
{
    /**
     * Sanitize text for use in AI prompts
     * Removes potentially dangerous content while preserving useful information
     */
    public function sanitizeForPrompt(string $input, int $maxLength = 5000): string
    {
        // Step 1: Trim and limit length
        $input = trim($input);
        if (strlen($input) > $maxLength) {
            $input = substr($input, 0, $maxLength);
        }

        // Step 2: Remove null bytes and control characters (except newlines and tabs)
        $input = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $input);

        // Step 3: Remove common prompt injection patterns
        $dangerousPatterns = [
            // Direct prompt manipulation
            '/ignore\s+(all\s+)?(previous\s+)?(instructions?|commands?|prompts?)/i',
            '/forget (everything|all|previous)/i',
            '/system:?\s*role/i',
            '/\[?INST\]?/i',  // Instruction markers
            '/\[?\/INST\]?/i',
            '/<\|?(im_start|im_end|system|assistant|user)\|?>/i',

            // Jailbreak attempts
            '/now you are/i',
            '/pretend (you are|to be)/i',
            '/act as (a |an )?(?!user|customer|person)/i',  // Allow normal "act as user" but block role changes
            '/you must (now |always )?(?:ignore|forget|override)/i',

            // Code execution attempts
            '/<script[^>]*>.*?<\/script>/is',
            '/javascript:/i',
            '/on(load|error|click|mouse\w+)\s*=/i',

            // SQL injection patterns (for safety)
            '/union\s+select/i',
            '/drop\s+table/i',
            '/;?\s*--/i',  // SQL comments

            // Excessive special characters that might break parsing
            '/[{}<>]{10,}/',
            '/[\[\]]{10,}/',
        ];

        foreach ($dangerousPatterns as $pattern) {
            $input = preg_replace($pattern, '', $input);
        }

        // Step 4: Normalize whitespace
        $input = preg_replace('/\s+/', ' ', $input);
        $input = trim($input);

        // Step 5: Escape special markdown/formatting that might affect AI interpretation
        // But preserve basic punctuation
        $input = $this->escapePromptDelimiters($input);

        return $input;
    }

    /**
     * Sanitize HTML content (for display in web UI)
     */
    public function sanitizeHtml(string $input): string
    {
        // Strip all HTML tags except safe ones
        $allowedTags = '<p><br><strong><em><u><a>';
        $input = strip_tags($input, $allowedTags);

        // Remove any event handlers or javascript: protocols from allowed tags
        $input = preg_replace('/on\w+\s*=\s*["\'][^"\']*["\']/i', '', $input);
        $input = preg_replace('/href\s*=\s*["\']javascript:[^"\']*["\']/i', 'href="#"', $input);

        return $input;
    }

    /**
     * Validate and sanitize URLs
     */
    public function sanitizeUrl(string $url): ?string
    {
        // Remove whitespace
        $url = trim($url);

        // Check if it's a valid URL
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        // Only allow http and https protocols
        $parsed = parse_url($url);
        if (! isset($parsed['scheme']) || ! in_array($parsed['scheme'], ['http', 'https'])) {
            return null;
        }

        // Block common malicious patterns
        $dangerousPatterns = [
            '/javascript:/i',
            '/data:/i',
            '/vbscript:/i',
            '/file:/i',
        ];

        foreach ($dangerousPatterns as $pattern) {
            if (preg_match($pattern, $url)) {
                return null;
            }
        }

        // Block localhost and internal IPs (SSRF protection)
        $host = $parsed['host'] ?? '';
        $localhostPatterns = [
            '/^localhost$/i',
            '/^127\.\d+\.\d+\.\d+$/',
            '/^\[?::1\]?$/',
            '/^0\.0\.0\.0$/',
            '/^10\.\d+\.\d+\.\d+$/',
            '/^172\.(1[6-9]|2\d|3[01])\.\d+\.\d+$/',
            '/^192\.168\.\d+\.\d+$/',
        ];

        foreach ($localhostPatterns as $pattern) {
            if (preg_match($pattern, $host)) {
                return null;
            }
        }

        return $url;
    }

    /**
     * Remove or escape prompt delimiters that might confuse the AI
     */
    private function escapePromptDelimiters(string $input): string
    {
        // Escape triple backticks (code blocks)
        $input = str_replace('```', '\'\'\'', $input);

        // Escape XML-style tags that might be interpreted as special markers
        $input = preg_replace('/<\/?([a-z_]+)>/i', '&lt;$1&gt;', $input);

        return $input;
    }

    /**
     * Sanitize file names
     */
    public function sanitizeFileName(string $fileName): string
    {
        // Remove directory traversal attempts
        $fileName = basename($fileName);

        // Remove special characters except dot, dash, underscore
        $fileName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $fileName);

        // Prevent multiple dots
        $fileName = preg_replace('/\.+/', '.', $fileName);

        // Remove leading and trailing dots
        $fileName = trim($fileName, '.');

        return $fileName;
    }

    /**
     * Sanitize and validate email addresses
     */
    public function sanitizeEmail(string $email): ?string
    {
        $email = trim(strtolower($email));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        return $email;
    }

    /**
     * Sanitize JSON input
     * Returns sanitized JSON string or null if invalid
     */
    public function sanitizeJson(string $json, int $maxDepth = 10): ?string
    {
        // Try to decode
        $data = json_decode($json, true, $maxDepth);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        // Re-encode to ensure clean JSON
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Check if text contains potential malicious content
     * Returns true if suspicious, false if safe
     */
    public function isSuspicious(string $input): bool
    {
        $suspiciousPatterns = [
            // Excessive special characters
            '/[^\w\s]{50,}/',

            // Unusual encoding attempts
            '/(%[0-9a-f]{2}){10,}/i',

            // Excessive length (potential DoS)
            '/^.{50000,}$/',

            // Binary data indicators
            '/[\x00-\x08\x0B\x0C\x0E-\x1F]{5,}/',

            // Template injection patterns
            '/\{\{.*?\}\}/',
            '/\{%.*?%\}/',
            '/\$\{.*?\}/',
        ];

        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Sanitize array of inputs recursively
     */
    public function sanitizeArray(array $data, string $method = 'sanitizeForPrompt'): array
    {
        $sanitized = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = $this->sanitizeArray($value, $method);
            } elseif (is_string($value)) {
                $sanitized[$key] = $this->$method($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }
}

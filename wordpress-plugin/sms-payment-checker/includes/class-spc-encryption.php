<?php
/**
 * Encryption handling for SMS Payment Checker
 *
 * @package SMS_Payment_Checker
 */

defined('ABSPATH') || exit;

/**
 * SPC_Encryption class
 */
class SPC_Encryption {

    /**
     * Decrypt AES-256-GCM encrypted payload
     *
     * @param string $encrypted_data Base64 encoded encrypted data.
     * @param string $secret_key     Device secret key.
     * @return array|false Decrypted payload or false on failure.
     */
    public static function decrypt_payload($encrypted_data, $secret_key) {
        try {
            $combined = base64_decode($encrypted_data);
            if ($combined === false || strlen($combined) < 12) {
                return false;
            }

            $iv_length = 12; // GCM IV is 12 bytes
            $tag_length = 16; // GCM tag is 16 bytes

            $iv = substr($combined, 0, $iv_length);
            $cipher_text_with_tag = substr($combined, $iv_length);

            // Separate ciphertext and tag
            $tag = substr($cipher_text_with_tag, -$tag_length);
            $cipher_text = substr($cipher_text_with_tag, 0, -$tag_length);

            // Derive key (first 32 bytes of secret)
            $key = str_pad(substr($secret_key, 0, 32), 32, "\0");

            $decrypted = openssl_decrypt(
                $cipher_text,
                'aes-256-gcm',
                $key,
                OPENSSL_RAW_DATA,
                $iv,
                $tag
            );

            if ($decrypted === false) {
                return false;
            }

            $payload = json_decode($decrypted, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return false;
            }

            return $payload;
        } catch (Exception $e) {
            error_log('SPC Decryption Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Verify HMAC signature
     *
     * @param string $data       Data that was signed.
     * @param string $signature  Base64 encoded signature.
     * @param string $secret_key Device secret key.
     * @return bool
     */
    public static function verify_signature($data, $signature, $secret_key) {
        $expected = base64_encode(hash_hmac('sha256', $data, $secret_key, true));
        return hash_equals($expected, $signature);
    }

    /**
     * Generate a random API key
     *
     * @param int $length Key length.
     * @return string
     */
    public static function generate_api_key($length = 32) {
        return bin2hex(random_bytes($length / 2));
    }

    /**
     * Generate a random secret key
     *
     * @param int $length Key length.
     * @return string
     */
    public static function generate_secret_key($length = 32) {
        return bin2hex(random_bytes($length / 2));
    }

    /**
     * Generate a random device ID
     *
     * @return string
     */
    public static function generate_device_id() {
        return 'SMSCHK-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
    }

    /**
     * Generate a random nonce
     *
     * @return string
     */
    public static function generate_nonce() {
        return base64_encode(random_bytes(16));
    }
}

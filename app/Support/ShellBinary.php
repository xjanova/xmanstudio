<?php

namespace App\Support;

use App\Models\Setting;

/**
 * The ffmpeg / ffprobe programs named on the Metal-X settings page, ready to
 * stand at the front of a shell command.
 *
 * The settings page stores free text and several places paste it straight into
 * a command line, so the text was a way to run anything on a server shared with
 * other sites. A stored value is used only when it looks like a program name or
 * a path — letters, digits and . _ - / \ : — and it is quoted either way;
 * anything else falls back to the plain name on PATH.
 */
final class ShellBinary
{
    public const PATTERN = '/^[A-Za-z0-9_.\/\\\\:-]+$/';

    /** Already quoted — put it in the command as it is. */
    public static function ffmpeg(): string
    {
        return self::resolve('ffmpeg_binary', config('metalx.ffmpeg.binary'), 'ffmpeg');
    }

    /** Already quoted — put it in the command as it is. */
    public static function ffprobe(): string
    {
        return self::resolve('ffprobe_binary', config('metalx.ffmpeg.ffprobe_binary'), 'ffprobe');
    }

    public static function isAcceptable(?string $value): bool
    {
        return is_string($value) && $value !== '' && preg_match(self::PATTERN, $value) === 1;
    }

    private static function resolve(string $key, mixed $configured, string $fallback): string
    {
        foreach ([Setting::getValue($key), $configured] as $candidate) {
            $candidate = is_string($candidate) ? trim($candidate) : '';

            if (self::isAcceptable($candidate)) {
                return escapeshellarg($candidate);
            }
        }

        return escapeshellarg($fallback);
    }
}

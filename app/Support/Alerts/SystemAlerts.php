<?php

namespace App\Support\Alerts;

use App\Support\AdminAlerts;
use App\Support\Telegram\BotActions;
use Illuminate\Support\Str;
use Throwable;

/**
 * The quiet ways the site breaks: a scheduled task that stopped succeeding, an integration that
 * is configured half-way and fails closed without telling anyone.
 */
final class SystemAlerts
{
    /** A scheduled task ended with a non-zero exit code, or threw. Once per task per 6 hours. */
    public static function scheduledTaskFailed(string $task, ?int $exitCode, ?Throwable $e = null): void
    {
        try {
            $name = self::taskName($task);
            AdminAlerts::send(new Alert(
                key: 'schedule-failed:' . sha1($name),
                level: Alert::WARNING,
                title: 'งานตั้งเวลาล้มเหลว: ' . Str::limit($name, 60),
                body: $e !== null
                    ? 'ข้อความ: ' . Str::limit(Redact::text($e->getMessage()), 300)
                    : 'จบด้วย exit code ' . $exitCode . ' — ดูรายละเอียดใน storage/logs/laravel.log',
                facts: array_filter(['งาน' => Str::limit($name, 40), 'exit code' => $exitCode !== null ? (string) $exitCode : null]),
                category: 'system',
                buttons: [[BotActions::muteButton('schedule-failed:' . sha1($name))]],
            ), 360);
        } catch (Throwable) {
        }
    }

    /**
     * "Sign in with XMAN ID" answered 503 because the shared secret is missing — exactly how SSO sat
     * dead in production, unnoticed, until 2026-09-01. Every login from ai.xman4289.com and the
     * X-DREAMER app fails while this is true.
     */
    public static function ssoNotConfigured(): void
    {
        try {
            AdminAlerts::send(new Alert(
                key: 'sso-not-configured',
                level: Alert::CRITICAL,
                title: 'ล็อกอินด้วย XMAN ID ใช้งานไม่ได้',
                body: "มีคนพยายามล็อกอินจาก ai.xman4289.com หรือแอป X-DREAMER แต่เซิร์ฟเวอร์ตอบ 503\n"
                    . 'สาเหตุ: ไม่ได้ตั้ง XDREAMER_SSO_SECRET ใน .env (ฝั่ง aixman ชื่อ XMAN_SSO_SECRET ค่าเดียวกัน)',
                facts: ['ผลกระทบ' => 'ล็อกอิน SSO ทั้งหมด', 'ตัวแปร' => 'XDREAMER_SSO_SECRET'],
                category: 'system',
            ), 360);
        } catch (Throwable) {
        }
    }

    /** "'/usr/bin/php8.3' 'artisan' smschecker:cleanup > '/dev/null' 2>&1" → "smschecker:cleanup" */
    private static function taskName(string $command): string
    {
        if (preg_match("~artisan['\"]?\s+([A-Za-z0-9:_-]+)~", $command, $m)) {
            return $m[1];
        }

        return trim(preg_replace('~\s*>.*$~', '', $command) ?? $command) ?: 'งานไม่ทราบชื่อ';
    }
}

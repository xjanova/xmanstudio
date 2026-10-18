<?php

namespace App\Http\Controllers;

use App\Models\BlockedIp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Honeypot decoy logins.
 *
 * Serves believable login pages at paths real visitors never reach — the
 * WordPress / phpMyAdmin / Joomla URLs that only automated scanners probe for.
 * It authenticates nothing: every submission "fails", but the address, the
 * credentials the bot tried and its user-agent are written as evidence (one
 * "ANTIX-DECOY ip=.. port=.. data=.." line that Anti X tails and turns into a
 * permanent ban across every machine), and the address is also blocked here at
 * the edge straight away so it is cut off from xman4289 at once.
 *
 * It can never catch us: these paths are not linked anywhere on the site and are
 * not our real routes, our own and allow-listed addresses are skipped, and an
 * authenticated user is never logged or blocked.
 */
class DecoyController extends Controller
{
    public function handle(Request $request)
    {
        if (! config('decoy.enabled')) {
            abort(404);
        }

        $ip = (string) $request->ip();

        // ไม่แตะคนของเราเอง: ล็อกอินอยู่แล้ว / IP ภายใน / อยู่ใน allow-list
        $safe = $request->user() !== null
            || $this->isPrivate($ip)
            || in_array($ip, (array) config('decoy.safe_ips'), true);

        if (! $safe && $request->isMethod('post')) {
            $this->record($request, $ip);
        }

        $status = $request->isMethod('post') ? 401 : 200;

        return response()->view('decoy.login', [
            'failed' => $request->isMethod('post'),
        ], $status);
    }

    /** Logs the attempt for Anti X and blocks the address here, failing open. */
    private function record(Request $request, string $ip): void
    {
        $cap = max(0, min((int) config('decoy.capture_bytes', 512), 16384));

        // ชื่อผู้ใช้/รหัสที่บอตลอง (ชื่อฟิลด์แล้วแต่สคริปต์: WordPress ใช้ log/pwd)
        $user = $this->firstField($request, ['log', 'user', 'username', 'email', 'name', 'uid']);
        $pass = $this->firstField($request, ['pwd', 'pass', 'password', 'passwd']);

        $summary = sprintf(
            '%s %s user=%s pass=%s ua=%s',
            $request->method(),
            $request->path(),
            $user,
            $pass,
            (string) $request->userAgent()
        );
        $data = $this->printable(substr($summary, 0, $cap));
        $port = $request->isSecure() ? 443 : 80;
        $line = sprintf("%s ANTIX-DECOY ip=%s port=%d data=%s\n", now()->utc()->format('Y-m-d\TH:i:s\Z'), $ip, $port, $data);

        try {
            $path = (string) config('decoy.log');
            $dir = dirname($path);
            if (! is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
            file_put_contents($path, $line, FILE_APPEND | LOCK_EX);
        } catch (\Throwable $e) {
            Log::warning('decoy log write failed', ['error' => $e->getMessage()]);
        }

        if (config('decoy.block')) {
            try {
                // null = ถาวร · source auto — ไม่ทับการบล็อก/ปลดด้วยมือของผู้ดูแล
                BlockedIp::block($ip, 'honeypot: ' . $this->printable(substr($summary, 0, 200)), null, BlockedIp::SOURCE_AUTO);
            } catch (\Throwable $e) {
                Log::warning('decoy block failed', ['error' => $e->getMessage()]);
            }
        }
    }

    private function firstField(Request $request, array $keys): string
    {
        foreach ($keys as $k) {
            $v = $request->input($k);
            if (is_string($v) && $v !== '') {
                return $v;
            }
        }

        return '';
    }

    /** Control / non-ASCII bytes shown as \xNN so one hit stays on one log line. */
    private function printable(string $s): string
    {
        $out = '';
        $len = strlen($s);
        for ($i = 0; $i < $len; $i++) {
            $b = ord($s[$i]);
            if ($b === 9) {
                $out .= '\\t';
            } elseif ($b === 13) {
                $out .= '\\r';
            } elseif ($b === 10) {
                $out .= '\\n';
            } elseif ($b >= 0x20 && $b < 0x7f) {
                $out .= $s[$i];
            } else {
                $out .= '\\x' . sprintf('%02x', $b);
            }
        }

        return substr(trim($out), 0, 1024);
    }

    private function isPrivate(string $ip): bool
    {
        return $ip === '' || filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) === false;
    }
}

<?php

namespace App\Console\Commands;

use App\Services\FacebookPageException;
use App\Services\FacebookPageService;
use Illuminate\Console\Command;

/**
 * Connect the company Facebook page from a token, without the token ever touching argv (where
 * `ps` and shell history would show it) or the output:
 *
 *   ... | php artisan facebook:page-token --page=106036940932060      (token on stdin)
 *   php artisan facebook:page-token --file=/path/to/token.txt          (file read, then left as is)
 *   php artisan facebook:page-token --check                            (test what is stored)
 *
 * A User token (app XmanApp) is traded for a long-lived one and the page's own token is kept; a
 * Page token is kept as it is. Same logic as the admin screen.
 */
class FacebookPageTokenCommand extends Command
{
    protected $signature = 'facebook:page-token
        {--page= : Page ID ของเพจบริษัท (ไม่ใส่ = ใช้ค่าที่ตั้งไว้)}
        {--file= : อ่าน token จากไฟล์นี้แทน stdin}
        {--check : ไม่เปลี่ยนอะไร แค่ทดสอบ token ที่เก็บไว้}';

    protected $description = 'Connect the company Facebook page (token from stdin or a file, never from arguments)';

    public function handle(FacebookPageService $facebook): int
    {
        try {
            if ($this->option('check')) {
                $page = $facebook->check();
                $this->info('เชื่อมต่อเพจได้: ' . $page['page_name'] . ' (' . $page['page_id'] . ')'
                    . ($page['followers'] !== null ? ' · ผู้ติดตาม ' . number_format($page['followers']) : '')
                    . ($page['inspected'] ? ' · token ' . ($page['expires_at'] ? 'หมดอายุ ' . $page['expires_at']->format('d/m/Y H:i') : 'ไม่มีวันหมดอายุ') : ''));

                return self::SUCCESS;
            }

            $token = $this->readToken();
            if ($token === '') {
                $this->error('ไม่ได้รับ token — ส่งทาง stdin หรือ --file=');

                return self::INVALID;
            }

            $result = $facebook->connect($token, $this->option('page') ?: null);
        } catch (FacebookPageException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('เชื่อมเพจแล้ว: ' . $result['page_name'] . ' (' . $result['page_id'] . ') จาก '
            . ($result['kind'] === 'user' ? 'User Token' : 'Page Token')
            . ' · ' . ($result['expires_at'] ? 'หมดอายุ ' . $result['expires_at']->format('d/m/Y H:i') : 'token ไม่มีวันหมดอายุ'));
        foreach ($result['warnings'] as $warning) {
            $this->warn($warning);
        }

        return self::SUCCESS;
    }

    private function readToken(): string
    {
        $file = (string) $this->option('file');
        if ($file !== '') {
            return is_readable($file) ? trim((string) file_get_contents($file)) : '';
        }

        if (function_exists('stream_isatty') && @stream_isatty(STDIN)) {
            return trim((string) $this->secret('วาง token แล้วกด Enter'));
        }

        return trim((string) stream_get_contents(STDIN));
    }
}

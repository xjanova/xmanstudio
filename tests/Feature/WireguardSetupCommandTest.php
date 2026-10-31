<?php

namespace Tests\Feature;

use App\Models\WireguardServer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Process;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Tests\TestCase;

/**
 * wireguard:setup รับกุญแจลับทาง stdin เท่านั้น ถ้ารับเป็น option กุญแจจะอยู่ใน argv ของ php ซึ่ง `ps` อ่านได้
 *
 *   — กุญแจจาก stdin ถูกเก็บลง DB และไม่ถูกพิมพ์ออกมา (output ของ workflow คือ log สาธารณะ)
 *   — stdin ว่างหรือผิดรูปต้อง fail ห้ามสร้างคู่กุญแจใหม่ เพราะ interface รันด้วยกุญแจของผู้เรียกไปแล้ว
 *   — ไม่มี option --private-key ให้เผลอใช้อีก
 */
class WireguardSetupCommandTest extends TestCase
{
    use RefreshDatabase;

    private const PRIVATE_KEY = 'AAECAwQFBgcICQoLDA0ODxAREhMUFRYXGBkaGxwdHh8=';

    private const PUBLIC_KEY = 'ICEiIyQlJicoKSorLC0uLzAxMjM0NTY3ODk6Ozw9Pj8=';

    protected function setUp(): void
    {
        parent::setUp();

        Process::fake([
            'wg genkey' => Process::result(self::PRIVATE_KEY),
            'wg pubkey' => Process::result(self::PUBLIC_KEY),
            '*' => Process::result('203.0.113.7'),
        ]);
    }

    public function test_private_key_from_stdin_is_stored_and_never_printed(): void
    {
        [$exitCode, $output] = $this->runWithStdin(self::PRIVATE_KEY."\n", ['--public-key' => self::PUBLIC_KEY]);

        $this->assertSame(0, $exitCode, $output);
        $this->assertStringNotContainsString(self::PRIVATE_KEY, $output);

        $server = WireguardServer::where('name', 'wg-test')->firstOrFail();
        $this->assertSame(self::PRIVATE_KEY, $server->private_key);
        $this->assertSame(self::PUBLIC_KEY, $server->public_key);
    }

    public function test_a_key_without_a_trailing_newline_is_accepted(): void
    {
        [$exitCode, $output] = $this->runWithStdin(self::PRIVATE_KEY, ['--public-key' => self::PUBLIC_KEY]);

        $this->assertSame(0, $exitCode, $output);
        $this->assertSame(self::PRIVATE_KEY, WireguardServer::where('name', 'wg-test')->firstOrFail()->private_key);
    }

    public function test_empty_stdin_fails_instead_of_generating_a_new_pair(): void
    {
        [$exitCode, $output] = $this->runWithStdin('', ['--public-key' => self::PUBLIC_KEY]);

        $this->assertSame(1, $exitCode);
        $this->assertSame(0, WireguardServer::count());
        Process::assertDidntRun('wg genkey');
        $this->assertStringContainsString('--private-key-stdin', $output);
    }

    public function test_malformed_key_fails_without_echoing_it(): void
    {
        $badKey = 'not-a-wireguard-key-but-maybe-a-secret';

        [$exitCode, $output] = $this->runWithStdin($badKey."\n", ['--public-key' => self::PUBLIC_KEY]);

        $this->assertSame(1, $exitCode);
        $this->assertSame(0, WireguardServer::count());
        $this->assertStringNotContainsString($badKey, $output);
    }

    public function test_stdin_key_needs_the_public_key(): void
    {
        [$exitCode] = $this->runWithStdin(self::PRIVATE_KEY."\n");

        $this->assertSame(1, $exitCode);
        $this->assertSame(0, WireguardServer::count());
    }

    public function test_private_key_is_not_an_option(): void
    {
        $this->assertFalse(Artisan::all()['wireguard:setup']->getDefinition()->hasOption('private-key'));
    }

    public function test_generated_key_stays_out_of_the_output_with_db_only(): void
    {
        $exitCode = Artisan::call('wireguard:setup', ['--name' => 'wg-test', '--db-only' => true]);

        $this->assertSame(0, $exitCode, Artisan::output());
        $this->assertStringNotContainsString(self::PRIVATE_KEY, Artisan::output());
        $this->assertSame(self::PRIVATE_KEY, WireguardServer::where('name', 'wg-test')->firstOrFail()->private_key);
    }

    public function test_the_workflow_keeps_secrets_off_the_command_line(): void
    {
        $workflow = file_get_contents(base_path('.github/workflows/wireguard-setup.yml'));

        $this->assertStringContainsString('--private-key-stdin', $workflow);
        $this->assertDoesNotMatchRegularExpression('/--private-key[=\s]/', $workflow);
        $this->assertDoesNotMatchRegularExpression('/^\s*envs:.*SUDO_PASS/m', $workflow);

        // add-server generates a key: without --db-only the command prints it into the Actions log.
        $this->assertSame(
            substr_count($workflow, 'php artisan wireguard:setup'),
            substr_count($workflow, '--db-only'),
        );
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array{int, string}
     */
    private function runWithStdin(string $stdin, array $options = []): array
    {
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $stdin);
        rewind($stream);

        $input = new ArrayInput(['--name' => 'wg-test', '--private-key-stdin' => true, '--db-only' => true] + $options);
        $input->setStream($stream);
        $output = new BufferedOutput;

        $exitCode = Artisan::all()['wireguard:setup']->run($input, $output);

        return [$exitCode, $output->fetch()];
    }
}

<?php

namespace Tests\Concerns;

use App\Models\TpixPriceSnapshot;
use App\Services\TpixPay\TpixMath;
use App\Services\TpixPay\TpixPaySettings;
use App\Services\TpixPay\TpixPriceService;
use Brick\Math\BigInteger;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

/**
 * TPIX Chain (JSON-RPC, single calls and batches) and the USD→THB feeds, faked for the TPIX payment
 * tests. Calling fakeTpixChain() is also what lets the code reach the network in a test at all
 * (tpix_pay.allow_network_in_tests) — every other test is guarded off the real chain.
 *
 * Tests set the chain's state through the properties; the callback reads them when a request comes
 * in, so the state can change mid-test (a block is mined, the FX feed goes down). Any block up to
 * $latestBlock exists (empty unless a transfer was mined into it), except the $missingBlocks.
 *
 * Default pool: 199.9 TPIX / 19.99 USDT = 0.10 USD per TPIX; USD→THB 32.5 → ฿3.25 per TPIX.
 */
trait FakesTpixChain
{
    protected string $shopAddress = '0x00000000000000000000000000000000000a11ce';

    /** WTPIX reserve in wei and USDT reserve in its 6-decimal units */
    protected string $reserveTpixWei = '199900000000000000000';

    protected string $reserveUsdtUnits = '19990000';

    protected ?string $token0 = null;

    /** The node's raw answer to token0(), when a test wants a broken one (e.g. a bare "0x") */
    protected ?string $token0Raw = null;

    protected bool $fxDown = false;

    protected float $usdThb = 32.5;

    protected bool $rpcDown = false;

    protected int $latestBlock = 1000;

    /** @var list<int> blocks the node leaves out of a batch answer */
    protected array $missingBlocks = [];

    /** @var array<string, array<string, mixed>> */
    protected array $txs = [];

    /** @var array<string, array<string, mixed>> */
    protected array $receipts = [];

    /** @var array<int, int> block number → unix timestamp */
    protected array $blockTimes = [];

    /** @var array<int, list<array<string, mixed>>> block number → its transactions */
    protected array $blockTxs = [];

    protected function fakeTpixChain(): void
    {
        config(['tpix_pay.allow_network_in_tests' => true]);

        Http::fake(function (Request $request) {
            $url = $request->url();

            if (str_contains($url, 'rpc.tpix.online') || str_contains($url, 'rpc1.tpix.online')) {
                if ($this->rpcDown) {
                    return Http::response('down', 502);
                }

                $body = $request->data();
                if (array_is_list($body)) {
                    return Http::response(array_values(array_filter(array_map(fn (array $call) => $this->batchEntry($call), $body))));
                }

                return Http::response(['jsonrpc' => '2.0', 'id' => 1, 'result' => $this->rpcResult($body['method'], $body['params'] ?? [])]);
            }

            if (str_contains($url, 'open.er-api.com') || str_contains($url, 'exchangerate-api.com')) {
                return $this->fxDown ? Http::response('down', 503) : Http::response(['rates' => ['THB' => $this->usdThb]]);
            }

            // LINE notify and anything else the tests did not ask for
            return Http::response(['message' => 'not faked'], 404);
        });
    }

    protected function enableTpixPayments(?string $address = null, bool $enabled = true, float $maxPoolRatio = 5): void
    {
        app(TpixPaySettings::class)->save([
            'enabled' => $enabled,
            'address' => $address ?? $this->shopAddress,
            'confirmations' => 3,
            'ttl' => 30,
            'max_pool_ratio' => $maxPoolRatio,
        ]);
    }

    /** The half-hour of one-minute pool reads a quote needs (the TWAP), at the default 0.10 USD. */
    protected function seedTwap(string $priceUsd = '0.1', string $source = TpixPriceService::SOURCE_LIVE, int $count = 3): void
    {
        foreach (range(1, $count) as $i) {
            TpixPriceSnapshot::create([
                'price_usd' => $priceUsd,
                'usd_thb' => (string) $this->usdThb,
                'price_thb' => (string) ((float) $priceUsd * $this->usdThb),
                'source' => $source,
                'created_at' => now()->subMinutes(5 * $i),
            ]);
        }
    }

    /**
     * Put a transfer on the fake chain (in its block, so the scanner's block walk sees it too).
     * $block = null: still in the mempool — no block, no receipt.
     *
     * @param  array<string, mixed>  $overrides  fields of the transaction (to, value, chainId, v…)
     */
    protected function mineTransfer(string $hash, string $wei, ?int $block, int $timestamp, array $overrides = [], string $status = '0x1'): void
    {
        $tx = array_merge([
            'hash' => $hash,
            'from' => '0x00000000000000000000000000000000000b0b00',
            'to' => $this->shopAddress,
            'value' => TpixMath::weiToHex($wei),
            'blockNumber' => $block === null ? null : '0x' . dechex($block),
            'chainId' => '0x10c1',
            'v' => '0x1',
        ], $overrides);

        $this->txs[$hash] = $tx;
        if ($block === null) {
            unset($this->receipts[$hash]);

            return;
        }

        $this->receipts[$hash] = ['transactionHash' => $hash, 'status' => $status, 'blockNumber' => '0x' . dechex($block)];
        $this->blockTimes[$block] = $timestamp;
        $this->blockTxs[$block][] = $tx;
    }

    protected static function txHash(string $seed): string
    {
        return '0x' . hash('sha256', $seed);
    }

    /** @param  array<string, mixed>  $call */
    private function batchEntry(array $call): ?array
    {
        $result = $this->rpcResult($call['method'], $call['params'] ?? []);

        return $result === null ? null : ['jsonrpc' => '2.0', 'id' => $call['id'], 'result' => $result];
    }

    private function rpcResult(string $method, array $params): mixed
    {
        return match ($method) {
            'eth_call' => match ($params[0]['data'] ?? '') {
                '0x0902f1ac' => '0x' . self::word($this->reserveTpixWei) . self::word($this->reserveUsdtUnits) . self::word('1700000000'),
                '0x0dfe1681' => $this->token0Raw ?? '0x' . str_pad(substr(strtolower($this->token0 ?? config('tpix_pay.wtpix_address')), 2), 64, '0', STR_PAD_LEFT),
                default => '0x',
            },
            'eth_getTransactionByHash' => $this->txs[$params[0]] ?? null,
            'eth_getTransactionReceipt' => $this->receipts[$params[0]] ?? null,
            'eth_blockNumber' => '0x' . dechex($this->latestBlock),
            'eth_getBlockByNumber' => $this->block((int) hexdec(substr($params[0], 2)), (bool) ($params[1] ?? false)),
            default => null,
        };
    }

    /** @return array<string, mixed>|null */
    private function block(int $number, bool $fullTransactions): ?array
    {
        if ($number > $this->latestBlock || in_array($number, $this->missingBlocks, true)) {
            return null;
        }

        $txs = $this->blockTxs[$number] ?? [];

        return [
            'number' => '0x' . dechex($number),
            'timestamp' => '0x' . dechex($this->blockTimes[$number] ?? now()->timestamp),
            'transactions' => $fullTransactions ? $txs : array_column($txs, 'hash'),
        ];
    }

    private static function word(string $decimal): string
    {
        return str_pad(BigInteger::of($decimal)->toBase(16), 64, '0', STR_PAD_LEFT);
    }
}

<?php

namespace Tests\Concerns;

use App\Services\TpixPay\TpixMath;
use App\Services\TpixPay\TpixPaySettings;
use Brick\Math\BigInteger;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

/**
 * TPIX Chain, its explorer and the USD→THB feeds, faked for the TPIX payment tests.
 *
 * Tests set the chain's state through the properties and call fakeTpixChain() once; the callback
 * reads the properties when a request comes in, so the state can change mid-test (a block is mined,
 * the FX feed goes down). Any request nobody set up fails the test.
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

    protected bool $fxDown = false;

    protected float $usdThb = 32.5;

    protected bool $rpcDown = false;

    protected int $latestBlock = 1000;

    /** @var array<string, array<string, mixed>> */
    protected array $txs = [];

    /** @var array<string, array<string, mixed>> */
    protected array $receipts = [];

    /** @var array<int, int> block number → unix timestamp */
    protected array $blockTimes = [];

    /** @var list<array<string, mixed>> */
    protected array $explorerItems = [];

    protected function fakeTpixChain(): void
    {
        Http::fake(function (Request $request) {
            $url = $request->url();

            if (str_contains($url, 'rpc.tpix.online') || str_contains($url, 'rpc1.tpix.online')) {
                if ($this->rpcDown) {
                    return Http::response('down', 502);
                }

                return Http::response(['jsonrpc' => '2.0', 'id' => 1, 'result' => $this->rpcResult($request['method'], $request['params'] ?? [])]);
            }

            if (str_contains($url, 'open.er-api.com') || str_contains($url, 'exchangerate-api.com')) {
                return $this->fxDown ? Http::response('down', 503) : Http::response(['rates' => ['THB' => $this->usdThb]]);
            }

            if (str_contains($url, 'explorer.tpix.online/api/v2/addresses/')) {
                return Http::response(['items' => $this->explorerItems, 'next_page_params' => null]);
            }

            // LINE notify and anything else the tests did not ask for
            return Http::response(['message' => 'not faked'], 404);
        });
    }

    protected function enableTpixPayments(?string $address = null, bool $enabled = true): void
    {
        app(TpixPaySettings::class)->save([
            'enabled' => $enabled,
            'address' => $address ?? $this->shopAddress,
            'confirmations' => 3,
            'ttl' => 30,
        ]);
    }

    /**
     * Put a transfer on the fake chain.
     *
     * @param  array<string, mixed>  $overrides  fields of the transaction (to, value, chainId, v…)
     */
    protected function mineTransfer(string $hash, string $wei, int $block, int $timestamp, array $overrides = [], string $status = '0x1'): void
    {
        $this->txs[$hash] = array_merge([
            'hash' => $hash,
            'from' => '0x00000000000000000000000000000000000b0b00',
            'to' => $this->shopAddress,
            'value' => TpixMath::weiToHex($wei),
            'blockNumber' => '0x' . dechex($block),
            'chainId' => '0x10c1',
            'v' => '0x1',
        ], $overrides);
        $this->receipts[$hash] = ['transactionHash' => $hash, 'status' => $status, 'blockNumber' => '0x' . dechex($block)];
        $this->blockTimes[$block] = $timestamp;
    }

    protected static function txHash(string $seed): string
    {
        return '0x' . hash('sha256', $seed);
    }

    private function rpcResult(string $method, array $params): mixed
    {
        return match ($method) {
            'eth_call' => match ($params[0]['data'] ?? '') {
                '0x0902f1ac' => '0x' . self::word($this->reserveTpixWei) . self::word($this->reserveUsdtUnits) . self::word('1700000000'),
                '0x0dfe1681' => '0x' . str_pad(substr(strtolower($this->token0 ?? config('tpix_pay.wtpix_address')), 2), 64, '0', STR_PAD_LEFT),
                default => '0x',
            },
            'eth_getTransactionByHash' => $this->txs[$params[0]] ?? null,
            'eth_getTransactionReceipt' => $this->receipts[$params[0]] ?? null,
            'eth_blockNumber' => '0x' . dechex($this->latestBlock),
            'eth_getBlockByNumber' => isset($this->blockTimes[hexdec(substr($params[0], 2))])
                ? ['number' => $params[0], 'timestamp' => '0x' . dechex($this->blockTimes[hexdec(substr($params[0], 2))])]
                : null,
            default => null,
        };
    }

    private static function word(string $decimal): string
    {
        return str_pad(BigInteger::of($decimal)->toBase(16), 64, '0', STR_PAD_LEFT);
    }
}

<?php

namespace App\Services\TpixPay;

use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * JSON-RPC to TPIX Chain (EVM, chain id 4289).
 *
 * Every request carries a User-Agent: Cloudflare in front of rpc.tpix.online refuses requests
 * without one. The backup node is tried when the main one does not answer or answers an error, so
 * one node down does not stall payments. Failures throw TpixRpcException — callers leave a
 * payment where it was (pending/confirming), never mark it failed because the chain was slow.
 */
class TpixRpcClient
{
    /**
     * @param  array<int, mixed>  $params
     *
     * @throws TpixRpcException
     */
    public function call(string $method, array $params = []): mixed
    {
        $urls = array_values(array_unique(array_filter([
            config('tpix_pay.rpc_url'),
            config('tpix_pay.rpc_backup_url'),
        ])));

        $lastError = 'no RPC URL configured';

        foreach ($urls as $url) {
            try {
                $response = Http::withHeaders(['User-Agent' => config('tpix_pay.user_agent')])
                    ->acceptJson()
                    ->timeout((int) config('tpix_pay.rpc_timeout_seconds', 8))
                    ->post($url, [
                        'jsonrpc' => '2.0',
                        'id' => 1,
                        'method' => $method,
                        'params' => $params,
                    ]);
            } catch (Throwable $e) {
                // Connection refused / timed out: the message names only the host, never a secret
                $lastError = class_basename($e) . ' from ' . parse_url($url, PHP_URL_HOST);

                continue;
            }

            if (! $response->successful()) {
                $lastError = 'HTTP ' . $response->status() . ' from ' . parse_url($url, PHP_URL_HOST);

                continue;
            }

            $body = $response->json();
            if (! is_array($body) || ! array_key_exists('result', $body)) {
                $lastError = 'RPC error from ' . parse_url($url, PHP_URL_HOST) . ': '
                    . (is_array($body) ? (string) ($body['error']['message'] ?? 'no result') : 'not JSON');

                continue;
            }

            return $body['result'];
        }

        throw new TpixRpcException("{$method} failed: {$lastError}");
    }

    /** @throws TpixRpcException */
    public function ethCall(string $to, string $data): string
    {
        $result = $this->call('eth_call', [['to' => $to, 'data' => $data], 'latest']);

        if (! is_string($result) || ! str_starts_with($result, '0x')) {
            throw new TpixRpcException('eth_call returned no data');
        }

        return $result;
    }

    /**
     * @return array<string, mixed>|null null while the node does not know the transaction
     *
     * @throws TpixRpcException
     */
    public function getTransactionByHash(string $hash): ?array
    {
        $tx = $this->call('eth_getTransactionByHash', [$hash]);

        return is_array($tx) ? $tx : null;
    }

    /**
     * @return array<string, mixed>|null null until the transaction is mined
     *
     * @throws TpixRpcException
     */
    public function getTransactionReceipt(string $hash): ?array
    {
        $receipt = $this->call('eth_getTransactionReceipt', [$hash]);

        return is_array($receipt) ? $receipt : null;
    }

    /** @throws TpixRpcException */
    public function blockNumber(): int
    {
        return self::hexToInt($this->call('eth_blockNumber'));
    }

    /**
     * @return array<string, mixed>|null
     *
     * @throws TpixRpcException
     */
    public function getBlockByNumber(int $number): ?array
    {
        $block = $this->call('eth_getBlockByNumber', ['0x' . dechex($number), false]);

        return is_array($block) ? $block : null;
    }

    /**
     * A hex quantity small enough for a PHP int (block numbers, timestamps, chain ids, v).
     *
     * @throws TpixRpcException
     */
    public static function hexToInt(mixed $hex): int
    {
        if (! is_string($hex) || ! preg_match('/^0x[0-9a-fA-F]{1,15}$/', $hex)) {
            throw new TpixRpcException('not a hex quantity');
        }

        return (int) hexdec(substr($hex, 2));
    }
}

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
        $lastError = 'no RPC URL configured';

        foreach ($this->urls() as $url) {
            $body = $this->post($url, ['jsonrpc' => '2.0', 'id' => 1, 'method' => $method, 'params' => $params], $lastError);

            if ($body === null) {
                continue;
            }
            if (! array_key_exists('result', $body)) {
                $lastError = 'RPC error from ' . parse_url($url, PHP_URL_HOST) . ': ' . (string) ($body['error']['message'] ?? 'no result');

                continue;
            }

            return $body['result'];
        }

        throw new TpixRpcException("{$method} failed: {$lastError}");
    }

    /**
     * Several calls in one JSON-RPC batch request (rpc.tpix.online supports batches).
     *
     * @param  list<array{0:string, 1:array<int, mixed>}>  $calls  [method, params] pairs
     * @return array<int, mixed> results by the call's index; a call the node answered with an
     *                           error (or not at all) is missing from the array
     *
     * @throws TpixRpcException when no node answered the batch at all
     */
    public function batch(array $calls): array
    {
        if ($calls === []) {
            return [];
        }

        $payload = [];
        foreach (array_values($calls) as $i => [$method, $params]) {
            $payload[] = ['jsonrpc' => '2.0', 'id' => $i, 'method' => $method, 'params' => $params];
        }

        $lastError = 'no RPC URL configured';
        foreach ($this->urls() as $url) {
            $body = $this->post($url, $payload, $lastError);
            if ($body === null || ! array_is_list($body)) {
                $lastError = $body === null ? $lastError : 'batch answered with a single object from ' . parse_url($url, PHP_URL_HOST);

                continue;
            }

            $results = [];
            foreach ($body as $entry) {
                if (is_array($entry) && isset($entry['id']) && array_key_exists('result', $entry) && $entry['result'] !== null) {
                    $results[(int) $entry['id']] = $entry['result'];
                }
            }

            return $results;
        }

        throw new TpixRpcException("batch failed: {$lastError}");
    }

    /** @return list<string> */
    private function urls(): array
    {
        return array_values(array_unique(array_filter([
            config('tpix_pay.rpc_url'),
            config('tpix_pay.rpc_backup_url'),
        ])));
    }

    /**
     * POST one JSON body; the decoded answer, or null (with $error set) when the node did not answer
     * usefully. The error names only the host, never a secret.
     *
     * @param  array<int|string, mixed>  $payload
     * @return array<int|string, mixed>|null
     *
     * @throws TpixRpcException in tests that have not faked the chain
     */
    private function post(string $url, array $payload, string &$error): ?array
    {
        // A test must never reach the real chain: only tests that fake it opt in
        if (app()->runningUnitTests() && ! config('tpix_pay.allow_network_in_tests')) {
            throw new TpixRpcException('network disabled in tests');
        }

        try {
            $response = Http::withHeaders(['User-Agent' => config('tpix_pay.user_agent')])
                ->acceptJson()
                ->timeout((int) config('tpix_pay.rpc_timeout_seconds', 8))
                ->post($url, $payload);
        } catch (Throwable $e) {
            $error = class_basename($e) . ' from ' . parse_url($url, PHP_URL_HOST);

            return null;
        }

        if (! $response->successful()) {
            $error = 'HTTP ' . $response->status() . ' from ' . parse_url($url, PHP_URL_HOST);

            return null;
        }

        $body = $response->json();
        if (! is_array($body)) {
            $error = 'not JSON from ' . parse_url($url, PHP_URL_HOST);

            return null;
        }

        return $body;
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

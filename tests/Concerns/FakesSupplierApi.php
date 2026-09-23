<?php

namespace Tests\Concerns;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * A fake of the supplier's API, routed by "METHOD /path" with Str::is
 * patterns, that records every request so a test can say what was — and
 * just as often, what was NOT — sent upstream.
 *
 * Http::fake() is registered once per test. A second call is appended behind
 * the first, and the first one's catch-all would answer everything for ever;
 * so later calls to upstream() only swap the route table.
 */
trait FakesSupplierApi
{
    /** @var array<int,array{method:string,path:string,body:array}> */
    protected array $sent = [];

    /** @var array<string,mixed> */
    protected array $routes = [];

    protected bool $faked = false;

    /**
     * @param  array<string,mixed>  $routes  "METHOD /path" => array body | Http::response(...) | closure
     */
    protected function upstream(array $routes = []): void
    {
        $this->sent = [];

        $this->routes = $routes + [
            'GET /api/vps/v1/templates' => [
                ['id' => 1077, 'name' => 'Ubuntu 24.04 LTS', 'description' => 'Ubuntu'],
                ['id' => 1121, 'name' => 'Ubuntu 24.04 with Docker', 'description' => 'Docker'],
                ['id' => 1999, 'name' => 'Ubuntu 24.04 with Hostinger Tools', 'description' => 'names the supplier'],
            ],
            'GET /api/vps/v1/data-centers' => [
                ['id' => 21, 'name' => 'kul', 'location' => 'my', 'city' => 'Kuala Lumpur', 'continent' => 'Asia'],
                ['id' => 19, 'name' => 'fra', 'location' => 'de', 'city' => 'Frankfurt', 'continent' => 'Europe'],
            ],
        ];

        if ($this->faked) {
            return;
        }

        $this->faked = true;

        Http::fake(function (Request $request) {
            $path = (string) parse_url($request->url(), PHP_URL_PATH);
            $this->sent[] = ['method' => $request->method(), 'path' => $path, 'body' => $request->data()];

            foreach ($this->routes as $pattern => $response) {
                if (! Str::is($pattern, $request->method() . ' ' . $path)) {
                    continue;
                }

                if (is_callable($response)) {
                    return $response($request);
                }

                if (is_array($response)) {
                    return Http::response($response);
                }

                // A canned response answers every call to its route, so hand
                // out a fresh copy: the original's stream is spent after one read.
                $psr = $response->wait();
                $body = $psr->getBody();

                if ($body->isSeekable()) {
                    $body->rewind();
                }

                return Http::response((string) $body, $psr->getStatusCode(), $psr->getHeaders());
            }

            // Anything unlisted (the password-leak check, auto-renew toggles)
            // succeeds with nothing to say.
            return Http::response('', 200);
        });
    }

    /**
     * @return array<int,array{method:string,path:string,body:array}>
     */
    protected function sentTo(string $method, string $pathPattern): array
    {
        return array_values(array_filter(
            $this->sent,
            fn ($r) => $r['method'] === $method && Str::is($pathPattern, $r['path']),
        ));
    }
}

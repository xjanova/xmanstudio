<?php

namespace App\Console\Commands;

use App\Services\TpixPay\TpixPaymentService;
use App\Services\TpixPay\TpixPaySettings;
use App\Services\TpixPay\TpixPriceService;
use Illuminate\Console\Command;

/**
 * Every minute: refresh the TPIX price (snapshot for the TWAP, and the cache every page reads), walk
 * the new blocks for transfers to open TPIX quotes and settle them, re-check the ones waiting for
 * confirmations, expire stale quotes.
 *
 * Quotes already handed out keep being watched even if the admin switches TPIX off afterwards —
 * a customer may be sending the coins right now.
 */
class TpixPayScanCommand extends Command
{
    protected $signature = 'tpix:pay-scan';

    protected $description = 'Snapshot the TPIX price and settle TPIX payments found on the chain';

    public function handle(TpixPriceService $prices, TpixPaymentService $payments, TpixPaySettings $settings): int
    {
        if ($settings->showsPrices() || $settings->acceptsPayments()) {
            $spot = $prices->spot(fresh: true);
            $this->line($spot ? "price: {$spot['price_thb']} THB ({$spot['price_usd']} USD, quote " . ($spot['quote_rate_thb'] ?? 'none yet') . ')' : 'price: unavailable');
        }

        $stats = $payments->detectPending();
        $expiry = $payments->expireStale();

        $this->line("blocks {$stats['blocks']}, checked {$stats['checked']}, settled {$stats['settled']}, expired {$expiry['expired']}, released {$expiry['released']}");

        return self::SUCCESS;
    }
}

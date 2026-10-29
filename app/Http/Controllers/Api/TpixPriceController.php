<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TpixPay\TpixPaySettings;
use App\Services\TpixPay\TpixPriceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

/**
 * GET /api/v1/tpix/price — the TPIX price the shop uses, for the apps (public).
 *
 * Read from the chain's TPIX DEX pool × the live USD→THB rate (TpixPriceService), cached for a
 * minute like the web pages. `quote_rate_thb` is what a payment quote would use right now (the lower
 * of the live price and the half-hour average). `receive_address` is only given while payments are
 * switched on. Amounts are decimal strings: an app must not round money through a float.
 * `ai_credit_discount_percent` (int) is the TPIX discount on AI credit packages (AiCreditOrders).
 */
class TpixPriceController extends Controller
{
    public function show(TpixPriceService $prices, TpixPaySettings $settings): JsonResponse
    {
        $spot = $prices->spot();
        $accepts = $settings->acceptsPayments() && ($spot['quote_rate_thb'] ?? null) !== null;

        $chain = [
            'symbol' => config('tpix_pay.symbol'),
            'chain_id' => (int) config('tpix_pay.chain_id'),
            'decimals' => (int) config('tpix_pay.decimals'),
            'payments_enabled' => $accepts,
            'prices_shown' => $settings->showsPrices() && ($spot['quote_rate_thb'] ?? null) !== null,
            'receive_address' => $accepts ? $settings->receiveAddress() : null,
            'explorer_url' => config('tpix_pay.explorer_url'),
            // How many percent cheaper an AI credit package is when paid in TPIX. In both answers:
            // aixman takes its presence as "this xmanstudio has the signed /api/aixman/tpix API".
            'ai_credit_discount_percent' => $settings->aiCreditDiscountPercent(),
        ];

        if ($spot === null) {
            return response()->json([
                'success' => false,
                'message' => 'ราคา TPIX ไม่พร้อมใช้งานในขณะนี้',
                'data' => $chain,
            ], 503)->header('Cache-Control', 'no-store');
        }

        return response()->json([
            'success' => true,
            'data' => $chain + [
                'price_usd' => $spot['price_usd'],
                'price_thb' => $spot['price_thb'],
                // null until the 30-minute average exists — no quotes and no price tags until then
                'quote_rate_thb' => $spot['quote_rate_thb'],
                'twap_usd' => $spot['twap_usd'],
                'usd_thb' => $spot['usd_thb'],
                'usd_thb_live' => $spot['fx_live'],
                'max_quote_thb' => $spot['max_quote_thb'],
                'source' => $spot['source'],
                'pair_address' => config('tpix_pay.pair_address'),
                'updated_at' => Carbon::createFromTimestamp($spot['at'])->toIso8601String(),
            ],
        ])->header('Cache-Control', 'public, max-age=30');
    }
}

<?php

namespace App\Http\Middleware;

use App\Models\Affiliate;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tracks affiliate referral codes.
 *
 * When a visitor arrives with ?ref=CODE, we:
 * 1. Validate the referral code
 * 2. Store it in session + cookie (30 days)
 * 3. Increment the affiliate's click count
 *
 * The referral code is later used during checkout to attribute commissions.
 */
class AffiliateTracking
{
    public function handle(Request $request, Closure $next): Response
    {
        $ref = $request->query('ref');

        if ($ref) {
            $affiliate = Affiliate::where('referral_code', $ref)
                ->where('status', 'active')
                ->first();

            if ($affiliate) {
                // Don't track self-referral
                if (! auth()->check() || auth()->id() !== $affiliate->user_id) {
                    session(['affiliate_ref' => $ref]);
                    $affiliate->recordClick();
                }
            }
        }

        $response = $next($request);

        // Set cookie if we have a referral in session
        if ($ref && session('affiliate_ref')) {
            $response->cookie('affiliate_ref', session('affiliate_ref'), 60 * 24 * 30); // 30 days
        }

        return $response;
    }
}

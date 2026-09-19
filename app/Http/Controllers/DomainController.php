<?php

namespace App\Http\Controllers;

use App\Models\DomainTld;
use App\Services\DomainSearchService;
use App\Support\DomainPricing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * The public domain shop: search, prices, and the start of an order.
 *
 * Nothing here names the registrar we buy from. The customer is buying a
 * domain from XMAN Studio — that is the product, and the supply chain behind
 * it is ours to worry about, the same way a shop does not print its
 * wholesaler on the receipt.
 */
class DomainController extends Controller
{
    public function __construct(
        protected DomainSearchService $search,
    ) {}

    /**
     * The search page. Renders results server-side when a query is present so
     * that a shared link to a search still works and so the page is indexable.
     */
    public function index(Request $request): View
    {
        $query = (string) $request->query('q', '');

        $results = $query !== ''
            ? $this->search->search($query)
            : null;

        return view('domains.index', [
            'query' => $query,
            'results' => $results,
            'featured' => $this->featuredTlds(),
            'popular' => $this->popularPrices(),
        ]);
    }

    /**
     * The same search over JSON, for the live results on the page.
     *
     * Throttled at the route. The cache inside the service is what actually
     * protects the upstream budget, but a bare endpoint that proxies straight
     * to a paid API is worth rate-limiting on its own.
     */
    public function searchJson(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'max:120'],
        ]);

        $results = $this->search->search($validated['q']);

        return response()->json($results);
    }

    /**
     * The price table, so a customer can see what a TLD costs before they
     * have thought of a name.
     */
    public function pricing(): View
    {
        $tlds = DomainTld::active()
            ->orderBy('sort_order')
            ->orderBy('tld')
            ->get()
            ->map(fn (DomainTld $t) => [
                'tld' => $t->tld,
                'register' => $t->registerPriceThb(),
                'register_display' => DomainPricing::format($t->registerPriceThb()),
                'renew' => $t->renewPriceThb(),
                'renew_display' => DomainPricing::format($t->renewPriceThb()),
                'dearer' => $t->renewalIsDearer(),
                'description_th' => $t->description_th,
                'description_en' => $t->description_en,
                'featured' => $t->is_featured,
            ]);

        return view('domains.pricing', ['tlds' => $tlds]);
    }

    /**
     * @return Collection<int,DomainTld>
     */
    protected function featuredTlds()
    {
        return DomainTld::active()
            ->where('is_featured', true)
            ->orderBy('sort_order')
            ->limit(8)
            ->get();
    }

    /**
     * A handful of prices for the page to show before anyone searches, so the
     * hero is not an empty box with a text field in it.
     *
     * @return Collection<int,array<string,mixed>>
     */
    protected function popularPrices()
    {
        return DomainTld::active()
            ->where('search_by_default', true)
            ->orderBy('sort_order')
            ->limit(6)
            ->get()
            ->map(fn (DomainTld $t) => [
                'tld' => $t->tld,
                'price' => DomainPricing::format($t->registerPriceThb()),
            ]);
    }
}

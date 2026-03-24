<?php

namespace App\Http\Controllers;

use App\Models\AiprayDonation;
use App\Models\Product;
use App\Services\AiprayDonationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AiprayController extends Controller
{
    public function show()
    {
        $product = Product::where('slug', 'aipray')->with(['versions', 'githubSetting'])->firstOrFail();

        // Fetch latest release from GitHub
        $release = null;
        $changelog = '';
        $downloadUrl = '';
        $version = $product->latestVersion?->version ?? '1.0.0';

        try {
            $response = Http::timeout(10)
                ->withHeaders(['Accept' => 'application/vnd.github.v3+json'])
                ->get('https://api.github.com/repos/xjanova/Aipray/releases/latest');

            if ($response->ok()) {
                $release = $response->json();
                $version = ltrim($release['tag_name'] ?? $version, 'v');
                $changelog = $release['body'] ?? '';
                foreach ($release['assets'] ?? [] as $asset) {
                    if (str_contains($asset['name'] ?? '', 'universal')) {
                        $downloadUrl = $asset['browser_download_url'] ?? '';
                        break;
                    }
                }
                if (! $downloadUrl) {
                    foreach ($release['assets'] ?? [] as $asset) {
                        if (str_ends_with($asset['name'] ?? '', '.apk')) {
                            $downloadUrl = $asset['browser_download_url'] ?? '';
                            break;
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            // Fallback to DB version
        }

        $donationService = app(AiprayDonationService::class);
        $donations = $donationService->getPublicDonations(20);
        $donationStats = $donationService->getStats();

        return view('aipray.show', compact(
            'product', 'version', 'changelog', 'downloadUrl',
            'donations', 'donationStats'
        ));
    }

    public function donate()
    {
        $donationService = app(AiprayDonationService::class);
        $qr = $donationService->generateQr(0); // Default QR without amount
        $donations = $donationService->getPublicDonations(10);

        return view('aipray.donate', compact('qr', 'donations'));
    }

    public function storeDonation(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'donor_name' => 'nullable|string|max:255',
            'message' => 'nullable|string|max:500',
            'is_anonymous' => 'nullable|boolean',
        ]);

        $donationService = app(AiprayDonationService::class);
        $donation = $donationService->createDonation($validated);

        // Generate QR with specific amount
        $qr = $donationService->generateQr((float) $validated['amount']);

        return view('aipray.donate', [
            'qr' => $qr,
            'donation' => $donation,
            'amount' => $validated['amount'],
            'donations' => $donationService->getPublicDonations(10),
            'success' => true,
        ]);
    }

    public function donationComplete(Request $request)
    {
        $request->validate(['donation_id' => 'required|integer']);

        $donation = AiprayDonation::findOrFail($request->donation_id);
        $donationService = app(AiprayDonationService::class);
        $donationService->completeDonation($donation, $request->input('reference'));

        return redirect()->route('aipray.show')
            ->with('success', 'ขอบคุณสำหรับการบริจาค! สาธุ');
    }
}

<?php

use App\Models\AdsTxtSetting;
use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;

return new class extends Migration
{
    /**
     * The site's Google AdSense client/publisher ID.
     * This value is PUBLIC (it appears in every page's HTML), not a secret.
     */
    private string $clientId = 'ca-pub-1012362923849759';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1) Seed the AdSense verification settings (injected by <x-adsense-head>).
        Setting::setValue('adsense_client_id', $this->clientId, 'string', 'adsense', 'Google AdSense client ID (ca-pub-...)', true);
        Setting::setValue('adsense_enabled', true, 'boolean', 'adsense', 'Inject the AdSense verification/ads script on all public pages', true);

        // 2) Put the real publisher line into ads.txt — but only if the admin
        //    has not already customised it (still on the shipped placeholder).
        $pubId = str_replace('ca-', '', $this->clientId); // ads.txt uses "pub-...", not "ca-pub-..."
        $adsTxt = AdsTxtSetting::getInstance();

        $isPlaceholder = empty($adsTxt->content)
            || str_contains($adsTxt->content, 'pub-0000000000000000');

        if ($isPlaceholder) {
            $adsTxt->update([
                'content' => implode("\n", [
                    '# Google AdSense',
                    "google.com, {$pubId}, DIRECT, f08c47fec0942fa0",
                ]),
                'enabled' => true,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Setting::query()->whereIn('key', ['adsense_client_id', 'adsense_enabled'])->delete();
        Cache::forget('setting.adsense_client_id');
        Cache::forget('setting.adsense_enabled');
    }
};

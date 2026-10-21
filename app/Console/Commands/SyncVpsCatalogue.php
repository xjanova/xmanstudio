<?php

namespace App\Console\Commands;

use App\Models\VpsPlan;
use App\Services\HostingerApiService;
use App\Support\VpsPricing;
use Illuminate\Console\Command;

/**
 * Pull the VPS plans and their real prices from the supplier's catalogue.
 *
 * Every plan keeps one price entry per billing period, each with the price id
 * a purchase has to send — ids are opaque and per period, so they are only
 * ever copied from here, never assembled.
 *
 * New plans arrive with our own names. The four standard sizes are switched
 * on the first time they appear, because "add a VPS shop" should end with a
 * shop; anything else (the game-panel machines) arrives off, for a person to
 * decide on.
 */
class SyncVpsCatalogue extends Command
{
    protected $signature = 'vps:sync-catalogue {--dry : Show what would change without writing}';

    protected $description = 'Sync VPS plans, specs and prices from the supplier catalogue';

    /** Our names for the standard sizes, by the catalogue's slug. */
    protected const NAMES = [
        'kvm1' => ['name' => 'VPS Starter', 'sort' => 10, 'featured' => false],
        'kvm2' => ['name' => 'VPS Business', 'sort' => 20, 'featured' => true],
        'kvm4' => ['name' => 'VPS Pro', 'sort' => 30, 'featured' => false],
        'kvm8' => ['name' => 'VPS Enterprise', 'sort' => 40, 'featured' => false],
    ];

    public function handle(HostingerApiService $api): int
    {
        if (! $api->isConfigured()) {
            $this->warn('No supplier API token configured. Set it at /admin/domains first.');

            return self::SUCCESS;
        }

        $items = $api->getCatalog('VPS');

        if ($items === null) {
            $this->error('Catalogue request failed. Check the log for details.');

            return self::FAILURE;
        }

        $parsed = $this->parse($items);

        if ($parsed === []) {
            $this->warn('The catalogue came back with no VPS plans.');

            return self::FAILURE;
        }

        $dry = (bool) $this->option('dry');
        $rows = [];

        foreach ($parsed as $slug => $data) {
            $plan = VpsPlan::firstOrNew(['slug' => $slug]);
            $isNew = ! $plan->exists;

            $plan->fill([
                'remote_item_id' => $data['item_id'],
                'remote_name' => $data['name'],
                'category' => $data['category'],
                'cpus' => $data['cpus'],
                'memory_mb' => $data['memory_mb'],
                'disk_mb' => $data['disk_mb'],
                'bandwidth_mb' => $data['bandwidth_mb'],
                'network_mbps' => $data['network_mbps'],
                'prices' => $data['prices'],
                'synced_at' => now(),
            ]);

            if ($isNew) {
                $known = self::NAMES[$slug] ?? null;

                $plan->name = $known['name'] ?? $this->ourName($data);
                $plan->sort_order = $known['sort'] ?? 500;
                $plan->is_featured = $known['featured'] ?? false;
                // The standard sizes open the shop; anything unfamiliar waits
                // for someone to look at it.
                $plan->is_active = $known !== null;
            }

            $changed = $isNew || $plan->isDirty(['prices', 'cpus', 'memory_mb', 'disk_mb']);

            if ($changed) {
                $monthly = $plan->price('1m');
                $rows[] = [
                    $slug,
                    $isNew ? 'NEW' : 'updated',
                    $plan->name,
                    $monthly ? number_format($monthly['first'] / 100) . ' → ' . number_format($monthly['renew'] / 100) . ' ' . $monthly['currency'] : '-',
                    $monthly ? VpsPricing::format($plan->firstPriceThb('1m')) . ' → ' . VpsPricing::format($plan->renewPriceThb('1m')) : '-',
                ];
            }

            if (! $dry) {
                $plan->save();
            }
        }

        if ($rows !== []) {
            $this->table(['Slug', 'Change', 'Name', 'Cost/mo (first → renew)', 'Our price/mo'], $rows);
        }

        $this->info(sprintf('%s %d plans in the catalogue.', $dry ? '[dry run]' : 'Done:', count($parsed)));

        return self::SUCCESS;
    }

    /**
     * @param  array<int,mixed>  $items
     * @return array<string,array<string,mixed>>
     */
    protected function parse(array $items): array
    {
        $out = [];

        foreach ($items as $item) {
            if (! is_array($item) || strtolower((string) ($item['category'] ?? '')) !== 'vps') {
                continue;
            }

            $itemId = (string) ($item['id'] ?? '');

            if (! preg_match('/-vps-([a-z0-9]+)$/i', $itemId, $m)) {
                continue;
            }

            $slug = strtolower($m[1]);
            $meta = is_array($item['metadata'] ?? null) ? $item['metadata'] : [];
            $prices = [];

            foreach (($item['prices'] ?? []) as $price) {
                if (! is_array($price) || empty($price['id'])) {
                    continue;
                }

                $period = VpsPricing::periodKey((int) ($price['period'] ?? 0), (string) ($price['period_unit'] ?? ''));

                if ($period === null || ! is_numeric($price['first_period_price'] ?? $price['price'] ?? null)) {
                    continue;
                }

                $first = (int) ($price['first_period_price'] ?? $price['price']);
                $renew = (int) ($price['price'] ?? $first);

                if ($first <= 0) {
                    continue;
                }

                $prices[$period] = [
                    'item_id' => (string) $price['id'],
                    'currency' => strtoupper((string) ($price['currency'] ?? 'THB')),
                    'first' => $first,
                    'renew' => max($renew, 1),
                    'months' => VpsPricing::months($period),
                ];
            }

            if ($prices === []) {
                continue;
            }

            $out[$slug] = [
                'item_id' => $itemId,
                'name' => (string) ($item['name'] ?? $slug),
                'category' => str_contains($slug, 'minecraft') || stripos((string) ($item['name'] ?? ''), 'game') !== false ? 'game' : 'vps',
                'cpus' => (int) ($meta['cpus'] ?? 1),
                'memory_mb' => (int) ($meta['memory'] ?? 0),
                'disk_mb' => (int) ($meta['disk_space'] ?? 0),
                'bandwidth_mb' => (int) ($meta['bandwidth'] ?? 0),
                'network_mbps' => isset($meta['network']) ? (int) $meta['network'] : null,
                'prices' => $prices,
            ];
        }

        return $out;
    }

    /** A name for a plan we have no name for, that does not repeat the supplier's. */
    protected function ourName(array $data): string
    {
        $prefix = $data['category'] === 'game' ? 'Game Server' : 'VPS';

        return sprintf('%s %d vCPU / %s', $prefix, $data['cpus'], VpsPlan::sizeLabel($data['memory_mb']));
    }
}

<?php

namespace App\Services\Rentals;

use App\Models\RentalAddon;
use App\Models\RentalPackage;
use App\Models\RentalPayment;
use App\Models\UserRental;
use App\Models\UserRentalAddon;
use App\Support\Satang;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Picking add-ons and pricing them, for the three moments money changes hands:
 *
 *  - first checkout: the package and its add-ons, each at its full cycle price
 *  - a later purchase: add-ons only, prorated to the package's remaining days (RentalAddonPricing)
 *  - renewal: the package and every add-on in force, each at its full cycle price again
 *
 * A priced selection becomes "lines" — the same shape on the payment (metadata.lines), the payment
 * page and the receipt — so what the customer was shown is what they are charged and billed.
 */
class RentalAddons
{
    /**
     * Turn request input into [code => quantity].
     *
     * Accepts both shapes: a list (addons[]=mlm-gateway&addons[]=mlm-gateway — what an outside
     * link builds; a repeated code is one more unit) and a map (addons[mlm-gateway]=2 — what the
     * checkout form posts). Anything that is not a sane code is dropped here.
     *
     * @return array<string,int>
     */
    public static function normalizeSelection(mixed $raw): array
    {
        if (! is_array($raw)) {
            return [];
        }

        $selection = [];
        foreach ($raw as $key => $value) {
            if (is_int($key)) {
                $code = is_string($value) ? trim($value) : '';
                $quantity = 1;
            } else {
                $code = trim((string) $key);
                $quantity = is_numeric($value) ? (int) $value : 0;
            }

            if ($quantity <= 0 || ! preg_match('/^[a-z0-9][a-z0-9-]{0,63}$/', $code)) {
                continue;
            }

            $selection[$code] = min(99, ($selection[$code] ?? 0) + $quantity);
        }

        return $selection;
    }

    /** @return Collection<int,RentalAddon> */
    public function catalogue(?string $productKey)
    {
        return RentalAddon::active()->forProduct($productKey)->get();
    }

    /**
     * Check a selection against a product's active catalogue and what the rental already carries.
     *
     * @param  array<string,int>  $selection
     * @param  array<string,int>  $held  code => units already active (or paid, awaiting a check) on the rental
     * @return array{items: array<int,array{addon:RentalAddon,quantity:int}>, errors: array<int,string>}
     */
    public function resolve(?string $productKey, array $selection, array $held = []): array
    {
        $catalogue = $this->catalogue($productKey)->keyBy('code');
        $heldGroups = [];
        foreach ($held as $code => $units) {
            $group = $catalogue->get($code)?->exclusive_group;
            if ($group) {
                $heldGroups[$group] = $code;
            }
        }

        $items = [];
        $errors = [];
        $groupsTaken = [];

        foreach ($selection as $code => $quantity) {
            /** @var RentalAddon|null $addon */
            $addon = $catalogue->get($code);
            if (! $addon) {
                $errors[] = "ไม่พบตัวเลือกเสริม {$code} หรือปิดขายอยู่";

                continue;
            }

            $already = (int) ($held[$code] ?? 0);
            $max = max(1, (int) $addon->max_quantity);
            if ($already >= $max) {
                $errors[] = "{$addon->display_name} มีอยู่แล้ว";

                continue;
            }
            $quantity = max(1, min($quantity, $max - $already));

            if ($addon->exclusive_group) {
                $group = $addon->exclusive_group;
                if (isset($heldGroups[$group]) && $heldGroups[$group] !== $code) {
                    $errors[] = "{$addon->display_name} เปลี่ยนแทนระดับเดิมที่ใช้อยู่ไม่ได้ในขั้นตอนนี้ — ติดต่อทีมงานเพื่อปรับระดับ";

                    continue;
                }
                if (isset($groupsTaken[$group])) {
                    $errors[] = "เลือก {$addon->display_name} คู่กับ {$groupsTaken[$group]} ไม่ได้ — เลือกได้ระดับเดียว";

                    continue;
                }
                $groupsTaken[$group] = $addon->display_name;
            }

            $items[] = ['addon' => $addon, 'quantity' => $quantity];
        }

        return ['items' => $items, 'errors' => $errors];
    }

    /**
     * Units of each add-on the rental already has: in force, or bought and waiting only for an
     * admin to check the slip. Unpaid ones do not count — a new purchase replaces them.
     *
     * @return array<string,int>
     */
    public function held(UserRental $rental): array
    {
        $rows = $rental->addons()->with('addon')
            ->where(function ($q) {
                $q->where('status', UserRentalAddon::STATUS_ACTIVE)
                    ->orWhere(fn ($q) => $q->where('status', UserRentalAddon::STATUS_PENDING)
                        ->whereHas('payment', fn ($p) => $p->where('status', RentalPayment::STATUS_PROCESSING)));
            })
            ->get();

        $held = [];
        foreach ($rows as $row) {
            if ($row->addon) {
                $held[$row->addon->code] = ($held[$row->addon->code] ?? 0) + (int) $row->quantity;
            }
        }

        return $held;
    }

    /** The package itself, at its price, as a line. */
    public static function packageLine(RentalPackage $package): array
    {
        $satang = Satang::fromBaht((string) $package->price);

        return [
            'type' => 'package',
            'code' => $package->code,
            'package_id' => $package->id,
            'name' => $package->display_name . ' (' . $package->duration_text . ')',
            'quantity' => 1,
            'unit_satang' => $satang,
            'amount_satang' => $satang,
        ];
    }

    /**
     * Add-ons for a whole cycle — first checkout and renewal.
     *
     * @param  array<int,array{addon:RentalAddon,quantity:int}>  $items
     */
    public static function fullCycleLines(array $items, string $cycle): array
    {
        $lines = [];
        foreach ($items as ['addon' => $addon, 'quantity' => $quantity]) {
            $unit = $addon->cyclePriceSatang($cycle);
            $lines[] = [
                'type' => 'addon',
                'code' => $addon->code,
                'addon_id' => $addon->id,
                'name' => $addon->display_name,
                'quantity' => $quantity,
                'unit_satang' => $unit,
                'amount_satang' => $unit * $quantity,
            ];
        }

        return $lines;
    }

    /**
     * Add-ons bought part-way through the cycle, priced for the day $on (Thai date).
     *
     * @param  array<int,array{addon:RentalAddon,quantity:int}>  $items
     */
    public static function proratedLines(array $items, UserRental $rental, ?CarbonInterface $on = null): array
    {
        $cycle = $rental->billingCycle() ?? 'monthly';
        $daysLeft = RentalAddonPricing::daysLeft($rental, $on);
        $cycleDays = RentalAddonPricing::cycleDays($rental);

        $lines = [];
        foreach ($items as ['addon' => $addon, 'quantity' => $quantity]) {
            $unit = $addon->cyclePriceSatang($cycle);
            $lines[] = [
                'type' => 'addon',
                'code' => $addon->code,
                'addon_id' => $addon->id,
                'name' => $addon->display_name,
                'quantity' => $quantity,
                'unit_satang' => $unit,
                'amount_satang' => RentalAddonPricing::prorate($unit, $quantity, $daysLeft, $cycleDays),
                'days' => $daysLeft,
                'cycle_days' => $cycleDays,
            ];
        }

        return $lines;
    }

    public static function total(array $lines): int
    {
        return array_sum(array_map(fn ($line) => (int) ($line['amount_satang'] ?? 0), $lines));
    }

    /** For pages: "฿1,461.91", or "฿690" when there are no satang to show. */
    public static function money(int $satang): string
    {
        $text = Satang::format($satang);

        return str_ends_with($text, '.00') ? substr($text, 0, -3) : $text;
    }
}

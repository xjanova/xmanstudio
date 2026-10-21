<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\Quotation\Pricing;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;

/**
 * The selling numbers, editable without a deploy.
 *
 * Volume discounts, the rush surcharge, how long a quotation stands and how
 * the money is split into instalments all used to be constants — a seasonal
 * promotion meant a code change and a release. The builder page, the PDF and
 * the instalment invoices all read App\Support\Quotation\Pricing, so whatever
 * is saved here shows up in all three at once.
 */
class QuotationPricingController extends Controller
{
    public function index()
    {
        return view('admin.quotations.pricing', [
            'tiers' => Pricing::discountTiers(),
            'split' => Pricing::paymentSplit(),
            'rushPercent' => Pricing::rushPercent(),
            'validDays' => Pricing::validDays(),
            'dueDays' => Pricing::dueDays(),
            'defaults' => [
                'tiers' => Pricing::DEFAULT_TIERS,
                'split' => Pricing::DEFAULT_SPLIT,
                'rush' => Pricing::DEFAULT_RUSH_PERCENT,
                'valid' => Pricing::DEFAULT_VALID_DAYS,
                'due' => Pricing::DEFAULT_DUE_DAYS,
            ],
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'rush_percent' => 'required|numeric|min:0|max:200',
            'valid_days' => 'required|integer|min:1|max:365',
            'due_days' => 'required|integer|min:0|max:180',

            'tiers' => 'array|max:6',
            'tiers.*.from' => 'nullable|numeric|min:0|max:99999999',
            'tiers.*.percent' => 'nullable|numeric|min:0|max:99',

            'split' => 'array|min:1|max:6',
            'split.*.percent' => 'nullable|numeric|min:0|max:100',
            'split.*.label' => 'nullable|string|max:60',
        ], [], [
            'rush_percent' => 'ค่าเร่งงาน',
            'valid_days' => 'อายุใบเสนอราคา',
            'due_days' => 'กำหนดชำระงวดแรก',
        ]);

        $tiers = $this->normaliseTiers($request->input('tiers', []));
        $split = $this->normaliseSplit($request->input('split', []));

        // Pricing falls back to the defaults when a saved plan does not add up,
        // which would leave the admin looking at a page that says 100 while the
        // documents quietly print 50/25/25. Better to refuse the save and say so.
        $this->validateSplit($split);

        Setting::setValue('quote_discount_tiers', $tiers, 'json', 'quotation', 'ขั้นส่วนลดตามยอด', false);
        Setting::setValue('quote_payment_split', $split, 'json', 'quotation', 'งวดการชำระเงิน', false);
        Setting::setValue('quote_rush_percent', $validated['rush_percent'], 'string', 'quotation', 'ค่าเร่งงาน (%)', false);
        Setting::setValue('quote_valid_days', $validated['valid_days'], 'integer', 'quotation', 'อายุใบเสนอราคา (วัน)', false);
        Setting::setValue('quote_due_days', $validated['due_days'], 'integer', 'quotation', 'กำหนดชำระงวดแรก (วัน)', false);

        return redirect()
            ->route('admin.quotations.pricing')
            ->with('success', 'บันทึกเงื่อนไขราคาเรียบร้อยแล้ว — หน้าสั่งงาน เอกสาร และใบแจ้งหนี้ใช้ค่านี้ทันที');
    }

    /**
     * Blank rows are how a tier is removed, so they are dropped rather than
     * failed. A row with only one of the two fields filled is a half-typed
     * edit and would read as "0 baht gets 10%" — also dropped.
     *
     * @param  array<int, mixed>  $rows
     * @return array<int, array{from: int, percent: float}>
     */
    protected function normaliseTiers(array $rows): array
    {
        $tiers = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $from = $row['from'] ?? null;
            $percent = $row['percent'] ?? null;
            if ($from === null || $from === '' || $percent === null || $percent === '' || (float) $percent <= 0) {
                continue;
            }
            $tiers[] = ['from' => (int) $from, 'percent' => round((float) $percent, 2)];
        }

        usort($tiers, fn (array $a, array $b) => $a['from'] <=> $b['from']);

        return $tiers;
    }

    /**
     * @param  array<int, mixed>  $rows
     * @return array<int, array{percent: float, label: string}>
     */
    protected function normaliseSplit(array $rows): array
    {
        $split = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $percent = $row['percent'] ?? null;
            if ($percent === null || $percent === '' || (float) $percent <= 0) {
                continue;
            }
            $label = trim((string) ($row['label'] ?? ''));
            $split[] = ['percent' => round((float) $percent, 2), 'label' => $label !== '' ? $label : 'งวดชำระ'];
        }

        return $split;
    }

    /**
     * @param  array<int, array{percent: float, label: string}>  $split
     */
    protected function validateSplit(array $split): void
    {
        $sum = round(array_sum(array_column($split, 'percent')), 2);

        if ($split === []) {
            $this->failSplit('ต้องมีงวดชำระอย่างน้อย 1 งวด');
        }

        if (abs($sum - 100) > 0.05) {
            $this->failSplit('งวดชำระรวมกันต้องได้ 100% — ตอนนี้รวมได้ ' . rtrim(rtrim(number_format($sum, 2), '0'), '.') . '%');
        }
    }

    protected function failSplit(string $message): never
    {
        /** @var Validator $validator */
        $validator = validator([], []);
        $validator->errors()->add('split', $message);

        throw new ValidationException($validator);
    }
}

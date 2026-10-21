<?php

namespace App\Services;

use App\Models\ProjectInvoice;
use App\Models\ProjectOrder;
use App\Models\Quotation;
use App\Support\Quotation\Pricing;
use Illuminate\Support\Facades\DB;

/**
 * What happens when a quotation turns into work.
 *
 * Three paths could accept a quotation — the admin changing the status, the
 * admin pressing "สร้างโครงการ", and the customer pressing "ตอบรับ" on the public
 * document — and each did something different. Only the first created a
 * project; the customer's answer created nothing at all, so a job accepted at
 * midnight sat as a status until somebody noticed. And none of them turned the
 * payment schedule printed on the quotation into anything the team could
 * collect against.
 *
 * All three now come here. It is idempotent: accepting twice, or an admin
 * confirming an acceptance the customer already made, returns the same project
 * and the same invoices rather than a duplicate set.
 */
class QuotationAcceptance
{
    /**
     * Turn an accepted quotation into a project with its instalments.
     *
     * Does NOT change the quotation's own status — the caller owns that, since
     * the customer path and the admin path record the answer differently.
     */
    public function projectFor(Quotation $quotation): ProjectOrder
    {
        return DB::transaction(function () use ($quotation) {
            $project = ProjectOrder::where('quotation_id', $quotation->id)->first();

            if (! $project) {
                $project = $this->createProject($quotation);
                $this->createFeatures($quotation, $project);
                $this->createOpeningTimeline($quotation, $project);
            }

            // Runs for an existing project too: a project created before
            // instalments existed, or one whose invoices were deleted, still
            // needs a schedule.
            $this->createInvoices($quotation, $project);

            return $project->refresh();
        });
    }

    protected function createProject(Quotation $quotation): ProjectOrder
    {
        return ProjectOrder::create([
            'user_id' => $quotation->user_id,
            'quotation_id' => $quotation->id,
            'project_name' => $quotation->service_name ?? $quotation->service_type,
            'project_description' => $quotation->project_description,
            'project_type' => $quotation->service_type,
            'total_price' => $quotation->grand_total,
            'start_date' => now()->toDateString(),
            'admin_notes' => 'สร้างอัตโนมัติจากใบเสนอราคา #' . $quotation->displayNumber(),
        ]);
    }

    /**
     * Each thing the customer picked becomes a feature to build.
     */
    protected function createFeatures(Quotation $quotation, ProjectOrder $project): void
    {
        $order = 0;

        foreach ((array) $quotation->service_options as $option) {
            $project->features()->create([
                'name' => is_array($option) ? ($option['name'] ?? '-') : (string) $option,
                'description' => is_array($option) ? ($option['description'] ?? null) : null,
                'order' => $order++,
            ]);
        }

        foreach ((array) $quotation->additional_options as $option) {
            $project->features()->create([
                'name' => is_array($option) ? ($option['name'] ?? '-') : (string) $option,
                'description' => is_array($option)
                    ? 'ตัวเลือกเพิ่มเติม — ฿' . number_format((float) ($option['price'] ?? 0))
                    : null,
                'order' => $order++,
            ]);
        }
    }

    protected function createOpeningTimeline(Quotation $quotation, ProjectOrder $project): void
    {
        $project->timeline()->create([
            'title' => 'รับงาน — สร้างจากใบเสนอราคา',
            'description' => "ลูกค้ายอมรับใบเสนอราคา #{$quotation->displayNumber()}\n"
                . "ชื่อ: {$quotation->customer_name}\n"
                . 'ยอดรวม: ฿' . number_format((float) $quotation->grand_total, 2),
            'event_date' => now(),
            'type' => 'start',
            'is_completed' => true,
        ]);
    }

    /**
     * The payment schedule, as invoices.
     *
     * Amounts come from Pricing at the moment of acceptance and are then frozen
     * on the rows: an admin who changes the instalment plan next month must not
     * silently re-split an invoice a customer has already been sent.
     *
     * Only the first instalment is issued. The rest stay scheduled until the
     * work they are tied to is delivered — billing all of them on day one is
     * how a customer gets three invoices for a job that has not started.
     *
     * @return array<int, ProjectInvoice>
     */
    public function createInvoices(Quotation $quotation, ProjectOrder $project): array
    {
        if ($project->invoices()->exists()) {
            return $project->invoices()->get()->all();
        }

        $instalments = Pricing::instalments((float) $quotation->grand_total);
        $total = count($instalments);
        $dueDays = Pricing::dueDays();
        $created = [];

        foreach ($instalments as $instalment) {
            $isFirst = $instalment['no'] === 1;

            $created[] = ProjectInvoice::create([
                'project_order_id' => $project->id,
                'quotation_id' => $quotation->id,
                'installment_no' => $instalment['no'],
                'total_installments' => $total,
                'title' => 'งวดที่ ' . $instalment['no'] . ' · ' . $instalment['label'],
                // เป็น string เพราะคอลัมน์ cast เป็น decimal — Laravel ส่งต่อให้ BigDecimal
                // ซึ่งเลิกรับ float แล้ว และ float ที่ผ่าน BigDecimal เสี่ยงเพี้ยนหลักสตางค์
                'percent' => (string) $instalment['percent'],
                'amount' => (string) $instalment['amount'],
                'status' => $isFirst ? ProjectInvoice::ISSUED : ProjectInvoice::SCHEDULED,
                'issued_at' => $isFirst ? now() : null,
                'due_date' => $isFirst ? now()->addDays($dueDays)->toDateString() : null,
            ]);
        }

        return $created;
    }

    /**
     * Keep the project's money in step with its invoices.
     *
     * paid_amount used to be typed in by hand, which meant it disagreed with
     * reality as soon as anyone forgot. It is now derived: the sum of what is
     * actually marked paid.
     */
    public function syncPayment(ProjectOrder $project): void
    {
        $paid = (float) $project->invoices()
            ->where('status', ProjectInvoice::PAID)
            ->sum('amount');

        // Voided instalments are not owed, so they must not make a fully-paid
        // job look partial forever.
        $billable = (float) $project->invoices()
            ->where('status', '!=', ProjectInvoice::VOID)
            ->sum('amount');

        $status = 'unpaid';
        if ($paid > 0) {
            $status = ($billable > 0 && $paid + 0.01 >= $billable) ? 'paid' : 'partial';
        }

        $project->update([
            'paid_amount' => round($paid, 2),
            'payment_status' => $status,
        ]);
    }
}

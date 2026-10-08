<?php

namespace App\Services;

use App\Models\GameCampaign;
use App\Models\GameDonation;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GameSupportService
{
    public static function satang(string $amount): int
    {
        if (! preg_match('/^\d{1,7}(\.\d{1,2})?$/D', $amount)) {
            throw ValidationException::withMessages(['amount' => 'ใส่ยอด 1–1,000,000 บาท ทศนิยมไม่เกิน 2 ตำแหน่ง']);
        }
        [$baht, $fraction] = array_pad(explode('.', $amount, 2), 2, '');
        $value = ((int) $baht * 100) + (int) str_pad($fraction, 2, '0');
        if ($value < 100 || $value > 100000000) {
            throw ValidationException::withMessages(['amount' => 'ใส่ยอด 1–1,000,000 บาท']);
        }

        return $value;
    }

    public function reward(GameCampaign $campaign, int $amount): array
    {
        return collect($campaign->tiers)->filter(fn ($t) => $amount >= $t['minimum'] * 100)->sortByDesc('minimum')->first()
            ?? ['minimum' => 1, 'name' => 'SUPPORTER', 'rewards' => ['รายนามผู้สนับสนุนตามความสมัครใจ']];
    }

    public function summary(): array
    {
        $donations = DB::table('game_donations')->where('status', 'approved')->selectRaw('game_campaign_id, SUM(amount_satang) AS amount, COUNT(DISTINCT user_id) AS supporters')->groupBy('game_campaign_id')->get()->keyBy('game_campaign_id');
        $votes = DB::table('game_votes')->selectRaw('game_campaign_id, COUNT(*) AS total')->groupBy('game_campaign_id')->pluck('total', 'game_campaign_id');
        $ratings = DB::table('game_ratings')->selectRaw('game_campaign_id, AVG(stars) AS average, COUNT(*) AS total')->groupBy('game_campaign_id')->get()->keyBy('game_campaign_id');

        return GameCampaign::where('active', true)->orderBy('id')->get()->map(fn ($g) => [
            'slug' => $g->slug, 'name' => $g->name, 'goal' => $g->goal_satang / 100,
            'raised' => (int) ($donations->get($g->id)?->amount ?? 0) / 100, 'supporters' => (int) ($donations->get($g->id)?->supporters ?? 0),
            'votes' => (int) ($votes[$g->id] ?? 0), 'stars' => round((float) ($ratings->get($g->id)?->average ?? 0), 2), 'rating_count' => (int) ($ratings->get($g->id)?->total ?? 0),
        ])->all();
    }

    public function review(GameDonation $donation, array $data, int $reviewer): void
    {
        DB::transaction(function () use ($donation, $data, $reviewer) {
            $row = GameDonation::lockForUpdate()->findOrFail($donation->id);
            if ($row->status !== 'pending') {
                throw ValidationException::withMessages(['status' => 'รายการนี้ตรวจแล้ว ไม่สามารถนับยอดซ้ำ']);
            }
            $reference = null;
            if ($data['decision'] === 'approved') {
                if (self::satang($data['verified_amount']) !== $row->amount_satang) {
                    throw ValidationException::withMessages(['verified_amount' => 'ยอดในรายการและยอดตรวจสอบไม่ตรงกัน']);
                }
                $reference = strtoupper(preg_replace('/\s+/', '', $data['bank_reference']));
                if (GameDonation::where('bank_reference', $reference)->exists()) {
                    throw ValidationException::withMessages(['bank_reference' => 'เลขอ้างอิงนี้ใช้กับรายการอื่นแล้ว']);
                }
            }
            $row->update(['status' => $data['decision'], 'bank_reference' => $reference, 'reviewed_by' => $reviewer, 'reviewed_at' => now(), 'review_note' => $data['review_note'], 'reward_status' => $data['decision'] === 'approved' ? 'available' : 'not_eligible', 'audit' => [...($row->audit ?? []), ['action' => $data['decision'], 'by' => $reviewer, 'at' => now()->toIso8601String(), 'note' => $data['review_note']]]]);
        });
    }
}

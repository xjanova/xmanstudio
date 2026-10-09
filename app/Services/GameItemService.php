<?php

namespace App\Services;

use App\Models\GameCampaign;
use App\Models\GameDonation;
use App\Models\GameEntitlement;
use App\Models\GameItem;
use App\Models\GameItemRedemption;
use Illuminate\Support\Facades\DB;

/**
 * In-game items for supporters. An approved donation grants the items of its reward
 * snapshot; each grant carries a redeem code the member types into the game, which asks
 * POST /api/gameshub/redeem. Codes are 80 random bits, stored as a hash for lookup and
 * encrypted for showing back to the owner.
 */
class GameItemService
{
    /** Crockford base32: no I, L, O or U, so a code read aloud or retyped survives. */
    private const ALPHABET = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';

    public static function newCode(): string
    {
        $groups = [];
        for ($g = 0; $g < 4; $g++) {
            $chunk = '';
            for ($i = 0; $i < 4; $i++) {
                $chunk .= self::ALPHABET[random_int(0, 31)];
            }
            $groups[] = $chunk;
        }

        return 'XG-' . implode('-', $groups);
    }

    /** What a player may type: any case, spaces or dashes, O for 0 and I/L for 1. */
    public static function normalize(string $code): string
    {
        $c = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $code));
        if (str_starts_with($c, 'XG')) {
            $c = substr($c, 2);
        }

        return strtr($c, ['O' => '0', 'I' => '1', 'L' => '1']);
    }

    public static function hash(string $code): string
    {
        return hash('sha256', 'gameshub-item:' . self::normalize($code));
    }

    /** Items named in a reward tier, resolved against the campaign's catalogue at donation time. */
    public function snapshotItems(GameCampaign $campaign, array $keys): array
    {
        if (! $keys) {
            return [];
        }

        // a deactivated item stays redeemable for whoever already has it, but is no longer promised
        return $campaign->items()->where('active', true)->whereIn('key', $keys)->orderBy('id')->get(['key', 'name', 'kind'])
            ->map(fn (GameItem $i) => ['key' => $i->key, 'name' => $i->name, 'kind' => $i->kind])->values()->all();
    }

    /** Called inside the approval transaction; the unique (donation, item) index keeps it idempotent. */
    public function grantForDonation(GameDonation $donation, int $by): int
    {
        $keys = collect($donation->reward_snapshot['items'] ?? [])->pluck('key')->filter()->all();
        if (! $keys) {
            return 0;
        }
        $granted = 0;
        foreach (GameItem::where('game_campaign_id', $donation->game_campaign_id)->whereIn('key', $keys)->get() as $item) {
            $exists = GameEntitlement::where('game_donation_id', $donation->id)->where('game_item_id', $item->id)->exists();
            if (! $exists) {
                $this->create($donation->user_id, $item, 'donation', $by, $donation->id, 'จากรายการสนับสนุน ' . $donation->public_id);
                $granted++;
            }
        }

        return $granted;
    }

    public function revokeForDonation(GameDonation $donation, int $by, string $note): int
    {
        return GameEntitlement::where('game_donation_id', $donation->id)->where('status', 'granted')
            ->update(['status' => 'revoked', 'revoked_by' => $by, 'revoked_at' => now(), 'note' => $note, 'updated_at' => now()]);
    }

    public function grantManual(int $userId, GameItem $item, int $by, string $note): GameEntitlement
    {
        return $this->create($userId, $item, 'manual', $by, null, $note);
    }

    public function revoke(GameEntitlement $entitlement, int $by, string $note): void
    {
        DB::transaction(function () use ($entitlement, $by, $note) {
            $row = GameEntitlement::lockForUpdate()->findOrFail($entitlement->id);
            if ($row->status === 'granted') {
                $row->update(['status' => 'revoked', 'revoked_by' => $by, 'revoked_at' => now(), 'note' => trim(($row->note ? $row->note . "\n" : '') . 'ยกเลิก: ' . $note)]);
            }
        });
    }

    private function create(int $userId, GameItem $item, string $source, int $by, ?int $donationId, string $note): GameEntitlement
    {
        // a clash in 80 random bits will not happen, but a retry costs nothing
        do {
            $code = self::newCode();
        } while (GameEntitlement::where('code_hash', self::hash($code))->exists());

        return GameEntitlement::create([
            'user_id' => $userId, 'game_item_id' => $item->id, 'game_donation_id' => $donationId,
            'source' => $source, 'status' => 'granted', 'code' => $code, 'code_hash' => self::hash($code),
            'granted_by' => $by, 'note' => $note,
        ]);
    }

    /**
     * Redeem a code for a game on one device. The same device asking again gets the same
     * answer without using up another device slot.
     *
     * @return array{status:int, body:array}
     */
    public function redeem(string $game, string $code, string $device, ?string $ip): array
    {
        $fail = fn (int $status, string $error, string $message) => ['status' => $status, 'body' => ['ok' => false, 'error' => $error, 'message' => $message]];

        return DB::transaction(function () use ($game, $code, $device, $ip, $fail) {
            $row = GameEntitlement::where('code_hash', self::hash($code))->lockForUpdate()->first();
            if (! $row) {
                return $fail(404, 'invalid_code', 'ไม่พบโค้ดนี้ ตรวจตัวอักษรอีกครั้ง');
            }
            $item = $row->item()->with('campaign')->first();
            if ($item->campaign->slug !== $game) {
                return $fail(409, 'wrong_game', 'โค้ดนี้ใช้กับเกม ' . $item->campaign->name);
            }
            if ($row->status !== 'granted') {
                return $fail(410, 'revoked', 'โค้ดนี้ถูกยกเลิกแล้ว');
            }
            $deviceHash = hash_hmac('sha256', $device, (string) config('app.key'));
            $seen = $row->redemptions()->where('device_hash', $deviceHash)->exists();
            if (! $seen) {
                if ($row->redeem_count >= $item->max_devices) {
                    return $fail(409, 'device_limit', 'โค้ดนี้ใช้ครบ ' . $item->max_devices . ' เครื่องแล้ว');
                }
                GameItemRedemption::create(['game_entitlement_id' => $row->id, 'device_hash' => $deviceHash, 'ip_hash' => $ip ? hash_hmac('sha256', $ip, (string) config('app.key')) : null]);
                $row->update(['redeem_count' => $row->redeem_count + 1, 'first_redeemed_at' => $row->first_redeemed_at ?? now(), 'last_redeemed_at' => now()]);
            }

            return ['status' => 200, 'body' => [
                'ok' => true,
                'game' => $game,
                'item' => ['key' => $item->key, 'name' => $item->name, 'kind' => $item->kind, 'description' => $item->description, 'image_url' => $item->image_url],
                'devices_used' => $row->redeem_count,
                'devices_max' => $item->max_devices,
                'already_on_this_device' => $seen,
            ]];
        });
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\GameItemService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * POST /api/gameshub/redeem {game, code, device} — called by the games themselves
 * (browser games on xgameshub.xman4289.com/play/<id>/), so it is open to any origin,
 * carries no cookies, and answers in JSON with a Thai message the game can show as is.
 */
class GameItemRedeemController extends Controller
{
    public function __invoke(Request $request, GameItemService $items)
    {
        $v = Validator::make($request->all(), [
            'game' => ['required', 'string', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'max:80'],
            'code' => ['required', 'string', 'max:40'],
            // a random id the game keeps in its own save, so one device redeems once
            'device' => ['required', 'string', 'regex:/^[A-Za-z0-9_-]{8,128}$/'],
        ]);
        if ($v->fails()) {
            return response()->json(['ok' => false, 'error' => 'invalid_request', 'message' => 'ข้อมูลไม่ครบ ตรวจโค้ดแล้วลองใหม่', 'fields' => array_keys($v->errors()->toArray())], 422);
        }
        $data = $v->validated();
        $result = $items->redeem($data['game'], $data['code'], $data['device'], $request->ip());

        return response()->json($result['body'], $result['status'])->header('Cache-Control', 'no-store');
    }
}

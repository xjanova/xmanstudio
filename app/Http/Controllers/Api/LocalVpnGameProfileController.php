<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LocalVpnFleet\GameProfiles;
use Illuminate\Http\JsonResponse;

/**
 * GET /api/v1/localvpn/game-profiles — the game server ranges the app sends
 * through a game exit (split tunnel). Public, cached five minutes.
 */
class LocalVpnGameProfileController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['profiles' => GameProfiles::publicList()])
            ->header('Cache-Control', 'public, max-age=300');
    }
}

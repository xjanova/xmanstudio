<?php

namespace App\Http\Controllers;

use App\Services\AixmanService;

/**
 * XdreamerController
 *
 * Renders the X-DREAMER AI platform pages with live data from AIXMAN
 * (via AixmanService). Falls back gracefully to defaults / placeholders
 * when AIXMAN tables/API are unavailable (local dev, offline).
 */
class XdreamerController extends Controller
{
    public function __construct(private AixmanService $aixman) {}

    public function home()
    {
        return view('xdreamer.home', [
            'page'     => 'home',
            'packages' => $this->aixman->getPackages(),
        ]);
    }

    public function studio()
    {
        $userId = auth()->id();
        $credits = $userId ? $this->aixman->getUserCredits($userId) : null;

        return view('xdreamer.studio', [
            'page'    => 'studio',
            'credits' => $credits,
        ]);
    }

    public function dashboard()
    {
        $userId = auth()->id();
        $credits = $userId ? $this->aixman->getUserCredits($userId) : null;
        $stats = $userId ? $this->aixman->getUserGenerationStats($userId) : null;

        return view('xdreamer.dashboard', [
            'page'    => 'dashboard',
            'credits' => $credits,
            'stats'   => $stats,
        ]);
    }

    public function gallery()
    {
        return view('xdreamer.gallery', [
            'page'        => 'gallery',
            'generations' => $this->aixman->getPublicGenerations(24),
        ]);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiprayAudioSample;
use App\Models\AiprayAiModel;
use App\Models\AiprayDonation;
use App\Models\AiprayPrayerSession;
use App\Models\AiprayTrainingJob;
use App\Services\AiprayMlServiceClient;

class AiprayDashboardController extends Controller
{
    public function index()
    {
        $ml = new AiprayMlServiceClient();

        $stats = [
            'total_samples' => AiprayAudioSample::count(),
            'verified_samples' => AiprayAudioSample::verified()->count(),
            'total_hours' => round(AiprayAudioSample::sum('duration') / 3600, 1),
            'total_sessions' => AiprayPrayerSession::count(),
            'total_models' => AiprayAiModel::count(),
            'best_accuracy' => AiprayAiModel::max('accuracy'),
            'active_jobs' => AiprayTrainingJob::where('status', 'running')->count(),
            'total_donations' => AiprayDonation::completed()->sum('amount'),
            'ml_healthy' => $ml->isHealthy(),
        ];

        $recentJobs = AiprayTrainingJob::latest()->limit(5)->get();
        $recentSessions = AiprayPrayerSession::latest()->limit(10)->get();

        return view('admin.aipray.dashboard', compact('stats', 'recentJobs', 'recentSessions'));
    }
}

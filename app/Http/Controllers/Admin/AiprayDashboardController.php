<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiprayAiModel;
use App\Models\AiprayAudioSample;
use App\Models\AiprayDonation;
use App\Models\AiprayPrayerSession;
use App\Models\AiprayTrainingJob;
use App\Services\AiprayMlServiceClient;

class AiprayDashboardController extends Controller
{
    public function index()
    {
        try {
            $ml = new AiprayMlServiceClient;
            $mlHealthy = $ml->isHealthy();
        } catch (\Exception $e) {
            $mlHealthy = false;
        }

        try {
            $stats = [
                'total_samples' => AiprayAudioSample::count(),
                'verified_samples' => AiprayAudioSample::verified()->count(),
                'total_hours' => round(AiprayAudioSample::sum('duration') / 3600, 1),
                'total_sessions' => AiprayPrayerSession::count(),
                'total_models' => AiprayAiModel::count(),
                'best_accuracy' => AiprayAiModel::max('accuracy'),
                'active_jobs' => AiprayTrainingJob::where('status', 'running')->count(),
                'total_donations' => AiprayDonation::completed()->sum('amount'),
                'ml_healthy' => $mlHealthy,
            ];
        } catch (\Exception $e) {
            $stats = [
                'total_samples' => 0, 'verified_samples' => 0, 'total_hours' => 0,
                'total_sessions' => 0, 'total_models' => 0, 'best_accuracy' => null,
                'active_jobs' => 0, 'total_donations' => 0, 'ml_healthy' => false,
            ];
        }

        try {
            $recentJobs = AiprayTrainingJob::latest()->limit(5)->get();
        } catch (\Exception $e) {
            $recentJobs = collect();
        }

        try {
            $recentSessions = AiprayPrayerSession::latest()->limit(10)->get();
        } catch (\Exception $e) {
            $recentSessions = collect();
        }

        return view('admin.aipray.dashboard', compact('stats', 'recentJobs', 'recentSessions'));
    }
}

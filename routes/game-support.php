<?php

use App\Http\Controllers\Admin\GameSupportController as AdminGameSupport;
use App\Http\Controllers\GameSupportController;
use App\Models\GameCampaign;
use Illuminate\Support\Facades\Route;

Route::get('/games-support', [GameSupportController::class, 'index'])->name('game-support.index');
Route::get('/games-support/summary.json', [GameSupportController::class, 'summary'])->middleware('throttle:120,1')->name('game-support.summary');
Route::get('/games-support/{campaign}', [GameSupportController::class, 'show'])->name('game-support.show');
Route::get('/games-support/{campaign}/join', fn (GameCampaign $campaign) => redirect()->route('game-support.show', $campaign))->middleware('auth')->name('game-support.join');
Route::middleware(['auth', 'throttle:10,1'])->prefix('games-support/{campaign}')->name('game-support.')->group(function () {
    Route::post('/donations', [GameSupportController::class, 'donate'])->middleware('throttle:5,60')->name('donate');
    Route::post('/vote', [GameSupportController::class, 'vote'])->name('vote');
    Route::post('/rating', [GameSupportController::class, 'rate'])->name('rate');
    Route::post('/comments', [GameSupportController::class, 'comment'])->middleware('throttle:5,60')->name('comment');
});
Route::middleware(['auth', 'admin'])->prefix('admin/game-support')->name('admin.game-support.')->group(function () {
    Route::get('/', [AdminGameSupport::class, 'index'])->name('index');
    Route::get('/donations/{donation}/slip', [AdminGameSupport::class, 'slip'])->name('slip');
    Route::post('/donations/{donation}/review', [AdminGameSupport::class, 'review'])->name('review');
    Route::post('/donations/{donation}/reward', [AdminGameSupport::class, 'reward'])->name('reward');
    Route::post('/donations/{donation}/void', [AdminGameSupport::class, 'void'])->name('void');
    Route::post('/comments/{comment}', [AdminGameSupport::class, 'moderate'])->name('moderate');
    Route::post('/campaigns', [AdminGameSupport::class, 'campaign'])->name('campaign.create');
    Route::post('/campaigns/{campaign}', [AdminGameSupport::class, 'campaign'])->name('campaign.update');
});

<?php

use App\Http\Controllers\Admin\GamesHubController as AdminGamesHub;
use App\Http\Controllers\Admin\GameSupportController as AdminGameSupport;
use App\Http\Controllers\GamesHubController;
use App\Http\Controllers\GameSupportController;
use App\Models\GameCampaign;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

// Public JSON read by the static hub on every visit: no session file, no cookies, so it
// costs nothing per visitor and a CDN may cache it (Cache-Control: public, max-age).
$stateless = [StartSession::class, ShareErrorsFromSession::class, ValidateCsrfToken::class, AddQueuedCookiesToResponse::class];

Route::get('/games-support', [GameSupportController::class, 'index'])->name('game-support.index');
Route::get('/games-support/summary.json', [GameSupportController::class, 'summary'])->middleware('throttle:120,1')->withoutMiddleware($stateless)->name('game-support.summary');
// read by the static XGamesHub site: announcements and the hero order
Route::get('/games-support/hub.json', [GamesHubController::class, 'hub'])->middleware('throttle:120,1')->withoutMiddleware($stateless)->name('game-support.hub');
Route::get('/games-support/my-items', [GamesHubController::class, 'myItems'])->middleware('auth')->name('game-support.my-items');
Route::get('/games-support/{campaign}', [GameSupportController::class, 'show'])->name('game-support.show');
Route::get('/games-support/{campaign}/reviews.json', [GamesHubController::class, 'reviewsJson'])->middleware('throttle:120,1')->withoutMiddleware($stateless)->name('game-support.reviews-json');
Route::get('/games-support/{campaign}/join', fn (GameCampaign $campaign) => redirect()->route('game-support.show', $campaign))->middleware('auth')->name('game-support.join');
Route::middleware(['auth', 'throttle:10,1'])->prefix('games-support/{campaign}')->name('game-support.')->group(function () {
    Route::post('/donations', [GameSupportController::class, 'donate'])->middleware('throttle:5,60')->name('donate');
    Route::post('/vote', [GameSupportController::class, 'vote'])->name('vote');
    Route::post('/rating', [GameSupportController::class, 'rate'])->name('rate');
    Route::post('/comments', [GameSupportController::class, 'comment'])->middleware('throttle:5,60')->name('comment');
    Route::post('/review', [GamesHubController::class, 'review'])->middleware('throttle:5,60,gs-review')->name('review');
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

// XGamesHub back office (slip review above stays at /admin/game-support)
Route::middleware(['auth', 'admin'])->prefix('admin/gameshub')->name('admin.gameshub.')->controller(AdminGamesHub::class)->group(function () {
    Route::get('/', 'dashboard')->name('dashboard');
    Route::get('/items', 'items')->name('items');
    Route::post('/items', 'storeItem')->name('items.store');
    Route::post('/items/{item}', 'updateItem')->name('items.update');
    Route::post('/entitlements', 'grant')->name('entitlements.grant');
    Route::post('/entitlements/{entitlement}/revoke', 'revokeEntitlement')->name('entitlements.revoke');
    Route::get('/reviews', 'reviews')->name('reviews');
    Route::post('/reviews/bulk', 'bulkReviews')->name('reviews.bulk');
    Route::post('/reviews/{review}', 'moderateReview')->name('reviews.moderate');
    Route::post('/reviews/{review}/feature', 'featureReview')->name('reviews.feature');
    Route::get('/comments', 'comments')->name('comments');
    Route::post('/comments/bulk', 'bulkComments')->name('comments.bulk');
    Route::get('/games', 'games')->name('games');
    Route::post('/games/{campaign}/hero', 'updateHero')->name('games.hero');
    Route::get('/announcements', 'announcements')->name('announcements');
    Route::post('/announcements', 'storeAnnouncement')->name('announcements.store');
    Route::post('/announcements/{announcement}', 'updateAnnouncement')->name('announcements.update');
    Route::delete('/announcements/{announcement}', 'destroyAnnouncement')->name('announcements.destroy');
});

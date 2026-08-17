<?php

use App\Http\Controllers\CampaignViewController;
use App\Http\Controllers\SsoCallbackController;
use App\Http\Controllers\UnsubscribeController;
use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Socialite;

Route::view('/', 'welcome')->name('home');

Route::match(['get', 'post'], '/unsubscribe/{subscriber}', UnsubscribeController::class)
    ->middleware('signed')
    ->name('unsubscribe');

Route::get('/campaigns/{campaign}/view/{subscriber}', CampaignViewController::class)
    ->middleware('signed')
    ->name('campaigns.view');

Route::get('auth/redirect', fn () => Socialite::driver('authelia')->redirect())
    ->name('auth.redirect');

Route::get('auth/callback', SsoCallbackController::class)
    ->name('auth.callback');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';

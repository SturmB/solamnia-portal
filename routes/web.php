<?php

use App\Http\Controllers\CampaignViewController;
use App\Http\Controllers\UnsubscribeController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::match(['get', 'post'], '/unsubscribe/{subscriber}', UnsubscribeController::class)
    ->middleware('signed')
    ->name('unsubscribe');

Route::get('/campaigns/{campaign}/view/{subscriber}', CampaignViewController::class)
    ->middleware('signed')
    ->name('campaigns.view');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';

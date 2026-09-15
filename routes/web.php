<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TelescopeMonitoringController;

/*
|--------------------------------------------------------------------------
| Telescope Monitoring Center
|--------------------------------------------------------------------------
|
| Custom monitoring features built on top of Laravel Telescope.
|
*/

Route::prefix('monitoring')->group(function () {

    Route::get('/', [
        TelescopeMonitoringController::class,
        'dashboard'
    ])->name('monitoring.dashboard');

    Route::get('/activity', [
        TelescopeMonitoringController::class,
        'activity'
    ])->name('monitoring.activity');

    Route::get('/alerts', [
        TelescopeMonitoringController::class,
        'alerts'
    ])->name('monitoring.alerts');

});


<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TelescopeMonitoringController;

/*
|--------------------------------------------------------------------------
| Telescope Monitoring Center
|--------------------------------------------------------------------------
*/

Route::prefix('monitoring')->group(function () {

    /*
     * Dashboard
     */
    Route::get('/', [
        TelescopeMonitoringController::class,
        'dashboard'
    ])->name('monitoring.dashboard');

    /*
     * Activity search
     */
    Route::get('/activity', [
        TelescopeMonitoringController::class,
        'activity'
    ])->name('monitoring.activity');

    /*
     * Alerts
     */
    Route::get('/alerts', [
        TelescopeMonitoringController::class,
        'alerts'
    ])->name('monitoring.alerts');

    /*
     * Feature 6:
     * CSV export
     */
    Route::get('/activity/export', [
        TelescopeMonitoringController::class,
        'export'
    ])->name('monitoring.activity.export');

    /*
     * Feature 7:
     * Cleanup old Telescope data
     */
    Route::delete('/cleanup', [
        TelescopeMonitoringController::class,
        'cleanup'
    ])->name('monitoring.cleanup');

});
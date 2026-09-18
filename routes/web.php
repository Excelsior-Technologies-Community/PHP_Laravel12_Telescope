<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TelescopeMonitoringController;

/*
|--------------------------------------------------------------------------
| Root Redirect
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect()->route('monitoring.dashboard');
});

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
     * Module 2: Security Threat & Malicious Request Inspector
     */
    Route::get('/security', [
        TelescopeMonitoringController::class,
        'security'
    ])->name('monitoring.security');

    /*
     * Module 3: API Health Check & Latency Distribution
     */
    Route::get('/health', [
        TelescopeMonitoringController::class,
        'health'
    ])->name('monitoring.health');

    Route::get('/health/probe', [
        TelescopeMonitoringController::class,
        'healthProbe'
    ])->name('monitoring.health.probe');

    /*
     * Module 1: Simulated Traffic & Error Generator
     */
    Route::post('/simulate/{type}', [
        TelescopeMonitoringController::class,
        'simulate'
    ])->name('monitoring.simulate');

    /*
     * Activity CSV export
     */
    Route::get('/activity/export', [
        TelescopeMonitoringController::class,
        'export'
    ])->name('monitoring.activity.export');

    /*
     * Cleanup old Telescope data
     */
    Route::delete('/cleanup', [
        TelescopeMonitoringController::class,
        'cleanup'
    ])->name('monitoring.cleanup');

});
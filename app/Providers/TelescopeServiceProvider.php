<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\TelescopeApplicationServiceProvider;

class TelescopeServiceProvider extends TelescopeApplicationServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Optional dark mode
        // Telescope::night();

        $this->hideSensitiveRequestDetails();

        $isLocal = $this->app->environment('local');

        Telescope::filter(function (IncomingEntry $entry) use ($isLocal) {

            /**
             *  Ignore service worker request (/sw.js)
             */
            if (
                $entry->type === 'request' &&
                isset($entry->content['uri']) &&
                $entry->content['uri'] === 'sw.js'
            ) {
                return false;
            }

            /**
             *  LOCAL: log EVERYTHING (requests, queries, jobs, etc.)
             */
            if ($isLocal) {
                return true;
            }

            /**
             *  NON-LOCAL (staging/production):
             * log only important things
             */
            return
                $entry->isReportableException() ||
                $entry->isFailedRequest() ||
                $entry->isFailedJob() ||
                $entry->isScheduledTask() ||
                $entry->hasMonitoredTag();
        });
    }

    /**
     * Hide sensitive request data.
     */
    protected function hideSensitiveRequestDetails(): void
    {
        if ($this->app->environment('local')) {
            return;
        }

        Telescope::hideRequestParameters([
            '_token',
            'password',
            'password_confirmation',
        ]);

        Telescope::hideRequestHeaders([
            'cookie',
            'x-csrf-token',
            'x-xsrf-token',
            'authorization',
        ]);
    }

    /**
     * Telescope access gate (non-local environments).
     */
    protected function gate(): void
    {
        Gate::define('viewTelescope', function ($user) {
            return isset($user->email)
                && $user->email === 'admin@example.com';
        });
    }
}

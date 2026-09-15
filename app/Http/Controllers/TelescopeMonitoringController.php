<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class TelescopeMonitoringController extends Controller
{
    /**
     * Telescope monitoring dashboard.
     */
    public function dashboard()
    {
        $this->ensureLocalEnvironment();

        $totalEntries = DB::table('telescope_entries')->count();

        $requests = DB::table('telescope_entries')
            ->where('type', 'request')
            ->count();

        $exceptions = DB::table('telescope_entries')
            ->where('type', 'exception')
            ->count();

        $queries = DB::table('telescope_entries')
            ->where('type', 'query')
            ->count();

        $jobs = DB::table('telescope_entries')
            ->where('type', 'job')
            ->count();

        /*
         * Failed requests.
         */
        $failedRequests = 0;

        $requestEntries = DB::table('telescope_entries')
            ->where('type', 'request')
            ->select('content')
            ->get();

        foreach ($requestEntries as $entry) {
            $content = $this->decodeContent($entry->content);

            $status = $this->getRequestStatus($content);

            if ($status >= 400) {
                $failedRequests++;
            }
        }

        /*
         * Slow queries.
         *
         * Telescope config currently uses:
         *
         * 'slow' => 100
         *
         * Therefore 100ms is considered the slow-query threshold.
         */
        $slowQueries = 0;

        $queryEntries = DB::table('telescope_entries')
            ->where('type', 'query')
            ->select('content')
            ->get();

        foreach ($queryEntries as $entry) {
            $content = $this->decodeContent($entry->content);

            $duration = $this->getQueryDuration($content);

            if ($duration >= 100) {
                $slowQueries++;
            }
        }

        /*
         * Recent activity.
         */
        $recentEntries = DB::table('telescope_entries')
            ->select([
                'sequence',
                'uuid',
                'type',
                'content',
                'created_at',
            ])
            ->orderByDesc('sequence')
            ->limit(10)
            ->get();

        $recentEntries = $recentEntries->map(function ($entry) {
            $content = $this->decodeContent($entry->content);

            return [
                'type' => $entry->type,
                'description' => $this->getDescription($entry->type, $content),
                'status' => $this->getStatus($entry->type, $content),
                'created_at' => $entry->created_at,
            ];
        });

        /*
         * Activity counts.
         */
        $activityCounts = DB::table('telescope_entries')
            ->select('type', DB::raw('COUNT(*) as total'))
            ->groupBy('type')
            ->orderByDesc('total')
            ->get();

        return view('telescope.monitoring.dashboard', compact(
            'totalEntries',
            'requests',
            'exceptions',
            'queries',
            'jobs',
            'failedRequests',
            'slowQueries',
            'recentEntries',
            'activityCounts'
        ));
    }

    /**
     * Advanced Telescope activity search.
     */
    public function activity(Request $request)
    {
        $this->ensureLocalEnvironment();

        $query = DB::table('telescope_entries')
            ->select([
                'sequence',
                'uuid',
                'type',
                'content',
                'created_at',
            ]);

        /*
         * Filter by Telescope entry type.
         */
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        /*
         * Date filtering.
         */
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        /*
         * Keyword search.
         *
         * Telescope stores entry information as JSON.
         * Searching the content allows us to search URI,
         * exception message, query, job name, etc.
         */
        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('content', 'like', '%' . $search . '%')
                    ->orWhere('uuid', 'like', '%' . $search . '%');
            });
        }

        $entries = $query
            ->orderByDesc('sequence')
            ->paginate(15)
            ->withQueryString();

        $entries->getCollection()->transform(function ($entry) {
            $content = $this->decodeContent($entry->content);

            return [
                'sequence' => $entry->sequence,
                'uuid' => $entry->uuid,
                'type' => $entry->type,
                'description' => $this->getDescription($entry->type, $content),
                'status' => $this->getStatus($entry->type, $content),
                'created_at' => $entry->created_at,
                'content' => $content,
            ];
        });

        return view('telescope.monitoring.activity', [
            'entries' => $entries,
        ]);
    }

    /**
     * Error and performance alerts.
     */
    public function alerts()
    {
        $this->ensureLocalEnvironment();

        $alerts = collect();

        /*
         * Failed requests.
         */
        $requestEntries = DB::table('telescope_entries')
            ->where('type', 'request')
            ->orderByDesc('sequence')
            ->limit(300)
            ->get();

        foreach ($requestEntries as $entry) {
            $content = $this->decodeContent($entry->content);

            $status = $this->getRequestStatus($content);

            if ($status >= 400) {
                $alerts->push([
                    'severity' => $status >= 500 ? 'critical' : 'warning',
                    'type' => 'Failed Request',
                    'icon' => 'bi-globe',
                    'title' => $this->getRequestMethod($content) . ' ' .
                        $this->getRequestUri($content),
                    'description' => 'HTTP response status: ' . $status,
                    'created_at' => $entry->created_at,
                ]);
            }
        }

        /*
         * Exceptions.
         */
        $exceptionEntries = DB::table('telescope_entries')
            ->where('type', 'exception')
            ->orderByDesc('sequence')
            ->limit(300)
            ->get();

        foreach ($exceptionEntries as $entry) {
            $content = $this->decodeContent($entry->content);

            $message = $content['message']
                ?? $content['exception']
                ?? 'Application exception detected';

            $alerts->push([
                'severity' => 'critical',
                'type' => 'Exception',
                'icon' => 'bi-bug',
                'title' => class_basename($content['class'] ?? 'Exception'),
                'description' => $message,
                'created_at' => $entry->created_at,
            ]);
        }

        /*
         * Failed jobs.
         */
        $jobEntries = DB::table('telescope_entries')
            ->where('type', 'job')
            ->orderByDesc('sequence')
            ->limit(300)
            ->get();

        foreach ($jobEntries as $entry) {
            $content = $this->decodeContent($entry->content);

            $failed = false;

            if (
                isset($content['status']) &&
                in_array(
                    strtolower((string) $content['status']),
                    ['failed', 'failure']
                )
            ) {
                $failed = true;
            }

            if (
                isset($content['exception']) ||
                isset($content['exception_class'])
            ) {
                $failed = true;
            }

            if ($failed) {
                $alerts->push([
                    'severity' => 'critical',
                    'type' => 'Failed Job',
                    'icon' => 'bi-cpu',
                    'title' => $content['name']
                        ?? $content['display_name']
                        ?? 'Queue Job',
                    'description' => 'A queued job failed.',
                    'created_at' => $entry->created_at,
                ]);
            }
        }

        /*
         * Slow queries.
         */
        $queryEntries = DB::table('telescope_entries')
            ->where('type', 'query')
            ->orderByDesc('sequence')
            ->limit(300)
            ->get();

        foreach ($queryEntries as $entry) {
            $content = $this->decodeContent($entry->content);

            $duration = $this->getQueryDuration($content);

            if ($duration >= 100) {
                $sql = $content['sql']
                    ?? $content['query']
                    ?? 'Slow database query';

                $alerts->push([
                    'severity' => $duration >= 1000
                        ? 'critical'
                        : 'warning',
                    'type' => 'Slow Query',
                    'icon' => 'bi-database',
                    'title' => number_format($duration, 2) . ' ms',
                    'description' => $sql,
                    'created_at' => $entry->created_at,
                ]);
            }
        }

        /*
         * Latest alerts first.
         */
        $alerts = $alerts
            ->sortByDesc(function ($alert) {
                return $alert['created_at'];
            })
            ->values();

        return view('telescope.monitoring.alerts', [
            'alerts' => $alerts,
        ]);
    }

    /**
     * Decode Telescope JSON content.
     */
    private function decodeContent($content): array
    {
        if (is_array($content)) {
            return $content;
        }

        $decoded = json_decode($content ?? '{}', true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Make a human-readable activity description.
     */
    private function getDescription(string $type, array $content): string
    {
        return match ($type) {
            'request' => $this->getRequestMethod($content)
                . ' '
                . $this->getRequestUri($content),

            'query' => $content['sql']
                ?? $content['query']
                ?? 'Database query',

            'exception' => $content['message']
                ?? $content['exception']
                ?? 'Application exception',

            'job' => $content['name']
                ?? $content['display_name']
                ?? 'Queue job',

            'mail' => $content['subject']
                ?? $content['mailable']
                ?? 'Email',

            'notification' => $content['notification']
                ?? 'Notification',

            'event' => $content['name']
                ?? 'Application event',

            'model' => $content['model']
                ?? 'Eloquent model activity',

            'cache' => $content['key']
                ?? 'Cache activity',

            'log' => $content['message']
                ?? 'Application log',

            'command' => $content['command']
                ?? $content['command_name']
                ?? 'Artisan command',

            default => ucfirst($type) . ' activity',
        };
    }

    /**
     * Get status label.
     */
    private function getStatus(string $type, array $content): string
    {
        if ($type === 'request') {
            return (string) $this->getRequestStatus($content);
        }

        if ($type === 'query') {
            $duration = $this->getQueryDuration($content);

            return number_format($duration, 2) . ' ms';
        }

        if ($type === 'exception') {
            return 'Exception';
        }

        return 'Recorded';
    }

    /**
     * Get request HTTP status.
     */
    private function getRequestStatus(array $content): int
    {
        $status = $content['response_status']
            ?? $content['status']
            ?? $content['status_code']
            ?? 200;

        return (int) $status;
    }

    /**
     * Get request method.
     */
    private function getRequestMethod(array $content): string
    {
        return strtoupper(
            $content['method']
            ?? $content['http_method']
            ?? 'GET'
        );
    }

    /**
     * Get request URI.
     */
    private function getRequestUri(array $content): string
    {
        return $content['uri']
            ?? $content['url']
            ?? $content['path']
            ?? '/';
    }

    /**
     * Get query execution duration.
     *
     * Telescope normally stores query duration in milliseconds.
     */
    private function getQueryDuration(array $content): float
    {
        return (float) (
            $content['duration']
            ?? $content['time']
            ?? $content['execution_time']
            ?? 0
        );
    }

    /**
     * Prevent exposing monitoring dashboard outside local environment.
     */
    private function ensureLocalEnvironment(): void
    {
        abort_unless(
            app()->environment('local'),
            403,
            'The monitoring dashboard is available only in the local environment.'
        );
    }
}

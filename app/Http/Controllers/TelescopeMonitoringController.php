<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TelescopeMonitoringController extends Controller
{
    /**
     * Telescope monitoring dashboard.
     */
    public function dashboard(Request $request)
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

            if ($this->getRequestStatus($content) >= 400) {
                $failedRequests++;
            }
        }

        /*
         * Slow queries.
         */
        $slowQueries = 0;

        $queryEntries = DB::table('telescope_entries')
            ->where('type', 'query')
            ->select('content')
            ->get();

        foreach ($queryEntries as $entry) {
            $content = $this->decodeContent($entry->content);

            if ($this->getQueryDuration($content) >= 100) {
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
                'description' => $this->getDescription(
                    $entry->type,
                    $content
                ),
                'status' => $this->getStatus(
                    $entry->type,
                    $content
                ),
                'created_at' => $entry->created_at,
            ];
        });

        /*
         * Activity counts.
         */
        $activityCounts = DB::table('telescope_entries')
            ->select(
                'type',
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('type')
            ->orderByDesc('total')
            ->get();

        /*
         * Feature 4:
         * Top requested URLs.
         */
        $topRoutes = collect();

        $topRequestEntries = DB::table('telescope_entries')
            ->where('type', 'request')
            ->select('content')
            ->orderByDesc('sequence')
            ->limit(2000)
            ->get();

        foreach ($topRequestEntries as $entry) {
            $content = $this->decodeContent($entry->content);

            $uri = $this->getRequestUri($content);

            if (!$uri) {
                continue;
            }

            $existing = $topRoutes->firstWhere('uri', $uri);

            if ($existing) {
                $existing['total']++;
            } else {
                $topRoutes->push([
                    'uri' => $uri,
                    'total' => 1,
                ]);
            }
        }

        $topRoutes = $topRoutes
            ->sortByDesc('total')
            ->take(10)
            ->values();

        /*
         * Feature 5:
         * Slowest queries.
         */
        $slowestQueries = collect();

        $slowQueryEntries = DB::table('telescope_entries')
            ->where('type', 'query')
            ->select([
                'content',
                'created_at',
            ])
            ->orderByDesc('sequence')
            ->limit(1000)
            ->get();

        foreach ($slowQueryEntries as $entry) {
            $content = $this->decodeContent($entry->content);

            $duration = $this->getQueryDuration($content);

            if ($duration >= 100) {
                $slowestQueries->push([
                    'sql' => $content['sql']
                        ?? $content['query']
                        ?? 'Database query',
                    'duration' => $duration,
                    'created_at' => $entry->created_at,
                ]);
            }
        }

        $slowestQueries = $slowestQueries
            ->sortByDesc('duration')
            ->take(10)
            ->values();

        /*
         * Feature 9:
         * Daily activity trend.
         */
        $dailyActivity = DB::table('telescope_entries')
            ->select(
                DB::raw('DATE(created_at) as activity_date'),
                DB::raw('COUNT(*) as total')
            )
            ->where(
                'created_at',
                '>=',
                now()->subDays(6)->startOfDay()
            )
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('activity_date')
            ->get();

        /*
         * Dashboard retention information.
         */
        $oldestEntry = DB::table('telescope_entries')
            ->orderBy('created_at')
            ->value('created_at');

        $newestEntry = DB::table('telescope_entries')
            ->orderByDesc('created_at')
            ->value('created_at');

        return view(
            'telescope.monitoring.dashboard',
            compact(
                'totalEntries',
                'requests',
                'exceptions',
                'queries',
                'jobs',
                'failedRequests',
                'slowQueries',
                'recentEntries',
                'activityCounts',
                'topRoutes',
                'slowestQueries',
                'dailyActivity',
                'oldestEntry',
                'newestEntry'
            )
        );
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
         * Feature 2:
         * Activity type filter.
         */
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        /*
         * Feature 1:
         * Date presets.
         */
        if ($request->filled('preset')) {
            switch ($request->preset) {
                case 'today':
                    $query->whereDate(
                        'created_at',
                        now()->toDateString()
                    );
                    break;

                case '7days':
                    $query->where(
                        'created_at',
                        '>=',
                        now()->subDays(7)
                    );
                    break;

                case '30days':
                    $query->where(
                        'created_at',
                        '>=',
                        now()->subDays(30)
                    );
                    break;
            }
        }

        /*
         * Custom date filtering.
         */
        if ($request->filled('date_from')) {
            $query->whereDate(
                'created_at',
                '>=',
                $request->date_from
            );
        }

        if ($request->filled('date_to')) {
            $query->whereDate(
                'created_at',
                '<=',
                $request->date_to
            );
        }

        /*
         * Feature 3:
         * HTTP method filtering.
         */
        if ($request->filled('method')) {

            $method = strtoupper($request->method);

            $query->where(
                'content',
                'like',
                '%"method":"' . $method . '"%'
            );
        }

        /*
         * Feature 2:
         * HTTP status filtering.
         */
        if ($request->filled('status')) {

            $status = (int) $request->status;

            $query->where(function ($q) use ($status) {

                $q->where(
                    'content',
                    'like',
                    '%"response_status":' . $status . '%'
                );

                $q->orWhere(
                    'content',
                    'like',
                    '%"status":' . $status . '%'
                );

                $q->orWhere(
                    'content',
                    'like',
                    '%"status_code":' . $status . '%'
                );
            });
        }

        /*
         * Keyword search.
         */
        if ($request->filled('search')) {

            $search = $request->search;

            $query->where(function ($q) use ($search) {

                $q->where(
                    'content',
                    'like',
                    '%' . $search . '%'
                );

                $q->orWhere(
                    'uuid',
                    'like',
                    '%' . $search . '%'
                );
            });
        }

        $entries = $query
            ->orderByDesc('sequence')
            ->paginate(15)
            ->withQueryString();

        $entries->getCollection()->transform(
            function ($entry) {

                $content = $this->decodeContent(
                    $entry->content
                );

                return [
                    'sequence' => $entry->sequence,
                    'uuid' => $entry->uuid,
                    'type' => $entry->type,
                    'description' => $this->getDescription(
                        $entry->type,
                        $content
                    ),
                    'status' => $this->getStatus(
                        $entry->type,
                        $content
                    ),
                    'created_at' => $entry->created_at,
                    'content' => $content,
                ];
            }
        );

        return view(
            'telescope.monitoring.activity',
            [
                'entries' => $entries,
            ]
        );
    }

    /**
     * Feature 6:
     * Export filtered activity to CSV.
     */
    public function export(Request $request): StreamedResponse
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

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('date_from')) {
            $query->whereDate(
                'created_at',
                '>=',
                $request->date_from
            );
        }

        if ($request->filled('date_to')) {
            $query->whereDate(
                'created_at',
                '<=',
                $request->date_to
            );
        }

        if ($request->filled('preset')) {

            if ($request->preset === 'today') {
                $query->whereDate(
                    'created_at',
                    now()->toDateString()
                );
            }

            if ($request->preset === '7days') {
                $query->where(
                    'created_at',
                    '>=',
                    now()->subDays(7)
                );
            }

            if ($request->preset === '30days') {
                $query->where(
                    'created_at',
                    '>=',
                    now()->subDays(30)
                );
            }
        }

        if ($request->filled('search')) {

            $search = $request->search;

            $query->where(function ($q) use ($search) {

                $q->where(
                    'content',
                    'like',
                    '%' . $search . '%'
                );

                $q->orWhere(
                    'uuid',
                    'like',
                    '%' . $search . '%'
                );
            });
        }

        $entries = $query
            ->orderByDesc('sequence')
            ->cursor();

        $filename =
            'telescope_activity_' .
            now()->format('Y_m_d_H_i_s') .
            '.csv';

        return response()->streamDownload(
            function () use ($entries) {

                $handle = fopen('php://output', 'w');

                fputcsv(
                    $handle,
                    [
                        'Sequence',
                        'UUID',
                        'Type',
                        'Description',
                        'Status',
                        'Created At',
                    ]
                );

                foreach ($entries as $entry) {

                    $content = $this->decodeContent(
                        $entry->content
                    );

                    fputcsv(
                        $handle,
                        [
                            $entry->sequence,
                            $entry->uuid,
                            $entry->type,
                            $this->getDescription(
                                $entry->type,
                                $content
                            ),
                            $this->getStatus(
                                $entry->type,
                                $content
                            ),
                            $entry->created_at,
                        ]
                    );
                }

                fclose($handle);
            },
            $filename,
            [
                'Content-Type' => 'text/csv',
            ]
        );
    }

    /**
     * Feature 7:
     * Delete old Telescope records.
     */
    public function cleanup(Request $request)
    {
        $this->ensureLocalEnvironment();

        $validated = $request->validate([
            'days' => [
                'required',
                'integer',
                'in:7,15,30,60,90',
            ],
        ]);

        $cutoff = now()->subDays(
            (int) $validated['days']
        );

        $deleted = DB::table('telescope_entries')
            ->where('created_at', '<', $cutoff)
            ->delete();

        return redirect()
            ->route('monitoring.dashboard')
            ->with(
                'success',
                $deleted .
                ' Telescope entries older than ' .
                $validated['days'] .
                ' days were deleted.'
            );
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

            $content = $this->decodeContent(
                $entry->content
            );

            $status = $this->getRequestStatus($content);

            if ($status >= 400) {

                $alerts->push([
                    'severity' =>
                        $status >= 500
                            ? 'critical'
                            : 'warning',

                    'type' => 'Failed Request',

                    'icon' => 'bi-globe',

                    'title' =>
                        $this->getRequestMethod($content) .
                        ' ' .
                        $this->getRequestUri($content),

                    'description' =>
                        'HTTP response status: ' .
                        $status,

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

            $content = $this->decodeContent(
                $entry->content
            );

            $message =
                $content['message']
                ?? $content['exception']
                ?? 'Application exception detected';

            $alerts->push([
                'severity' => 'critical',
                'type' => 'Exception',
                'icon' => 'bi-bug',
                'title' => class_basename(
                    $content['class'] ?? 'Exception'
                ),
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

            $content = $this->decodeContent(
                $entry->content
            );

            $failed = false;

            if (
                isset($content['status']) &&
                in_array(
                    strtolower(
                        (string) $content['status']
                    ),
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
                    'title' =>
                        $content['name']
                        ?? $content['display_name']
                        ?? 'Queue Job',

                    'description' =>
                        'A queued job failed.',

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

            $content = $this->decodeContent(
                $entry->content
            );

            $duration =
                $this->getQueryDuration($content);

            if ($duration >= 100) {

                $sql =
                    $content['sql']
                    ?? $content['query']
                    ?? 'Slow database query';

                $alerts->push([
                    'severity' =>
                        $duration >= 1000
                            ? 'critical'
                            : 'warning',

                    'type' => 'Slow Query',

                    'icon' => 'bi-database',

                    'title' =>
                        number_format(
                            $duration,
                            2
                        ) . ' ms',

                    'description' => $sql,

                    'created_at' =>
                        $entry->created_at,
                ]);
            }
        }

        $alerts = $alerts
            ->sortByDesc(
                fn ($alert) =>
                    $alert['created_at']
            )
            ->values();

        return view(
            'telescope.monitoring.alerts',
            [
                'alerts' => $alerts,
            ]
        );
    }

    /**
     * Decode Telescope JSON content.
     */
    private function decodeContent($content): array
    {
        if (is_array($content)) {
            return $content;
        }

        $decoded = json_decode(
            $content ?? '{}',
            true
        );

        return is_array($decoded)
            ? $decoded
            : [];
    }

    /**
     * Human-readable activity description.
     */
    private function getDescription(
        string $type,
        array $content
    ): string {

        return match ($type) {

            'request' =>
                $this->getRequestMethod($content) .
                ' ' .
                $this->getRequestUri($content),

            'query' =>
                $content['sql']
                ?? $content['query']
                ?? 'Database query',

            'exception' =>
                $content['message']
                ?? $content['exception']
                ?? 'Application exception',

            'job' =>
                $content['name']
                ?? $content['display_name']
                ?? 'Queue job',

            'mail' =>
                $content['subject']
                ?? $content['mailable']
                ?? 'Email',

            'notification' =>
                $content['notification']
                ?? 'Notification',

            'event' =>
                $content['name']
                ?? 'Application event',

            'model' =>
                $content['model']
                ?? 'Eloquent model activity',

            'cache' =>
                $content['key']
                ?? 'Cache activity',

            'log' =>
                $content['message']
                ?? 'Application log',

            'command' =>
                $content['command']
                ?? $content['command_name']
                ?? 'Artisan command',

            default =>
                ucfirst($type) . ' activity',
        };
    }

    /**
     * Get status label.
     */
    private function getStatus(
        string $type,
        array $content
    ): string {

        if ($type === 'request') {
            return (string)
                $this->getRequestStatus($content);
        }

        if ($type === 'query') {

            $duration =
                $this->getQueryDuration($content);

            return number_format(
                $duration,
                2
            ) . ' ms';
        }

        if ($type === 'exception') {
            return 'Exception';
        }

        return 'Recorded';
    }

    /**
     * Get request HTTP status.
     */
    private function getRequestStatus(
        array $content
    ): int {

        return (int) (
            $content['response_status']
            ?? $content['status']
            ?? $content['status_code']
            ?? 200
        );
    }

    /**
     * Get request method.
     */
    private function getRequestMethod(
        array $content
    ): string {

        return strtoupper(
            $content['method']
            ?? $content['http_method']
            ?? 'GET'
        );
    }

    /**
     * Get request URI.
     */
    private function getRequestUri(
        array $content
    ): string {

        return $content['uri']
            ?? $content['url']
            ?? $content['path']
            ?? '/';
    }

    /**
     * Get query execution duration.
     */
    private function getQueryDuration(
        array $content
    ): float {

        return (float) (
            $content['duration']
            ?? $content['time']
            ?? $content['execution_time']
            ?? 0
        );
    }

    /**
     * Local environment only.
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
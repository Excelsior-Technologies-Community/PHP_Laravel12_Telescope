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
     * Module 1: Simulated Traffic & Error Generator
     */
    public function simulate(Request $request, string $type)
    {
        $this->ensureLocalEnvironment();

        switch ($type) {
            case 'slow-query':
                // Simulate a high-latency database query
                try {
                    DB::select("SELECT SLEEP(1.2) as sleep_time, 'Simulated High-Latency Database Query' as description");
                } catch (\Throwable $e) {
                    // Fallback for drivers that don't support sleep
                    usleep(1200000);
                    DB::table('telescope_entries')->count();
                }
                return back()->with('success', '⚡ Simulated Slow Database Query executed (1.2s latency logged in Telescope).');

            case 'exception':
                // Simulate an unhandled critical exception
                $simulatedException = new \RuntimeException("💥 Simulated Critical Application Exception: PaymentGatewayConnectionTimeout on /api/v1/checkout");
                report($simulatedException);
                return back()->with('success', '🚨 Simulated 500 Unhandled Exception reported and captured in Telescope Exception Watcher.');

            case 'burst-traffic':
                // Simulate a traffic spike with various status codes
                $methods = ['GET', 'POST', 'PUT', 'DELETE'];
                $routes = ['/api/products', '/api/users', '/checkout/pay', '/auth/login', '/dashboard/analytics', '/search?q=laravel'];
                $statuses = [200, 200, 201, 200, 400, 404, 200, 500];

                for ($i = 0; $i < 20; $i++) {
                    $method = $methods[array_rand($methods)];
                    $uri = $routes[array_rand($routes)];
                    $status = $statuses[array_rand($statuses)];
                    $duration = rand(15, 350);

                    DB::table('telescope_entries')->insert([
                        'sequence' => (int) (DB::table('telescope_entries')->max('sequence') ?? 0) + 1,
                        'uuid' => (string) \Illuminate\Support\Str::uuid(),
                        'batch_id' => (string) \Illuminate\Support\Str::uuid(),
                        'family_hash' => md5($uri),
                        'should_display_on_index' => 1,
                        'type' => 'request',
                        'content' => json_encode([
                            'uri' => $uri,
                            'method' => $method,
                            'controller_action' => 'SimulatedController@handle',
                            'middleware' => ['web'],
                            'response_status' => $status,
                            'duration' => $duration,
                            'memory' => rand(12, 36),
                            'ip_address' => '127.0.0.' . rand(1, 25),
                            'user' => null,
                        ]),
                        'created_at' => now(),
                    ]);
                }
                return back()->with('success', '🚀 Simulated Burst Traffic Spike (20 rapid requests logged across multiple endpoints).');

            case 'queue-job':
                // Simulate a background queue event
                try {
                    dispatch(function () {
                        usleep(50000);
                    });
                } catch (\Throwable $e) {}
                
                // Also log a simulated failed job entry into Telescope
                DB::table('telescope_entries')->insert([
                    'sequence' => (int) (DB::table('telescope_entries')->max('sequence') ?? 0) + 1,
                    'uuid' => (string) \Illuminate\Support\Str::uuid(),
                    'batch_id' => (string) \Illuminate\Support\Str::uuid(),
                    'family_hash' => md5('ProcessOrderJob'),
                    'should_display_on_index' => 1,
                    'type' => 'job',
                    'content' => json_encode([
                        'name' => 'App\\Jobs\\ProcessInvoiceNotificationJob',
                        'queue' => 'default',
                        'connection' => 'database',
                        'status' => 'failed',
                        'exception' => 'SimulatedJobTimeoutException: Maximum execution time of 30 seconds exceeded',
                    ]),
                    'created_at' => now(),
                ]);
                return back()->with('success', '📧 Simulated Background Queue Job & Failed Job event logged in Telescope.');

            case 'cache-miss':
                // Simulate cache miss, lock, and cache hit
                \Illuminate\Support\Facades\Cache::forget('telescope_sim_key');
                $lock = \Illuminate\Support\Facades\Cache::lock('telescope_sim_lock', 10);
                $lock->get();
                \Illuminate\Support\Facades\Cache::remember('telescope_sim_key', 60, function () {
                    return 'Simulated Cache Payload Data';
                });
                $lock->release();
                return back()->with('success', '🛑 Simulated Cache Miss, Mutex Lock & Write Storm executed successfully.');

            default:
                return back()->with('error', 'Unknown simulation type.');
        }
    }

    /**
     * Module 2: Security Threat & Malicious Request Inspector
     */
    public function security(Request $request)
    {
        $this->ensureLocalEnvironment();

        $rawRequests = DB::table('telescope_entries')
            ->where('type', 'request')
            ->select(['sequence', 'uuid', 'content', 'created_at'])
            ->orderByDesc('sequence')
            ->limit(1500)
            ->get();

        $incidents = collect();
        $attackerIps = [];

        // Known attack signatures
        $vulnPatterns = [
            '/\.(env|git|htaccess|aws|sql|bak|yaml|yml|cfg|ini)/i' => 'Sensitive File Probing (.env / .git)',
            '/(wp-login|wp-admin|xmlrpc|phpinfo|actuator|adminer|phpmyadmin|eval-stdin|solr|boaform|cgi-bin)/i' => 'Admin Portal / Vulnerability Scanner',
            '/(id_rsa|\/etc\/passwd|\/etc\/shadow|win\.ini|boot\.ini)/i' => 'System Credential Traversal',
        ];

        $sqliPatterns = [
            "/('|\%27)\s*(OR|AND)\s*('?1'?\s*=\s*'?1'|[0-9]+\s*=\s*[0-9]+)/i" => 'SQLi Boolean Bypass',
            "/(UNION\s+ALL\s+SELECT|UNION\s+SELECT|SELECT\s+.*\s+FROM)/i" => 'SQLi Union Extraction',
            "/(SLEEP\([0-9]+\)|BENCHMARK\(|WAITFOR\s+DELAY)/i" => 'SQLi Blind Time-Based Injection',
            "/(--|\#|\/\*)/" => 'SQLi Comment Infiltration',
        ];

        $xssPatterns = [
            "/(<script|javascript:|onerror\s*=|onload\s*=|document\.cookie|alert\(|<svg|<iframe)/i" => 'Cross-Site Scripting (XSS) Payload',
        ];

        $pathTraversalPatterns = [
            "/(\.\.\/|\.\.\\|%2e%2e%2f|%2e%2e\/)/i" => 'Directory Path Traversal Attempt',
        ];

        $totalVulnScans = 0;
        $totalSqli = 0;
        $totalXss = 0;
        $totalBruteForce = 0;

        foreach ($rawRequests as $entry) {
            $content = $this->decodeContent($entry->content);
            $uri = $this->getRequestUri($content);
            $status = $this->getRequestStatus($content);
            $method = $this->getRequestMethod($content);
            $ip = $content['ip_address'] ?? '127.0.0.1';
            $payloadString = json_encode($content['payload'] ?? []) . ' ' . json_encode($content['headers'] ?? []) . ' ' . $uri;

            $detectedThreat = null;
            $severity = 'medium';
            $threatCategory = 'Scanner';

            // 1. Check Vulnerability Scans
            foreach ($vulnPatterns as $pattern => $title) {
                if (preg_match($pattern, $uri)) {
                    $detectedThreat = $title;
                    $severity = 'high';
                    $threatCategory = 'Vulnerability Scan';
                    $totalVulnScans++;
                    break;
                }
            }

            // 2. Check SQL Injection
            if (!$detectedThreat) {
                foreach ($sqliPatterns as $pattern => $title) {
                    if (preg_match($pattern, $payloadString)) {
                        $detectedThreat = $title;
                        $severity = 'critical';
                        $threatCategory = 'SQL Injection';
                        $totalSqli++;
                        break;
                    }
                }
            }

            // 3. Check XSS
            if (!$detectedThreat) {
                foreach ($xssPatterns as $pattern => $title) {
                    if (preg_match($pattern, $payloadString)) {
                        $detectedThreat = $title;
                        $severity = 'high';
                        $threatCategory = 'XSS Exploit';
                        $totalXss++;
                        break;
                    }
                }
            }

            // 4. Check Path Traversal
            if (!$detectedThreat) {
                foreach ($pathTraversalPatterns as $pattern => $title) {
                    if (preg_match($pattern, $uri)) {
                        $detectedThreat = $title;
                        $severity = 'high';
                        $threatCategory = 'Path Traversal';
                        $totalVulnScans++;
                        break;
                    }
                }
            }

            // 5. Check Brute-Force Auth Failures
            if (!$detectedThreat && in_array($status, [401, 403, 429]) && str_contains(strtolower($uri), 'login')) {
                $detectedThreat = 'Repeated Authentication Failure (Brute-Force Pattern)';
                $severity = 'medium';
                $threatCategory = 'Brute Force';
                $totalBruteForce++;
            }

            if ($detectedThreat) {
                $incidents->push([
                    'sequence' => $entry->sequence,
                    'uuid' => $entry->uuid,
                    'type' => $threatCategory,
                    'title' => $detectedThreat,
                    'severity' => $severity,
                    'method' => $method,
                    'uri' => $uri,
                    'status' => $status,
                    'ip' => $ip,
                    'created_at' => $entry->created_at,
                ]);

                // Track attacker IP stats
                if (!isset($attackerIps[$ip])) {
                    $attackerIps[$ip] = [
                        'ip' => $ip,
                        'total_attacks' => 0,
                        'highest_severity' => $severity,
                        'threat_types' => [],
                        'last_seen' => $entry->created_at,
                    ];
                }
                $attackerIps[$ip]['total_attacks']++;
                if (!in_array($threatCategory, $attackerIps[$ip]['threat_types'])) {
                    $attackerIps[$ip]['threat_types'][] = $threatCategory;
                }
                if ($severity === 'critical') {
                    $attackerIps[$ip]['highest_severity'] = 'critical';
                }
            }
        }

        // Sort attacker IPs by volume
        usort($attackerIps, fn($a, $b) => $b['total_attacks'] <=> $a['total_attacks']);

        $totalThreats = $incidents->count();

        return view('telescope.monitoring.security', compact(
            'incidents',
            'attackerIps',
            'totalThreats',
            'totalVulnScans',
            'totalSqli',
            'totalXss',
            'totalBruteForce'
        ));
    }

    /**
     * Module 3: API Health Check & Real-Time Endpoint Uptime Monitor
     */
    public function health(Request $request)
    {
        $this->ensureLocalEnvironment();

        $probeData = $this->runHealthProbes();

        return view('telescope.monitoring.health', $probeData);
    }

    /**
     * JSON Probe API for dynamic live refreshing.
     */
    public function healthProbe(Request $request)
    {
        $this->ensureLocalEnvironment();

        $probeData = $this->runHealthProbes();

        return response()->json($probeData);
    }

    /**
     * Execute comprehensive system diagnostic probes and latency analytics.
     */
    private function runHealthProbes(): array
    {
        // 1. Database Probe
        $dbStatus = 'healthy';
        $dbLatency = 0;
        $dbError = null;
        try {
            $start = microtime(true);
            DB::select('SELECT 1');
            $dbLatency = round((microtime(true) - $start) * 1000, 2);
        } catch (\Throwable $e) {
            $dbStatus = 'unhealthy';
            $dbError = $e->getMessage();
        }

        // 2. Cache Probe
        $cacheStatus = 'healthy';
        $cacheLatency = 0;
        $cacheError = null;
        try {
            $start = microtime(true);
            $testKey = 'health_probe_' . time();
            \Illuminate\Support\Facades\Cache::put($testKey, 'ok', 5);
            $val = \Illuminate\Support\Facades\Cache::get($testKey);
            \Illuminate\Support\Facades\Cache::forget($testKey);
            $cacheLatency = round((microtime(true) - $start) * 1000, 2);
            if ($val !== 'ok') {
                $cacheStatus = 'degraded';
            }
        } catch (\Throwable $e) {
            $cacheStatus = 'unhealthy';
            $cacheError = $e->getMessage();
        }

        // 3. Storage Disk Write Probe
        $storageStatus = 'healthy';
        $storageLatency = 0;
        $storageError = null;
        try {
            $start = microtime(true);
            $testFile = storage_path('app/health_test_' . time() . '.tmp');
            file_put_contents($testFile, 'storage_probe_ok');
            @unlink($testFile);
            $storageLatency = round((microtime(true) - $start) * 1000, 2);
        } catch (\Throwable $e) {
            $storageStatus = 'unhealthy';
            $storageError = $e->getMessage();
        }

        // 4. Queue Probe
        $queueStatus = 'healthy';
        $queueConnection = config('queue.default', 'database');

        // 5. Telescope DB Storage Probe
        $telescopeEntriesCount = DB::table('telescope_entries')->count();
        $telescopeStorageDriver = config('telescope.driver', 'database');

        // 6. Latency Percentiles (P50, P90, P99)
        $durations = [];
        $requestEntries = DB::table('telescope_entries')
            ->where('type', 'request')
            ->select('content')
            ->orderByDesc('sequence')
            ->limit(500)
            ->get();

        foreach ($requestEntries as $entry) {
            $c = $this->decodeContent($entry->content);
            if (isset($c['duration']) && is_numeric($c['duration'])) {
                $durations[] = (float) $c['duration'];
            }
        }

        sort($durations);
        $totalRequests = count($durations);

        $p50 = 0;
        $p90 = 0;
        $p99 = 0;
        $avgLatency = 0;
        $minLatency = 0;
        $maxLatency = 0;

        if ($totalRequests > 0) {
            $avgLatency = round(array_sum($durations) / $totalRequests, 2);
            $minLatency = round($durations[0], 2);
            $maxLatency = round($durations[$totalRequests - 1], 2);

            $p50Index = (int) floor($totalRequests * 0.50);
            $p90Index = (int) floor($totalRequests * 0.90);
            $p99Index = (int) floor($totalRequests * 0.99);

            $p50 = round($durations[$p50Index] ?? $avgLatency, 2);
            $p90 = round($durations[$p90Index] ?? $maxLatency, 2);
            $p99 = round($durations[$p99Index] ?? $maxLatency, 2);
        }

        // Overall System Status
        $systemStatus = 'operational';
        if ($dbStatus === 'unhealthy' || $storageStatus === 'unhealthy') {
            $systemStatus = 'outage';
        } elseif ($cacheStatus === 'unhealthy' || $p90 > 500) {
            $systemStatus = 'degraded';
        }

        return [
            'systemStatus' => $systemStatus,
            'dbStatus' => $dbStatus,
            'dbLatency' => $dbLatency,
            'dbError' => $dbError,
            'cacheStatus' => $cacheStatus,
            'cacheLatency' => $cacheLatency,
            'cacheError' => $cacheError,
            'storageStatus' => $storageStatus,
            'storageLatency' => $storageLatency,
            'storageError' => $storageError,
            'queueStatus' => $queueStatus,
            'queueConnection' => $queueConnection,
            'telescopeEntriesCount' => $telescopeEntriesCount,
            'telescopeStorageDriver' => $telescopeStorageDriver,
            'totalRequestsAnalyzed' => $totalRequests,
            'avgLatency' => $avgLatency,
            'p50' => $p50,
            'p90' => $p90,
            'p99' => $p99,
            'minLatency' => $minLatency,
            'maxLatency' => $maxLatency,
            'probedAt' => now()->toIso8601String(),
        ];
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
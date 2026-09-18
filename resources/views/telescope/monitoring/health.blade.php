<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Health & Uptime Monitor - Telescope APM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background: #f5f7fb; }
        .stat-card { border: 0; border-radius: 15px; transition: 0.2s; }
        .dashboard-card { border: 0; border-radius: 15px; }
        .probe-card { border-radius: 12px; border: 1px solid #e2e8f0; background: #ffffff; padding: 1.25rem; }
        .status-pill { padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="{{ route('monitoring.dashboard') }}">
            <i class="bi bi-activity"></i> Telescope Monitoring Center
        </a>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('monitoring.dashboard') }}" class="btn btn-sm btn-outline-light">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
            <a href="{{ route('monitoring.activity') }}" class="btn btn-sm btn-outline-light">
                <i class="bi bi-list-check"></i> Activity
            </a>
            <a href="{{ route('monitoring.alerts') }}" class="btn btn-sm btn-outline-light">
                <i class="bi bi-bell"></i> Alerts
            </a>
            <a href="{{ route('monitoring.security') }}" class="btn btn-sm btn-outline-light">
                <i class="bi bi-shield-shaded"></i> Security
            </a>
            <a href="{{ route('monitoring.health') }}" class="btn btn-sm btn-success active">
                <i class="bi bi-heart-pulse"></i> Health
            </a>
            <a href="{{ url(config('telescope.path', 'telescope')) }}" target="_blank" class="btn btn-sm btn-primary">
                Open Telescope
            </a>
        </div>
    </div>
</nav>

<div class="container py-4">

    {{-- Breadcrumb & Title --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h3 class="fw-bold mb-1 d-flex align-items-center gap-2">
                <span class="text-success"><i class="bi bi-heart-pulse-fill"></i></span> System Health & Real-Time Uptime Monitor
            </h3>
            <p class="text-muted mb-0 small">
                Live core probes for Database, Cache, Disk Storage, Queue Worker, and Telescope Telemetry with latency distribution analytics.
            </p>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <span class="badge bg-light text-dark border small" id="probedTime">Last Probed: Just Now</span>
            <a href="{{ route('monitoring.health') }}" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-arrow-clockwise"></i> Re-Probe Diagnostics
            </a>
        </div>
    </div>

    {{-- Overall System Status Banner --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4 
        @if($systemStatus === 'operational') bg-success-subtle text-success-emphasis border-success
        @elseif($systemStatus === 'degraded') bg-warning-subtle text-warning-emphasis border-warning
        @else bg-danger-subtle text-danger-emphasis border-danger @endif">
        <div class="card-body p-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="fs-1">
                    @if($systemStatus === 'operational') 🟢
                    @elseif($systemStatus === 'degraded') 🟡
                    @else 🔴 @endif
                </div>
                <div>
                    <h4 class="fw-bold mb-1">
                        @if($systemStatus === 'operational') All Core Systems Operational
                        @elseif($systemStatus === 'degraded') Degraded Performance Detected
                        @else Critical Service Outage @endif
                    </h4>
                    <p class="mb-0 small opacity-75">
                        Database connection is active, disk I/O operational, and cache engine responsive.
                    </p>
                </div>
            </div>
            <div>
                <span class="badge 
                    @if($systemStatus === 'operational') bg-success text-white
                    @elseif($systemStatus === 'degraded') bg-warning text-dark
                    @else bg-danger text-white @endif px-3 py-2 fs-6 rounded-pill">
                    {{ strtoupper($systemStatus) }}
                </span>
            </div>
        </div>
    </div>

    {{-- Core Diagnostic Probes Grid --}}
    <h5 class="fw-bold mb-3 d-flex align-items-center gap-2">
        <i class="bi bi-cpu text-primary"></i> Core Service Diagnostic Probes
    </h5>

    <div class="row g-3 mb-4">
        
        {{-- Database Probe --}}
        <div class="col-md-3 col-sm-6">
            <div class="probe-card shadow-sm h-100">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="fw-bold text-dark d-flex align-items-center gap-2">
                        <i class="bi bi-database text-primary"></i> Database (MySQL)
                    </div>
                    @if($dbStatus === 'healthy')
                        <span class="badge bg-success-subtle text-success">Healthy</span>
                    @else
                        <span class="badge bg-danger">Down</span>
                    @endif
                </div>
                <div class="h3 fw-bold mb-1 text-dark">{{ $dbLatency }} <small class="fs-6 text-muted">ms</small></div>
                <small class="text-muted d-block">Query Ping Latency</small>
            </div>
        </div>

        {{-- Cache Engine Probe --}}
        <div class="col-md-3 col-sm-6">
            <div class="probe-card shadow-sm h-100">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="fw-bold text-dark d-flex align-items-center gap-2">
                        <i class="bi bi-lightning-charge text-warning"></i> Cache Engine
                    </div>
                    @if($cacheStatus === 'healthy')
                        <span class="badge bg-success-subtle text-success">Healthy</span>
                    @else
                        <span class="badge bg-danger">Issue</span>
                    @endif
                </div>
                <div class="h3 fw-bold mb-1 text-dark">{{ $cacheLatency }} <small class="fs-6 text-muted">ms</small></div>
                <small class="text-muted d-block">Read / Write Roundtrip</small>
            </div>
        </div>

        {{-- Storage Disk Probe --}}
        <div class="col-md-3 col-sm-6">
            <div class="probe-card shadow-sm h-100">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="fw-bold text-dark d-flex align-items-center gap-2">
                        <i class="bi bi-hdd-network text-info"></i> Disk Storage
                    </div>
                    @if($storageStatus === 'healthy')
                        <span class="badge bg-success-subtle text-success">Healthy</span>
                    @else
                        <span class="badge bg-danger">Write Failed</span>
                    @endif
                </div>
                <div class="h3 fw-bold mb-1 text-dark">{{ $storageLatency }} <small class="fs-6 text-muted">ms</small></div>
                <small class="text-muted d-block">Local Disk I/O Write</small>
            </div>
        </div>

        {{-- Telescope Watcher Engine Probe --}}
        <div class="col-md-3 col-sm-6">
            <div class="probe-card shadow-sm h-100">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="fw-bold text-dark d-flex align-items-center gap-2">
                        <i class="bi bi-activity text-purple"></i> Telescope Entries
                    </div>
                    <span class="badge bg-success-subtle text-success">Active</span>
                </div>
                <div class="h3 fw-bold mb-1 text-dark">{{ number_format($telescopeEntriesCount) }}</div>
                <small class="text-muted d-block">Total Stored Records</small>
            </div>
        </div>

    </div>

    {{-- Request Latency Distribution (P50, P90, P99) --}}
    <div class="card dashboard-card shadow-sm mb-4">
        <div class="card-header bg-white py-3 border-0">
            <h5 class="card-title fw-bold mb-0 d-flex align-items-center gap-2">
                <i class="bi bi-bar-chart-line text-primary"></i> Endpoint Latency Percentile Distribution (APM)
            </h5>
            <small class="text-muted">Calculated across the latest {{ $totalRequestsAnalyzed }} HTTP requests recorded in Telescope</small>
        </div>
        <div class="card-body">
            <div class="row g-4 text-center">
                
                {{-- Average --}}
                <div class="col-md-3 col-6">
                    <div class="p-3 bg-light rounded-3 border">
                        <div class="text-muted small fw-semibold">Average Latency</div>
                        <div class="h2 fw-bold text-primary my-1">{{ $avgLatency }} <small class="fs-6">ms</small></div>
                        <small class="text-muted">Mean Response Time</small>
                    </div>
                </div>

                {{-- P50 (Median) --}}
                <div class="col-md-3 col-6">
                    <div class="p-3 bg-light rounded-3 border">
                        <div class="text-muted small fw-semibold">P50 (Median)</div>
                        <div class="h2 fw-bold text-success my-1">{{ $p50 }} <small class="fs-6">ms</small></div>
                        <small class="text-muted">50% Requests Faster</small>
                    </div>
                </div>

                {{-- P90 --}}
                <div class="col-md-3 col-6">
                    <div class="p-3 bg-light rounded-3 border">
                        <div class="text-muted small fw-semibold">P90 Percentile</div>
                        <div class="h2 fw-bold text-warning my-1">{{ $p90 }} <small class="fs-6">ms</small></div>
                        <small class="text-muted">90% Requests Faster</small>
                    </div>
                </div>

                {{-- P99 (Worst Case) --}}
                <div class="col-md-3 col-6">
                    <div class="p-3 bg-light rounded-3 border">
                        <div class="text-muted small fw-semibold">P99 (Worst 1%)</div>
                        <div class="h2 fw-bold text-danger my-1">{{ $p99 }} <small class="fs-6">ms</small></div>
                        <small class="text-muted">Tail Latency Cap</small>
                    </div>
                </div>

            </div>

            <div class="mt-4 p-3 bg-light rounded-3 border">
                <div class="d-flex justify-content-between small text-muted mb-1">
                    <span>Min Latency: <strong>{{ $minLatency }} ms</strong></span>
                    <span>Max Observed Latency: <strong>{{ $maxLatency }} ms</strong></span>
                </div>
                <div class="progress" style="height: 12px;">
                    <div class="progress-bar bg-success" style="width: 50%" title="P50"></div>
                    <div class="progress-bar bg-warning" style="width: 40%" title="P90"></div>
                    <div class="progress-bar bg-danger" style="width: 10%" title="P99"></div>
                </div>
            </div>
        </div>
    </div>

</div>

</body>
</html>

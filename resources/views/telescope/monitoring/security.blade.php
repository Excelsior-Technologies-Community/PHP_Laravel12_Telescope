<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security & Threat Inspector - Telescope APM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background: #f5f7fb; }
        .stat-card { border: 0; border-radius: 15px; transition: 0.2s; }
        .stat-card:hover { transform: translateY(-3px); }
        .stat-icon { width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 22px; }
        .dashboard-card { border: 0; border-radius: 15px; }
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
            <a href="{{ route('monitoring.security') }}" class="btn btn-sm btn-danger active">
                <i class="bi bi-shield-shaded"></i> Security
            </a>
            <a href="{{ route('monitoring.health') }}" class="btn btn-sm btn-outline-light">
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
                <span class="text-danger"><i class="bi bi-shield-lock-fill"></i></span> Security Threat & Malicious Request Inspector
            </h3>
            <p class="text-muted mb-0 small">
                Automated detection of vulnerability scans, SQL injection, XSS payloads, and authentication brute-force attacks from Telescope traffic.
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('monitoring.security') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-clockwise"></i> Refresh Security Audit
            </a>
        </div>
    </div>

    {{-- Security KPI Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card stat-card shadow-sm border-start border-danger border-4">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small">Total Threats Detected</div>
                        <h3 class="fw-bold mb-0 text-danger">{{ $totalThreats }}</h3>
                    </div>
                    <div class="stat-icon bg-danger-subtle text-danger">
                        <i class="bi bi-shield-exclamation"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card stat-card shadow-sm border-start border-warning border-4">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small">Vulnerability Scans</div>
                        <h3 class="fw-bold mb-0 text-warning">{{ $totalVulnScans }}</h3>
                        <small class="text-muted">.env, wp-login, git</small>
                    </div>
                    <div class="stat-icon bg-warning-subtle text-warning">
                        <i class="bi bi-search"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card stat-card shadow-sm border-start border-danger border-4">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small">SQLi & XSS Exploits</div>
                        <h3 class="fw-bold mb-0 text-danger">{{ $totalSqli + $totalXss }}</h3>
                        <small class="text-muted">SQLi: {{ $totalSqli }} | XSS: {{ $totalXss }}</small>
                    </div>
                    <div class="stat-icon bg-danger-subtle text-danger">
                        <i class="bi bi-code-slash"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card stat-card shadow-sm border-start border-info border-4">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small">Brute-Force Failures</div>
                        <h3 class="fw-bold mb-0 text-info">{{ $totalBruteForce }}</h3>
                        <small class="text-muted">401/403/429 status</small>
                    </div>
                    <div class="stat-icon bg-info-subtle text-info">
                        <i class="bi bi-key-fill"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        
        {{-- Top Threat Sources / Attacker IPs --}}
        <div class="col-lg-4">
            <div class="card dashboard-card shadow-sm h-100">
                <div class="card-header bg-white py-3 border-0">
                    <h5 class="card-title fw-bold mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-crosshair text-danger"></i> Top Suspect IPs
                    </h5>
                </div>
                <div class="card-body p-0">
                    @if(empty($attackerIps))
                        <div class="p-4 text-center text-muted">
                            <i class="bi bi-shield-check text-success fs-1 d-block mb-2"></i>
                            No malicious IP activity detected in current Telescope log window.
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light small">
                                    <tr>
                                        <th>IP Address</th>
                                        <th>Threats</th>
                                        <th>Severity</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach(array_slice($attackerIps, 0, 8) as $att)
                                        <tr>
                                            <td>
                                                <div class="fw-bold text-dark font-monospace small">{{ $att['ip'] }}</div>
                                                <small class="text-muted" style="font-size: 11px;">
                                                    {{ implode(', ', $att['threat_types']) }}
                                                </small>
                                            </td>
                                            <td>
                                                <span class="badge bg-danger rounded-pill">{{ $att['total_attacks'] }}</span>
                                            </td>
                                            <td>
                                                @if($att['highest_severity'] === 'critical')
                                                    <span class="badge bg-danger">Critical</span>
                                                @elseif($att['highest_severity'] === 'high')
                                                    <span class="badge bg-warning text-dark">High</span>
                                                @else
                                                    <span class="badge bg-secondary">Medium</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Security Incident Log Table --}}
        <div class="col-lg-8">
            <div class="card dashboard-card shadow-sm h-100">
                <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                    <h5 class="card-title fw-bold mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-journal-text text-primary"></i> Real-Time Security Incident Stream
                    </h5>
                    <span class="badge bg-secondary">{{ $incidents->count() }} Events</span>
                </div>
                <div class="card-body p-0">
                    @if($incidents->isEmpty())
                        <div class="p-5 text-center text-muted">
                            <i class="bi bi-check-circle-fill text-success fs-1 d-block mb-2"></i>
                            <h6 class="fw-bold text-dark">Zero Security Incidents Detected</h6>
                            <p class="small mb-0">All recent incoming HTTP requests conform to safe patterns.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light small">
                                    <tr>
                                        <th>Severity</th>
                                        <th>Threat Type</th>
                                        <th>Target Endpoint / Method</th>
                                        <th>Status</th>
                                        <th>Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($incidents->take(15) as $incident)
                                        <tr>
                                            <td>
                                                @if($incident['severity'] === 'critical')
                                                    <span class="badge bg-danger">CRITICAL</span>
                                                @elseif($incident['severity'] === 'high')
                                                    <span class="badge bg-warning text-dark">HIGH</span>
                                                @else
                                                    <span class="badge bg-secondary">MEDIUM</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="fw-semibold text-dark small">{{ $incident['title'] }}</div>
                                                <small class="text-muted font-monospace" style="font-size: 11px;">IP: {{ $incident['ip'] }}</small>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark border me-1">{{ $incident['method'] }}</span>
                                                <span class="font-monospace text-truncate d-inline-block small" style="max-width: 260px;" title="{{ $incident['uri'] }}">
                                                    {{ $incident['uri'] }}
                                                </span>
                                            </td>
                                            <td>
                                                @if($incident['status'] >= 500)
                                                    <span class="badge bg-danger">{{ $incident['status'] }}</span>
                                                @elseif($incident['status'] >= 400)
                                                    <span class="badge bg-warning text-dark">{{ $incident['status'] }}</span>
                                                @else
                                                    <span class="badge bg-success">{{ $incident['status'] }}</span>
                                                @endif
                                            </td>
                                            <td class="text-muted small text-nowrap">
                                                {{ \Carbon\Carbon::parse($incident['created_at'])->diffForHumans() }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

    </div>

</div>

</body>
</html>

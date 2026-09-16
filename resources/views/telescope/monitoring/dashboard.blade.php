<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Telescope Monitoring Dashboard</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        rel="stylesheet"
    >

    <style>

        body {
            background: #f5f7fb;
        }

        .navbar-brand {
            font-weight: 700;
        }

        .stat-card {
            border: 0;
            border-radius: 15px;
            transition: 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-3px);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
        }

        .dashboard-card {
            border: 0;
            border-radius: 15px;
        }

        .table {
            vertical-align: middle;
        }

        .badge {
            font-weight: 500;
        }

        .chart-bar {
            height: 10px;
            border-radius: 20px;
            background: #0d6efd;
        }

        .sticky-tools {
            position: sticky;
            top: 10px;
        }

    </style>

</head>

<body>

<nav class="navbar navbar-dark bg-dark shadow-sm">

    <div class="container">

        <a
            class="navbar-brand"
            href="{{ route('monitoring.dashboard') }}"
        >

            <i class="bi bi-activity"></i>

            Telescope Monitoring Center

        </a>

        <div class="d-flex gap-2">

            <a
                href="{{ route('monitoring.dashboard') }}"
                class="btn btn-light btn-sm"
            >
                <i class="bi bi-speedometer2"></i>
                Dashboard
            </a>

            <a
                href="{{ route('monitoring.activity') }}"
                class="btn btn-outline-light btn-sm"
            >
                <i class="bi bi-search"></i>
                Activity
            </a>

            <a
                href="{{ route('monitoring.alerts') }}"
                class="btn btn-outline-warning btn-sm"
            >
                <i class="bi bi-exclamation-triangle"></i>
                Alerts
            </a>

            <a
                href="/telescope"
                target="_blank"
                class="btn btn-outline-info btn-sm"
            >
                <i class="bi bi-binoculars"></i>
                Telescope
            </a>

        </div>

    </div>

</nav>

<div class="container py-4">

    @if(session('success'))

        <div class="alert alert-success alert-dismissible fade show">

            <i class="bi bi-check-circle"></i>

            {{ session('success') }}

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
            ></button>

        </div>

    @endif

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="fw-bold mb-1">
                Application Monitoring
            </h2>

            <p class="text-muted mb-0">
                Monitor Laravel application activity using Telescope.
            </p>

        </div>

        <!-- Feature 8: Auto refresh -->

        <div class="d-flex align-items-center gap-2">

            <div class="form-check form-switch">

                <input
                    class="form-check-input"
                    type="checkbox"
                    id="autoRefresh"
                >

                <label
                    class="form-check-label"
                    for="autoRefresh"
                >
                    Auto Refresh
                </label>

            </div>

            <span
                id="refreshStatus"
                class="badge text-bg-secondary"
            >
                OFF
            </span>

        </div>

    </div>

    <!-- Statistics -->

    <div class="row g-4 mb-4">

        <div class="col-md-6 col-lg-3">

            <div class="card stat-card shadow-sm h-100">

                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-center">

                        <div>

                            <small class="text-muted">
                                Total Entries
                            </small>

                            <h3 class="fw-bold mb-0">
                                {{ number_format($totalEntries) }}
                            </h3>

                        </div>

                        <div class="stat-icon bg-primary-subtle text-primary">

                            <i class="bi bi-database"></i>

                        </div>

                    </div>

                </div>

            </div>

        </div>

        <div class="col-md-6 col-lg-3">

            <div class="card stat-card shadow-sm h-100">

                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-center">

                        <div>

                            <small class="text-muted">
                                Requests
                            </small>

                            <h3 class="fw-bold mb-0">
                                {{ number_format($requests) }}
                            </h3>

                        </div>

                        <div class="stat-icon bg-success-subtle text-success">

                            <i class="bi bi-globe"></i>

                        </div>

                    </div>

                </div>

            </div>

        </div>

        <div class="col-md-6 col-lg-3">

            <div class="card stat-card shadow-sm h-100">

                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-center">

                        <div>

                            <small class="text-muted">
                                Exceptions
                            </small>

                            <h3 class="fw-bold mb-0">
                                {{ number_format($exceptions) }}
                            </h3>

                        </div>

                        <div class="stat-icon bg-danger-subtle text-danger">

                            <i class="bi bi-bug"></i>

                        </div>

                    </div>

                </div>

            </div>

        </div>

        <div class="col-md-6 col-lg-3">

            <div class="card stat-card shadow-sm h-100">

                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-center">

                        <div>

                            <small class="text-muted">
                                Database Queries
                            </small>

                            <h3 class="fw-bold mb-0">
                                {{ number_format($queries) }}
                            </h3>

                        </div>

                        <div class="stat-icon bg-info-subtle text-info">

                            <i class="bi bi-server"></i>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

    <!-- Performance -->

    <div class="row g-4 mb-4">

        <div class="col-md-4">

            <div class="card dashboard-card shadow-sm h-100">

                <div class="card-body">

                    <div class="d-flex align-items-center gap-3">

                        <div class="stat-icon bg-warning-subtle text-warning">

                            <i class="bi bi-speedometer"></i>

                        </div>

                        <div>

                            <div class="text-muted">
                                Slow Queries
                            </div>

                            <h4 class="fw-bold mb-0">
                                {{ number_format($slowQueries) }}
                            </h4>

                        </div>

                    </div>

                </div>

            </div>

        </div>

        <div class="col-md-4">

            <div class="card dashboard-card shadow-sm h-100">

                <div class="card-body">

                    <div class="d-flex align-items-center gap-3">

                        <div class="stat-icon bg-danger-subtle text-danger">

                            <i class="bi bi-x-circle"></i>

                        </div>

                        <div>

                            <div class="text-muted">
                                Failed Requests
                            </div>

                            <h4 class="fw-bold mb-0">
                                {{ number_format($failedRequests) }}
                            </h4>

                        </div>

                    </div>

                </div>

            </div>

        </div>

        <div class="col-md-4">

            <div class="card dashboard-card shadow-sm h-100">

                <div class="card-body">

                    <div class="d-flex align-items-center gap-3">

                        <div class="stat-icon bg-secondary-subtle text-secondary">

                            <i class="bi bi-cpu"></i>

                        </div>

                        <div>

                            <div class="text-muted">
                                Queue Jobs
                            </div>

                            <h4 class="fw-bold mb-0">
                                {{ number_format($jobs) }}
                            </h4>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

    <!-- Feature 1: Date presets -->

    <div class="card dashboard-card shadow-sm mb-4">

        <div class="card-body">

            <div class="d-flex justify-content-between align-items-center">

                <div>

                    <h5 class="fw-bold mb-1">
                        Quick Activity Filters
                    </h5>

                    <small class="text-muted">
                        View Telescope activity for a selected period.
                    </small>

                </div>

                <div class="d-flex gap-2">

                    <a
                        href="{{ route('monitoring.activity', ['preset' => 'today']) }}"
                        class="btn btn-outline-primary btn-sm"
                    >
                        Today
                    </a>

                    <a
                        href="{{ route('monitoring.activity', ['preset' => '7days']) }}"
                        class="btn btn-outline-primary btn-sm"
                    >
                        Last 7 Days
                    </a>

                    <a
                        href="{{ route('monitoring.activity', ['preset' => '30days']) }}"
                        class="btn btn-outline-primary btn-sm"
                    >
                        Last 30 Days
                    </a>

                </div>

            </div>

        </div>

    </div>

    <!-- Feature 9: Daily trend -->

    <div class="card dashboard-card shadow-sm mb-4">

        <div class="card-header bg-white border-0 pt-4 px-4">

            <h5 class="fw-bold mb-0">
                7-Day Activity Trend
            </h5>

        </div>

        <div class="card-body">

            @php
                $maxDailyActivity = max(
                    1,
                    $dailyActivity->max('total') ?? 1
                );
            @endphp

            @forelse($dailyActivity as $day)

                <div class="mb-3">

                    <div class="d-flex justify-content-between mb-1">

                        <small>
                            {{ $day->activity_date }}
                        </small>

                        <strong>
                            {{ number_format($day->total) }}
                        </strong>

                    </div>

                    <div class="progress">

                        <div
                            class="progress-bar"
                            style="width: {{ ($day->total / $maxDailyActivity) * 100 }}%"
                        ></div>

                    </div>

                </div>

            @empty

                <p class="text-muted mb-0">
                    No activity available for the last 7 days.
                </p>

            @endforelse

        </div>

    </div>

    <div class="row g-4 mb-4">

        <!-- Feature 4: Top routes -->

        <div class="col-lg-6">

            <div class="card dashboard-card shadow-sm h-100">

                <div class="card-header bg-white border-0 pt-4 px-4">

                    <h5 class="fw-bold mb-0">
                        Top Requested URLs
                    </h5>

                </div>

                <div class="card-body">

                    @forelse($topRoutes as $route)

                        <div class="mb-3">

                            <div class="d-flex justify-content-between">

                                <span
                                    class="text-truncate"
                                    style="max-width: 80%;"
                                >
                                    {{ $route['uri'] }}
                                </span>

                                <strong>
                                    {{ number_format($route['total']) }}
                                </strong>

                            </div>

                            <div class="progress mt-1">

                                <div
                                    class="progress-bar"
                                    style="width: {{ min(100, ($route['total'] / max(1, $topRoutes->max('total'))) * 100) }}%"
                                ></div>

                            </div>

                        </div>

                    @empty

                        <p class="text-muted">
                            No request activity found.
                        </p>

                    @endforelse

                </div>

            </div>

        </div>

        <!-- Feature 5: Slowest queries -->

        <div class="col-lg-6">

            <div class="card dashboard-card shadow-sm h-100">

                <div class="card-header bg-white border-0 pt-4 px-4">

                    <h5 class="fw-bold mb-0">
                        Slowest Queries
                    </h5>

                </div>

                <div class="card-body">

                    @forelse($slowestQueries as $query)

                        <div class="border-bottom pb-3 mb-3">

                            <div class="d-flex justify-content-between">

                                <span class="badge text-bg-danger">

                                    {{ number_format($query['duration'], 2) }}
                                    ms

                                </span>

                                <small class="text-muted">
                                    {{ $query['created_at'] }}
                                </small>

                            </div>

                            <div
                                class="small text-truncate mt-2"
                                title="{{ $query['sql'] }}"
                            >
                                {{ $query['sql'] }}
                            </div>

                        </div>

                    @empty

                        <p class="text-muted">
                            No slow queries found.
                        </p>

                    @endforelse

                </div>

            </div>

        </div>

    </div>

    <!-- Activity -->

    <div class="row g-4 mb-4">

        <div class="col-lg-8">

            <div class="card dashboard-card shadow-sm">

                <div class="card-header bg-white border-0 pt-4 px-4">

                    <div class="d-flex justify-content-between">

                        <h5 class="fw-bold mb-0">
                            Recent Telescope Activity
                        </h5>

                        <a
                            href="{{ route('monitoring.activity') }}"
                            class="btn btn-sm btn-primary"
                        >
                            View All
                        </a>

                    </div>

                </div>

                <div class="card-body">

                    <div class="table-responsive">

                        <table class="table">

                            <thead>

                            <tr>

                                <th>Type</th>

                                <th>Description</th>

                                <th>Status</th>

                                <th>Time</th>

                            </tr>

                            </thead>

                            <tbody>

                            @forelse($recentEntries as $entry)

                                <tr>

                                    <td>

                                        <span class="badge text-bg-dark">

                                            {{ ucfirst($entry['type']) }}

                                        </span>

                                    </td>

                                    <td>

                                        <span
                                            class="d-inline-block text-truncate"
                                            style="max-width: 300px;"
                                        >
                                            {{ $entry['description'] }}
                                        </span>

                                    </td>

                                    <td>

                                        @if(
                                            $entry['type'] === 'request'
                                            && is_numeric($entry['status'])
                                            && $entry['status'] >= 400
                                        )

                                            <span class="badge text-bg-danger">
                                                {{ $entry['status'] }}
                                            </span>

                                        @elseif(
                                            $entry['type'] === 'exception'
                                        )

                                            <span class="badge text-bg-danger">
                                                Exception
                                            </span>

                                        @else

                                            <span class="badge text-bg-success">
                                                {{ $entry['status'] }}
                                            </span>

                                        @endif

                                    </td>

                                    <td>

                                        <small class="text-muted">
                                            {{ $entry['created_at'] }}
                                        </small>

                                    </td>

                                </tr>

                            @empty

                                <tr>

                                    <td
                                        colspan="4"
                                        class="text-center py-4"
                                    >
                                        No Telescope activity found.
                                    </td>

                                </tr>

                            @endforelse

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>

        </div>

        <!-- Activity distribution -->

        <div class="col-lg-4">

            <div class="card dashboard-card shadow-sm">

                <div class="card-header bg-white border-0 pt-4 px-4">

                    <h5 class="fw-bold mb-0">
                        Activity Distribution
                    </h5>

                </div>

                <div class="card-body">

                    @forelse($activityCounts as $activity)

                        <div class="d-flex justify-content-between align-items-center mb-3">

                            <div>

                                <span class="badge text-bg-secondary">

                                    {{ ucfirst($activity->type) }}

                                </span>

                            </div>

                            <strong>
                                {{ number_format($activity->total) }}
                            </strong>

                        </div>

                    @empty

                        <p class="text-muted">
                            No activity available.
                        </p>

                    @endforelse

                </div>

            </div>

        </div>

    </div>

    <!-- Feature 7: Cleanup -->

    <div class="card dashboard-card shadow-sm mb-4">

        <div class="card-body">

            <div class="row align-items-center">

                <div class="col-lg-7">

                    <h5 class="fw-bold">
                        Telescope Data Cleanup
                    </h5>

                    <p class="text-muted mb-lg-0">

                        Delete old Telescope records to keep the
                        monitoring database smaller.

                    </p>

                    @if($oldestEntry)

                        <small class="text-muted">

                            Oldest entry:
                            {{ $oldestEntry }}

                            @if($newestEntry)
                                | Latest:
                                {{ $newestEntry }}
                            @endif

                        </small>

                    @endif

                </div>

                <div class="col-lg-5">

                    <form
                        method="POST"
                        action="{{ route('monitoring.cleanup') }}"
                        class="d-flex gap-2 justify-content-lg-end"
                        onsubmit="return confirm('Delete old Telescope entries? This cannot be undone.')"
                    >

                        @csrf

                        @method('DELETE')

                        <select
                            name="days"
                            class="form-select"
                            style="max-width: 150px;"
                        >

                            <option value="7">
                                Older than 7 days
                            </option>

                            <option value="15">
                                Older than 15 days
                            </option>

                            <option value="30">
                                Older than 30 days
                            </option>

                            <option value="60">
                                Older than 60 days
                            </option>

                            <option value="90">
                                Older than 90 days
                            </option>

                        </select>

                        <button
                            type="submit"
                            class="btn btn-danger"
                        >

                            <i class="bi bi-trash"></i>

                            Cleanup

                        </button>

                    </form>

                </div>

            </div>

        </div>

    </div>

</div>

<script>

    /*
     * Feature 8:
     * Auto refresh every 30 seconds.
     */

    const autoRefresh =
        document.getElementById('autoRefresh');

    const refreshStatus =
        document.getElementById('refreshStatus');

    let refreshTimer = null;

    autoRefresh.addEventListener('change', function () {

        if (this.checked) {

            refreshStatus.textContent = 'ON';

            refreshStatus.className =
                'badge text-bg-success';

            refreshTimer = setInterval(
                function () {
                    window.location.reload();
                },
                30000
            );

        } else {

            refreshStatus.textContent = 'OFF';

            refreshStatus.className =
                'badge text-bg-secondary';

            clearInterval(refreshTimer);

        }

    });

</script>

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>

</body>

</html>
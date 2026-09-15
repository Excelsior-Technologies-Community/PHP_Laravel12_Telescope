<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

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
    </style>
</head>

<body>

<nav class="navbar navbar-dark bg-dark shadow-sm">
    <div class="container">

        <a class="navbar-brand" href="{{ route('monitoring.dashboard') }}">
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
                class="btn btn-outline-light btn-sm"
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

    <div class="mb-4">
        <h2 class="fw-bold mb-1">
            Application Monitoring
        </h2>

        <p class="text-muted mb-0">
            Monitor Laravel application activity using Telescope data.
        </p>
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

    <!-- Activity -->

    <div class="row g-4">

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

                                        @elseif($entry['type'] === 'exception')

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
                                    <td colspan="4" class="text-center py-4">
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

        <!-- Activity Distribution -->

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

</div>

</body>
</html>


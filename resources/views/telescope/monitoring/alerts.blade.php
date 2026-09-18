<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Telescope Alerts</title>

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

        .main-card {
            border: 0;
            border-radius: 15px;
        }

        .alert-item {
            border-left: 5px solid;
            border-radius: 10px;
        }

        .alert-warning-custom {
            border-left-color: #ffc107;
            background: #fffaf0;
        }

        .alert-critical-custom {
            border-left-color: #dc3545;
            background: #fff5f5;
        }

        .alert-icon {
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            font-size: 20px;
        }
    </style>

</head>

<body>

<nav class="navbar navbar-dark bg-dark">

    <div class="container">

        <a
            href="{{ route('monitoring.dashboard') }}"
            class="navbar-brand fw-bold"
        >
            <i class="bi bi-activity"></i>
            Telescope Monitoring
        </a>

        <div class="d-flex flex-wrap gap-2">

            <a
                href="{{ route('monitoring.dashboard') }}"
                class="btn btn-outline-light btn-sm"
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
                class="btn btn-warning btn-sm"
            >
                <i class="bi bi-exclamation-triangle"></i>
                Alerts
            </a>

            <a
                href="{{ route('monitoring.security') }}"
                class="btn btn-outline-danger btn-sm"
            >
                <i class="bi bi-shield-shaded"></i>
                Security
            </a>

            <a
                href="{{ route('monitoring.health') }}"
                class="btn btn-outline-success btn-sm"
            >
                <i class="bi bi-heart-pulse"></i>
                Health
            </a>

            <a
                href="{{ url(config('telescope.path', 'telescope')) }}"
                target="_blank"
                class="btn btn-primary btn-sm"
            >
                <i class="bi bi-binoculars"></i>
                Telescope
            </a>

        </div>

    </div>

</nav>

<div class="container py-4">

    <div class="mb-4">

        <h2 class="fw-bold">
            Error & Performance Alerts
        </h2>

        <p class="text-muted">
            Important errors, failed requests, failed jobs and slow
            database queries detected from Telescope.
        </p>

    </div>

    <div class="card main-card shadow-sm">

        <div class="card-body">

            <div class="d-flex justify-content-between align-items-center mb-4">

                <h5 class="fw-bold mb-0">
                    Monitoring Alerts
                </h5>

                <span class="badge text-bg-danger">
                    {{ $alerts->count() }} Alerts
                </span>

            </div>

            @forelse($alerts as $alert)

                <div
                    class="alert-item p-3 mb-3
                    {{ $alert['severity'] === 'critical'
                        ? 'alert-critical-custom'
                        : 'alert-warning-custom' }}"
                >

                    <div class="d-flex gap-3">

                        <div>

                            <div
                                class="alert-icon
                                {{ $alert['severity'] === 'critical'
                                    ? 'bg-danger-subtle text-danger'
                                    : 'bg-warning-subtle text-warning' }}"
                            >

                                <i class="bi {{ $alert['icon'] }}"></i>

                            </div>

                        </div>

                        <div class="flex-grow-1">

                            <div class="d-flex justify-content-between">

                                <div>

                                    @if($alert['severity'] === 'critical')

                                        <span class="badge text-bg-danger">
                                            CRITICAL
                                        </span>

                                    @else

                                        <span class="badge text-bg-warning">
                                            WARNING
                                        </span>

                                    @endif

                                    <span class="badge text-bg-secondary ms-1">
                                        {{ $alert['type'] }}
                                    </span>

                                </div>

                                <small class="text-muted">
                                    {{ $alert['created_at'] }}
                                </small>

                            </div>

                            <h6 class="fw-bold mt-2 mb-1">
                                {{ $alert['title'] }}
                            </h6>

                            <p class="text-muted mb-0">

                                {{ \Illuminate\Support\Str::limit(
                                    $alert['description'],
                                    250
                                ) }}

                            </p>

                        </div>

                    </div>

                </div>

            @empty

                <div class="text-center py-5">

                    <div class="mb-3">

                        <i
                            class="bi bi-check-circle text-success"
                            style="font-size: 60px;"
                        ></i>

                    </div>

                    <h4 class="fw-bold">
                        No Alerts Found
                    </h4>

                    <p class="text-muted">
                        Your application currently has no detected
                        errors or performance alerts.
                    </p>

                </div>

            @endforelse

        </div>

    </div>

</div>

</body>

</html>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Telescope Activity Search</title>

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

        pre {
            max-height: 250px;
            overflow: auto;
            font-size: 12px;
        }

        .table {
            vertical-align: middle;
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
                class="btn btn-light btn-sm"
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
            Advanced Activity Search
        </h2>

        <p class="text-muted">
            Search and filter Laravel Telescope activity.
        </p>

    </div>

    <!-- Filters -->

    <div class="card main-card shadow-sm mb-4">

        <div class="card-body">

            <form
                method="GET"
                action="{{ route('monitoring.activity') }}"
                id="activityFilterForm"
            >

                <div class="row g-3">

                    <!-- Feature 1: Date preset -->

                    <div class="col-md-3">

                        <label class="form-label fw-semibold">
                            Date Preset
                        </label>

                        <select
                            name="preset"
                            class="form-select"
                        >

                            <option value="">
                                Custom / All Dates
                            </option>

                            <option
                                value="today"
                                @selected(request('preset') === 'today')
                            >
                                Today
                            </option>

                            <option
                                value="7days"
                                @selected(request('preset') === '7days')
                            >
                                Last 7 Days
                            </option>

                            <option
                                value="30days"
                                @selected(request('preset') === '30days')
                            >
                                Last 30 Days
                            </option>

                        </select>

                    </div>

                    <!-- Type -->

                    <div class="col-md-3">

                        <label class="form-label fw-semibold">
                            Activity Type
                        </label>

                        <select
                            name="type"
                            class="form-select"
                        >

                            <option value="">
                                All Activity
                            </option>

                            @foreach([
                                'request',
                                'query',
                                'exception',
                                'job',
                                'event',
                                'cache',
                                'command',
                                'mail',
                                'notification',
                                'model',
                                'log',
                                'view',
                                'schedule',
                                'redis'
                            ] as $type)

                                <option
                                    value="{{ $type }}"
                                    @selected(request('type') === $type)
                                >

                                    {{ ucfirst($type) }}

                                </option>

                            @endforeach

                        </select>

                    </div>

                    <!-- Feature 3: Method -->

                    <div class="col-md-2">

                        <label class="form-label fw-semibold">
                            HTTP Method
                        </label>

                        <select
                            name="method"
                            class="form-select"
                        >

                            <option value="">
                                All
                            </option>

                            @foreach([
                                'GET',
                                'POST',
                                'PUT',
                                'PATCH',
                                'DELETE'
                            ] as $method)

                                <option
                                    value="{{ $method }}"
                                    @selected(request('method') === $method)
                                >
                                    {{ $method }}
                                </option>

                            @endforeach

                        </select>

                    </div>

                    <!-- Feature 2: Status -->

                    <div class="col-md-2">

                        <label class="form-label fw-semibold">
                            HTTP Status
                        </label>

                        <select
                            name="status"
                            class="form-select"
                        >

                            <option value="">
                                All
                            </option>

                            @foreach([
                                200,
                                201,
                                204,
                                301,
                                302,
                                400,
                                401,
                                403,
                                404,
                                419,
                                422,
                                429,
                                500,
                                502,
                                503
                            ] as $status)

                                <option
                                    value="{{ $status }}"
                                    @selected(
                                        (string) request('status')
                                        === (string) $status
                                    )
                                >
                                    {{ $status }}
                                </option>

                            @endforeach

                        </select>

                    </div>

                    <!-- Search -->

                    <div class="col-md-2">

                        <label class="form-label fw-semibold">
                            Search
                        </label>

                        <input
                            type="text"
                            name="search"
                            value="{{ request('search') }}"
                            class="form-control"
                            placeholder="URI / SQL..."
                        >

                    </div>

                    <!-- From -->

                    <div class="col-md-3">

                        <label class="form-label fw-semibold">
                            From
                        </label>

                        <input
                            type="date"
                            name="date_from"
                            value="{{ request('date_from') }}"
                            class="form-control"
                        >

                    </div>

                    <!-- To -->

                    <div class="col-md-3">

                        <label class="form-label fw-semibold">
                            To
                        </label>

                        <input
                            type="date"
                            name="date_to"
                            value="{{ request('date_to') }}"
                            class="form-control"
                        >

                    </div>

                    <!-- Buttons -->

                    <div class="col-md-6 d-flex align-items-end">

                        <div class="d-flex gap-2 w-100">

                            <button
                                type="submit"
                                class="btn btn-primary"
                            >

                                <i class="bi bi-search"></i>

                                Search

                            </button>

                            <a
                                href="{{ route('monitoring.activity') }}"
                                class="btn btn-outline-secondary"
                            >

                                <i class="bi bi-arrow-clockwise"></i>

                                Reset

                            </a>

                            <!-- Feature 6: CSV -->

                            <button
                                type="button"
                                class="btn btn-success"
                                onclick="exportActivity()"
                            >

                                <i class="bi bi-filetype-csv"></i>

                                Export CSV

                            </button>

                        </div>

                    </div>

                </div>

            </form>

        </div>

    </div>

    <!-- Results -->

    <div class="card main-card shadow-sm">

        <div class="card-body">

            <div class="d-flex justify-content-between align-items-center mb-3">

                <h5 class="fw-bold mb-0">
                    Telescope Entries
                </h5>

                <span class="badge text-bg-primary">
                    {{ $entries->total() }} Results
                </span>

            </div>

            <div class="table-responsive">

                <table class="table table-hover align-middle">

                    <thead>

                    <tr>

                        <th>#</th>

                        <th>Type</th>

                        <th>Description</th>

                        <th>Status / Duration</th>

                        <th>Date</th>

                        <th>Details</th>

                    </tr>

                    </thead>

                    <tbody>

                    @forelse($entries as $entry)

                        <tr>

                            <td>
                                {{ $entry['sequence'] }}
                            </td>

                            <td>

                                <span class="badge text-bg-dark">

                                    {{ ucfirst($entry['type']) }}

                                </span>

                            </td>

                            <td>

                                <span
                                    class="d-inline-block text-truncate"
                                    style="max-width: 350px;"
                                >
                                    {{ $entry['description'] }}
                                </span>

                            </td>

                            <td>

                                @if(
                                    $entry['type'] === 'request'
                                    && is_numeric($entry['status'])
                                )

                                    @if($entry['status'] >= 400)

                                        <span class="badge text-bg-danger">
                                            {{ $entry['status'] }}
                                        </span>

                                    @else

                                        <span class="badge text-bg-success">
                                            {{ $entry['status'] }}
                                        </span>

                                    @endif

                                @elseif(
                                    $entry['type'] === 'exception'
                                )

                                    <span class="badge text-bg-danger">
                                        Exception
                                    </span>

                                @else

                                    <span class="badge text-bg-secondary">
                                        {{ $entry['status'] }}
                                    </span>

                                @endif

                            </td>

                            <td>

                                <small>
                                    {{ $entry['created_at'] }}
                                </small>

                            </td>

                            <td>

                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-primary"
                                    data-bs-toggle="modal"
                                    data-bs-target="#entryModal{{ $entry['sequence'] }}"
                                >

                                    <i class="bi bi-eye"></i>

                                </button>

                            </td>

                        </tr>

                        <!-- Details Modal -->

                        <div
                            class="modal fade"
                            id="entryModal{{ $entry['sequence'] }}"
                            tabindex="-1"
                        >

                            <div class="modal-dialog modal-lg modal-dialog-scrollable">

                                <div class="modal-content">

                                    <div class="modal-header">

                                        <h5 class="modal-title">

                                            {{ ucfirst($entry['type']) }}

                                            Entry

                                        </h5>

                                        <button
                                            type="button"
                                            class="btn-close"
                                            data-bs-dismiss="modal"
                                        ></button>

                                    </div>

                                    <div class="modal-body">

                                        <div class="mb-3">

                                            <strong>
                                                UUID:
                                            </strong>

                                            <code>
                                                {{ $entry['uuid'] }}
                                            </code>

                                        </div>

                                        <div class="mb-3">

                                            <strong>
                                                Description:
                                            </strong>

                                            <p class="mt-1">
                                                {{ $entry['description'] }}
                                            </p>

                                        </div>

                                        <strong>
                                            Raw Telescope Data:
                                        </strong>

                                        <pre class="bg-dark text-light p-3 rounded mt-2">{{ json_encode(
                                            $entry['content'],
                                            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
                                        ) }}</pre>

                                    </div>

                                </div>

                            </div>

                        </div>

                    @empty

                        <tr>

                            <td
                                colspan="6"
                                class="text-center py-5"
                            >

                                <i
                                    class="bi bi-search fs-1 text-muted"
                                ></i>

                                <h5 class="mt-3">
                                    No Telescope entries found
                                </h5>

                                <p class="text-muted">
                                    Try changing your filters.
                                </p>

                            </td>

                        </tr>

                    @endforelse

                    </tbody>

                </table>

            </div>

            <div class="mt-3">

                {{ $entries->links() }}

            </div>

        </div>

    </div>

</div>

<script>

    /*
     * Feature 6:
     * Preserve current filters during CSV export.
     */

    function exportActivity()
    {
        const form =
            document.getElementById(
                'activityFilterForm'
            );

        const params =
            new URLSearchParams(
                new FormData(form)
            );

        const url =
            "{{ route('monitoring.activity.export') }}"
            + '?'
            + params.toString();

        window.location.href = url;
    }

</script>

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>

</body>

</html>
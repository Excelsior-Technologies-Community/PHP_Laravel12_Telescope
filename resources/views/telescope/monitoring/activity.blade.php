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

        <div class="d-flex gap-2">

            <a
                href="{{ route('monitoring.dashboard') }}"
                class="btn btn-outline-light btn-sm"
            >
                Dashboard
            </a>

            <a
                href="{{ route('monitoring.alerts') }}"
                class="btn btn-outline-light btn-sm"
            >
                Alerts
            </a>

            <a
                href="/telescope"
                target="_blank"
                class="btn btn-outline-info btn-sm"
            >
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
            >

                <div class="row g-3">

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

                    <div class="col-md-3">

                        <label class="form-label fw-semibold">
                            Search
                        </label>

                        <input
                            type="text"
                            name="search"
                            value="{{ request('search') }}"
                            class="form-control"
                            placeholder="URI, query, exception..."
                        >

                    </div>

                    <div class="col-md-2">

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

                    <div class="col-md-2">

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

                    <div class="col-md-2 d-flex align-items-end">

                        <div class="d-flex gap-2 w-100">

                            <button
                                type="submit"
                                class="btn btn-primary flex-fill"
                            >
                                <i class="bi bi-search"></i>
                                Search
                            </button>

                            <a
                                href="{{ route('monitoring.activity') }}"
                                class="btn btn-outline-secondary"
                            >
                                <i class="bi bi-arrow-clockwise"></i>
                            </a>

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

                                @elseif($entry['type'] === 'exception')

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

                                        <pre class="bg-dark text-light p-3 rounded mt-2">{{ json_encode($entry['content'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>

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

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>

</body>

</html>

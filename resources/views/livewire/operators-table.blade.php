<div>
    <div class="filter mb-3 operators-toolbar">
        {{-- Row 1: Search + location filters --}}
        <div class="row align-items-center g-2 mb-2">
            <div class="col-12 col-lg">
                <div class="search">
                    <input wire:model.live.debounce.300ms="search_key" type="text" placeholder="Search operators...">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </div>
            </div>
            @if (isRoot())
                <div class="col-12 col-sm-6 col-lg-3">
                    @include('components.selects.select_region', ['regions' => $regions])
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                    @include('components.selects.select_district', ['districts' => $districts])
                </div>
            @endif
        </div>

        {{-- Row 2: Dates + actions --}}
        <div class="row align-items-center g-2">
            <div class="col-12 col-md">
                <div class="operators-date-range">
                    <div class="date-field">
                        <label class="date-label" for="operators-date-from">From</label>
                        <input id="operators-date-from"
                               type="date"
                               wire:model.live="date_from"
                               class="form-control form-control-sm"
                               max="{{ now()->format('Y-m-d') }}"
                               title="From Date">
                    </div>
                    <span class="date-separator d-none d-sm-inline text-muted">—</span>
                    <div class="date-field">
                        <label class="date-label" for="operators-date-to">To</label>
                        <input id="operators-date-to"
                               type="date"
                               wire:model.live="date_to"
                               class="form-control form-control-sm"
                               max="{{ now()->format('Y-m-d') }}"
                               title="To Date">
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-auto">
                <div class="operators-actions">
                    <button wire:click="refresh" class="btn btn-sm btn-outline-secondary" title="Clear filters">
                        <i class="fa-solid fa-arrows-rotate"></i>
                        <span class="d-md-none ms-1">Refresh</span>
                    </button>

                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-success dropdown-toggle"
                                type="button"
                                id="operatorsDownloadMenu"
                                data-toggle="dropdown"
                                aria-haspopup="true"
                                aria-expanded="false">
                            <i class="fa-solid fa-download me-1"></i>Download
                        </button>
                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="operatorsDownloadMenu">
                            <h6 class="dropdown-header">Operator Stats</h6>
                            <button type="button" class="dropdown-item" wire:click="exportToExcel">
                                <i class="fa-solid fa-file-csv text-success me-2"></i>Stats CSV
                            </button>
                            <button type="button" class="dropdown-item" wire:click="downloadOperatorPDF">
                                <i class="fa-solid fa-file-pdf text-danger me-2"></i>Stats PDF
                            </button>
                            <div class="dropdown-divider"></div>
                            <h6 class="dropdown-header">Operators with Mafundi</h6>
                            <button type="button" class="dropdown-item" wire:click="exportOperatorsWithMafundi">
                                <i class="fa-solid fa-file-csv text-success me-2"></i>Mafundi CSV
                            </button>
                            <button type="button" class="dropdown-item" wire:click="downloadOperatorsWithMafundiPDF">
                                <i class="fa-solid fa-file-pdf text-danger me-2"></i>Mafundi PDF
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Date Filter Active Indicator --}}
    @if($this->hasDateFilter())
        <div class="alert alert-info py-2 mb-3 d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2">
            <small>
                <i class="fa-solid fa-filter me-1"></i>
                Showing data from
                <strong>{{ $date_from ? \Carbon\Carbon::parse($date_from)->format('M d, Y') : 'Beginning' }}</strong>
                to
                <strong>{{ $date_to ? \Carbon\Carbon::parse($date_to)->format('M d, Y') : 'Now' }}</strong>
            </small>
            <button wire:click="$set('date_from', null); $set('date_to', null)" class="btn btn-sm btn-outline-info align-self-start align-self-sm-center">
                <i class="fa-solid fa-times me-1"></i>Clear Dates
            </button>
        </div>
    @endif

    {{-- Cards Grid --}}
    <div class="row g-3">
        @forelse ($operators as $operator)
            @php
                $stats = $this->getOperatorStats($operator);
                $showFiltered = $this->date_from && $this->date_to;
            @endphp

            <div class="col-md-4 col-lg-4" wire:key="op-{{ $operator->id }}">
                <div class="op-card text-decoration-none">

                    {{-- Name + Status --}}
                    <div class="d-flex align-items-start justify-content-between mb-2">
                        <div>
                            <a href="{{ route('operator', $operator) }}"
                                class="fw-semibold text-blue lh-sm">{{ $operator->fname }} {{ $operator->lname }}</a>
                        </div>
                        @if ($operator->phone)
                            <small class="text-muted">
                                {{ $operator->phone }}
                            </small>
                        @endif
                        <span class="badge {{ $operator->status === 'active' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }} rounded-pill ms-2">
                            {{ ucfirst($operator->status) }}
                        </span>
                    </div>

                    <hr class="my-2 opacity-25">

                    <table class="op-mini-table w-100 mt-2">
                        <thead>
                            <tr>
                                <th></th>
                                <th>Sites</th>
                                <th>Fundis</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if ($showFiltered)
                                <tr>
                                    <td class="op-row-label text-primary">Range</td>
                                    <td>{{ number_format($stats['filtered_site_visits'] ?? 0) }}</td>
                                    <td>{{ number_format($stats['filtered_fundis'] ?? 0) }}</td>
                                </tr>
                            @else
                                <tr>
                                    <td class="op-row-label">Today</td>
                                    <td>{{ $stats['today_visits'] }}</td>
                                    <td>{{ $stats['today_fundis'] }}</td>
                                </tr>
                                <tr>
                                    <td class="op-row-label">Week</td>
                                    <td>{{ $stats['week_visits'] }}</td>
                                    <td>{{ $stats['week_fundis'] }}</td>
                                </tr>
                                <tr>
                                    <td class="op-row-label">Month</td>
                                    <td>{{ $stats['month_visits'] }}</td>
                                    <td>{{ $stats['month_fundis'] }}</td>
                                </tr>
                            @endif
                            <tr>
                                <td class="op-row-label">Total</td>
                                <td>{{ number_format($stats['total_visits']) }}</td>
                                <td>{{ number_format($stats['total_fundis']) }}</td>
                            </tr>
                        </tbody>
                    </table>

                </div>
            </div>
        @empty
            <div class="col-12 text-center text-muted py-5">
                <i class="fa-solid fa-user-tie fa-2x mb-2 d-block opacity-25"></i>
                No operators found.
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    <div class="d-flex justify-content-center mt-4">
        {{ $operators->links() }}
    </div>
    <style>
        .filtered-stats {
            border-left: 3px solid #3b82f6;
        }

        .operators-toolbar .operators-date-range {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-end;
            gap: 0.5rem 0.75rem;
        }

        .operators-toolbar .date-field {
            flex: 1 1 140px;
            min-width: 0;
        }

        .operators-toolbar .date-label {
            display: block;
            font-size: 0.75rem;
            color: #6c757d;
            margin-bottom: 0.2rem;
            line-height: 1;
        }

        .operators-toolbar .operators-actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            align-items: center;
            gap: 0.5rem;
        }

        .operators-toolbar .dropdown-menu {
            min-width: 220px;
        }

        .operators-toolbar .dropdown-item {
            cursor: pointer;
        }

        .operators-toolbar .dropdown-header {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        @media (max-width: 767.98px) {
            .operators-toolbar .operators-actions {
                justify-content: stretch;
            }

            .operators-toolbar .operators-actions > .btn,
            .operators-toolbar .operators-actions > .dropdown {
                flex: 1 1 auto;
            }

            .operators-toolbar .operators-actions > .dropdown > .btn {
                width: 100%;
            }

            .operators-toolbar .dropdown-menu {
                width: 100%;
            }
        }

        @media (max-width: 575.98px) {
            .op-card .op-mini-table th,
            .op-card .op-mini-table td {
                font-size: 0.8rem;
                padding: 0.2rem 0.25rem;
            }
        }
    </style>
</div>


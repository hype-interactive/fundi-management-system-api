<div>
    <div class="filter mb-3">
        <div class="row align-items-center">
            <div class="col-md-2">
                <div class="search">
                    <input wire:model.live.debounce.300ms="search_key" type="text" placeholder="Search operators...">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </div>
            </div>
            <div class="col-md-4">
                <div class="row g-2">
                    @if (isRoot())
                        <div class="col">
                            @include('components.selects.select_region', ['regions' => $regions])
                        </div>
                        <div class="col">
                            @include('components.selects.select_district', ['districts' => $districts])
                        </div>
                    @endif
                </div>
            </div>
            {{-- Date Range Filters --}}
            <div class="col-md-3">
                <div class="row g-2">
                    <div class="col-6">
                        <input type="date" 
                               wire:model.live="date_from" 
                               class="form-control form-control-sm py-3"
                               placeholder="From"
                               max="{{ now()->format('Y-m-d') }}"
                               title="From Date">
                    </div>
                    <div class="col-6">
                        <input type="date" 
                               wire:model.live="date_to" 
                               class="form-control form-control-sm py-3"
                               placeholder="To"
                               max="{{ now()->format('Y-m-d') }}"
                               title="To Date">
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="d-flex justify-content-end gap-2">
                    <button wire:click="refresh" class="btn btn-sm btn-outline-secondary">
                        <i class="fa-solid fa-arrows-rotate"></i>
                    </button>
                    <button wire:click="exportToExcel" class="btn btn-sm btn-outline-success ml-1">
                        <i class="fa-solid fa-file-csv me-1"></i>Export
                    </button>
                    <button wire:click="downloadOperatorPDF" class="btn btn-sm btn-outline-danger ml-1">
                        <i class="fa-solid fa-file-pdf me-1"></i>PDF
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Date Filter Active Indicator --}}
    @if($this->hasDateFilter())
        <div class="alert alert-info py-2 mb-3 d-flex justify-content-between align-items-center">
            <small>
                <i class="fa-solid fa-filter me-1"></i>
                Showing data from 
                <strong>{{ $date_from ? \Carbon\Carbon::parse($date_from)->format('M d, Y') : 'Beginning' }}</strong>
                to 
                <strong>{{ $date_to ? \Carbon\Carbon::parse($date_to)->format('M d, Y') : 'Now' }}</strong>
            </small>
            <button wire:click="$set('date_from', null); $set('date_to', null)" class="btn btn-sm btn-outline-info">
                <i class="fa-solid fa-times me-1"></i>Clear Dates
            </button>
        </div>
    @endif

    {{-- Cards Grid --}}
    <div class="row g-3">
        @forelse ($operators as $operator)
            @php
                $stats = $this->getOperatorStats($operator);
                $showFiltered = $this->hasDateFilter();
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
</style>
</div>


<div class="site-visits-wrapper">
    <!-- Mobile Filter Toggle -->
    <div class="mobile-filter-toggle d-md-none mb-3">
        <div class="d-flex justify-content-between align-items-center">
            <button wire:click="$toggle('showMobileFilters')" class="btn btn-outline-primary btn-sm">
                <i class="fa-solid fa-filter me-1"></i>
                Filters
                <i class="fa-solid fa-chevron-{{ $showMobileFilters ? 'up' : 'down' }} ms-1"></i>
            </button>
            <div class="d-flex gap-2">
                <button wire:click="exportToExcel" class="btn btn-outline-success btn-sm">
                    <i class="fa-solid fa-file-excel me-1"></i>
                    Excel
                </button>
                <button wire:click="downloadSiteVisitPDF" class="btn btn-outline-danger btn-sm">
                    <i class="fa-solid fa-file-pdf me-1"></i>
                    PDF
                </button>
            </div>
        </div>
    </div>

    <!-- Enhanced Filters Section with Two Rows -->
    <div class="filter mb-3 {{ !$showMobileFilters ? 'd-none d-md-block' : '' }}">

        <!-- First Row: Search + Primary Location Filters -->
        <div class="row g-3 mb-3">
            
            <!-- Primary Location Filters -->
            <div class="col-md-12 col-12">
                <div class="row g-2">
                    <div class="col-md-2 col-12">
                <div class="search position-relative">
                    <input wire:model.live.debounce.300ms="search_key" type="text" id="search"
                        placeholder="Search site name, operator...">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    @if ($search_key)
                        <button wire:click="$set('search_key', '')" class="btn btn-sm position-absolute"
                            style="right: 35px; top: 50%; transform: translateY(-50%); border: none; background: none;">
                            <i class="fa-solid fa-times text-muted"></i>
                        </button>
                    @endif
                </div>
            </div>
                    @if (isRoot())
                        <div class="col-lg-2 col-md-4 col-6">
                            <div class="select">
                                @include('components.selects.select_region')
                            </div>
                        </div>
                        <div class="col-lg-2 col-md-4 col-6">
                            <div class="select">
                                @include('components.selects.select_district')
                            </div>
                        </div>
                    @endif

                    <div class="col-lg-2 col-md-4 col-6">
                        <div class="select">
                            @include('components.selects.select_ward')
                        </div>
                    </div>

                    <div class="col-lg-2 col-md-4 col-6">
                        <div class="select">
                            <select wire:model.live="status" class="form-select form-select-sm">
                                <option value="">All Status</option>
                                @foreach ($status_options as $key => $value)
                                    <option value="{{ $key }}">{{ $value }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <!-- Operator Filter -->
                    <div class="col-lg-2 col-md-4 col-6">
                        <div class="select">
                            <select wire:model.live="operator_id" class="form-select form-select-sm">
                                <option value="">All Operators</option>
                                @foreach ($operators as $operator)
                                    <option value="{{ $operator->id }}">{{ $operator->full_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Date Filters -->
                    <div class="col-lg-2 col-md-4 col-6">
                        <div class="select">
                            <input wire:model.live="visit_date_from" type="date" class="form-select form-select-sm"
                                placeholder="From Date" title="From Date">
                        </div>
                    </div>

                    <div class="col-lg-2 col-md-4 col-6 ">
                        <div class="select">
                            <input wire:model.live="visit_date_to" type="date" class="form-select form-select-sm"
                                placeholder="To Date" title="To Date">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Desktop Actions -->
    <div class="results-summary mb-2 d-none d-md-flex justify-content-between align-items-center">
        <div class="d-flex gap-2 align-items-center">
            <span class="results-count">
                <i class="fa-solid fa-location me-1"></i>
                {{ number_format($siteVisits->total()) }} Sites
            </span>
        </div>

        <div class="d-flex gap-2">
            <button wire:click="toggleCompactView"
                class="btn btn-sm {{ $compactView ? 'btn-primary' : 'btn-outline-primary' }}">
                <i class="fa-solid fa-{{ $compactView ? 'expand' : 'compress' }}"></i>
                {{ $compactView ? 'Expand' : 'Compact' }}
            </button>
            <button wire:click="refresh" class="btn btn-outline-secondary btn-sm">
                <i class="fa-solid fa-arrows-rotate me-1"></i>
                Clear Filters
            </button>
            <button wire:click="exportToExcel" class="btn btn-outline-success btn-sm">
                <i class="fa-solid fa-file-excel me-1"></i>
                Export Excel
            </button>
            <button wire:click="downloadSiteVisitPDF" class="btn btn-outline-danger btn-sm">
                <i class="fa-solid fa-file-pdf me-1"></i>
                Download PDF
            </button>
        </div>
    </div>

    <!-- Table Container -->
    <div class="custom-table">
        <!-- Desktop Table -->
        <div class="d-none d-md-block">
            <table class="table">
                <thead class="thead-light">
                    <tr>
                        <th scope="col">
                            <a wire:click="refresh" class="cursor-pointer">
                                <i class="fa-solid fa-arrows-rotate"></i>
                            </a>
                        </th>
                        <th scope="col">Site Details</th>
                        @if (!$compactView)
                            <th scope="col" class="d-lg-table-cell d-none">Operator</th>
                            <th scope="col" class="d-lg-table-cell d-none">Visit Date</th>
                            <th scope="col" class="d-xl-table-cell d-none">Location</th>
                        @else
                            <th scope="col">Visit Info</th>
                        @endif
                        <th scope="col" class="d-lg-table-cell d-none">Status</th>
                        <th scope="col" class="d-xl-table-cell d-none">Photos</th>
                        <th scope="col" class="d-xl-table-cell d-none">GPS</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($siteVisits as $visit)
                        <tr wire:key="visit-{{ $visit->id }}">
                            <th scope="row">
                                <a href="{{ route('site-visit', [$visit]) }}" class="avatar-circle">
                                    <i class="fa-solid fa-map-location-dot"></i>
                                </a>
                            </th>
                            <td>
                                <a href="{{ route('site-visit', $visit) }}" class="text-decoration-none">
                                    <div class="visit-details">
                                        <span class="fw-semibold site-name">{{ $visit->site_name }}</span>
                                        {{-- @if ($visit->site_description)
                                            <small class="text-muted d-block site-description">
                                                {{ Str::limit($visit->site_description, 50) }}
                                            </small>
                                        @endif --}}
                                    </div>
                                </a>
                            </td>
                            @if (!$compactView)
                                <td class="d-lg-table-cell d-none">
                                    <span class="operator-name">{{ $visit->operator->full_name ?? 'N/A' }}</span>
                                </td>
                                <td class="d-lg-table-cell d-none">
                                    <span class="visit-date">{{ $visit->visit_date->format('M d, Y') }}</span>
                                </td>
                                <td class="d-xl-table-cell d-none">
                                    <span class="location-name">
                                        {{ $visit->ward->name ?? 'N/A' }},
                                        {{ $visit->ward->district->name ?? 'N/A' }}
                                    </span>
                                </td>
                            @else
                                <td>
                                    <div class="visit-info">
                                        <div class="operator-name fw-medium">
                                            {{ $visit->operator->full_name ?? 'N/A' }}</div>
                                        <div class="visit-date text-muted small">
                                            {{ $visit->visit_date->format('M d, Y') }}</div>
                                        <div class="location-name text-muted small">
                                            {{ $visit->ward->name ?? 'N/A' }},
                                            {{ $visit->ward->district->name ?? 'N/A' }}
                                        </div>
                                    </div>
                                </td>
                            @endif
                            <td class="d-lg-table-cell d-none">
                                @if ($visit->status === 'rejected')
                                    <span class="text-danger">Rejected</span>
                                @elseif ($visit->status === 'approved')
                                    <span class="text-success">Approved</span>
                                @else
                                    <span class="text-warning">Pending</span>
                                @endif
                            </td>
                            <td class="d-xl-table-cell d-none">
                                <span class="photos-count">
                                    <i class="fa-solid fa-camera me-1"></i>
                                    {{ $visit->photos->count() }}
                                </span>
                            </td>
                            <td class="d-xl-table-cell d-none">
                                @if ($visit->gps_latitude && $visit->gps_longitude)
                                    <span class="gps-coords text-success">
                                        <i class="fa-solid fa-location-dot me-1"></i>
                                        Available
                                    </span>
                                @else
                                    <span class="gps-coords text-muted">
                                        <i class="fa-solid fa-location-slash me-1"></i>
                                        N/A
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                <i class="fa-solid fa-map-location-dot me-2"></i>
                                No site visits found matching your criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Mobile Cards -->
        <div class="mobile-cards d-md-none">
            @forelse ($siteVisits as $visit)
                <div class="visit-card" wire:key="mobile-{{ $visit->id }}">
                    <div class="card-header">
                        <div class="visit-avatar">
                            <a href="{{ route('site-visit', [$visit]) }}" class="avatar-circle">
                                <i class="fa-solid fa-map-location-dot"></i>
                            </a>
                        </div>
                        <div class="visit-details">
                            <a href="{{ route('site-visit', $visit) }}" class="text-decoration-none">
                                <h6 class="site-name mb-1">{{ $visit->site_name }}</h6>
                            </a>
                            <div class="visit-meta">
                                @if ($visit->rejection_reason)
                                    <span class="badge bg-danger">Rejected</span>
                                @elseif($visit->approved_at)
                                    <span class="badge bg-success">Approved</span>
                                @else
                                    <span class="badge bg-warning">Pending</span>
                                @endif
                                <span class="photos-count ms-2">
                                    <i class="fa-solid fa-camera me-1"></i>
                                    {{ $visit->photos->count() }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        @if ($visit->site_description)
                            <div class="description-row">
                                <i class="fa-solid fa-info-circle me-2 text-muted"></i>
                                <span>{{ Str::limit($visit->site_description, 80) }}</span>
                            </div>
                        @endif
                        <div class="operator-row">
                            <i class="fa-solid fa-user me-2 text-muted"></i>
                            <span>{{ $visit->operator->full_name ?? 'N/A' }}</span>
                        </div>
                        <div class="date-row">
                            <i class="fa-solid fa-calendar me-2 text-muted"></i>
                            <span>{{ $visit->visit_date->format('M d, Y') }}</span>
                        </div>
                        <div class="location-row">
                            <i class="fa-solid fa-map-marker-alt me-2 text-muted"></i>
                            <span>{{ $visit->ward->name ?? 'N/A' }},
                                {{ $visit->ward->district->name ?? 'N/A' }}</span>
                        </div>
                        @if ($visit->gps_latitude && $visit->gps_longitude)
                            <div class="gps-row">
                                <i class="fa-solid fa-location-dot me-2 text-success"></i>
                                <span class="text-success">GPS Available</span>
                            </div>
                        @endif
                        @if ($visit->fundis->count() > 0 || $visit->products->count() > 0)
                            <div class="additional-info">
                                @if ($visit->fundis->count() > 0)
                                    <span class="badge bg-info me-2">
                                        <i class="fa-solid fa-users me-1"></i>
                                        {{ $visit->fundis->count() }} Mafundi
                                    </span>
                                @endif
                                @if ($visit->products->count() > 0)
                                    <span class="badge bg-secondary">
                                        <i class="fa-solid fa-box me-1"></i>
                                        {{ $visit->products->count() }} Products
                                    </span>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="empty-state text-center py-5">
                    <i class="fa-solid fa-map-location-dot fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No site visits found</h5>
                    <p class="text-muted">Try adjusting your search criteria.</p>
                </div>
            @endforelse
        </div>
    </div>

    <!-- Pagination -->
    <div class="pagination-wrapper d-flex justify-content-center mt-3">
        {{ $siteVisits->links() }}
    </div>
</div>

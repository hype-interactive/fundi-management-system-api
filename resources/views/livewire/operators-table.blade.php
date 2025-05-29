<div>
    <!-- Filters Section -->
    <div class="filter mb-1">
        <div class="row">
            <div class="col-md-3">
                <div class="search">
                    <input wire:model.live.debounce.300ms="search_key" type="text" id="search"
                        placeholder="Search operators...">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </div>
            </div>
            <div class="col-md-9">
                <div class="row">
                    @if (isRoot())
                        <div class="col">
                            <div class="select">
                                @include('components.selects.select_region', ['regions' => $regions])
                            </div>
                        </div>
                        <div class="col">
                            <div class="select">
                                @include('components.selects.select_district', ['districts' => $districts])
                            </div>
                        </div>
                    @endif
                    <div class="col">
                        <div class="select">
                            <select wire:model.live="status" class="form-select">
                                <option value="">All Status</option>
                                @foreach ($status_options as $key => $value)
                                    <option value="{{ $key }}">{{ $value }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Desktop Actions -->
    <div class="results-summary mb-2 d-flex justify-content-between align-items-center">
        <div class="d-flex gap-2">
            <button wire:click="$toggle('compactView')"
                class="btn btn-sm {{ $compactView ? 'btn-primary' : 'btn-outline-primary' }}">
                <i class="fa-solid fa-{{ $compactView ? 'expand' : 'compress' }}"></i>
                {{ $compactView ? 'Expand' : 'Compact' }}
            </button>
        </div>
        <div class="d-flex gap-2">
            <button wire:click="refresh" class="btn btn-outline-secondary btn-sm">
                <i class="fa-solid fa-arrows-rotate me-1"></i>
                Clear Filters
            </button>
            <button wire:click="exportToExcel" class="btn btn-outline-success btn-sm">
                <i class="fa-solid fa-file-excel me-1"></i>
                Export Excel
            </button>
            <button wire:click="downloadOperatorPDF" class="btn btn-outline-danger btn-sm">
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
                            <a wire:click="refresh">
                                <i class="fa-solid fa-arrows-rotate"></i>
                            </a>
                        </th>
                        <th scope="col">Name</th>
                        @if (!$compactView)
                            <th scope="col" class="d-lg-table-cell d-none">Email</th>
                            <th scope="col" class="d-xl-table-cell d-none">Phone</th>
                        @else
                            <th scope="col">Contact</th>
                        @endif
                        <th scope="col" class="d-lg-table-cell d-none">Status</th>
                        <th scope="col" class="d-xl-table-cell d-none">Districts</th>
                        <th scope="col" class="d-xl-table-cell d-none">Performance</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($operators as $operator)
                        <tr>
                            <th scope="row">
                                <a href="{{ route('operator', [$operator]) }}" class="avatar-circle">
                                    {{-- @if ($operator->profile_photo)
                                        <img src="{{ asset('storage/' . $operator->profile_photo) }}"
                                            alt="{{ $operator->full_name }}" class="avatar-img">
                                    @else
                                        <i class="fa-solid fa-user-tie"></i>
                                    @endif --}}
                                    <i class="fa-solid fa-user-tie"></i>
                                </a>
                            </th>
                            <td>
                                <a href="{{ route('operator', $operator) }}" class="text-decoration-none">
                                    <div class="operator-name">
                                        <span class="fw-semibold">{{ $operator->fname }} {{ $operator->lname }}</span>
                                        @if ($operator->mname)
                                            <small class="text-muted d-block">{{ $operator->mname }}</small>
                                        @endif
                                    </div>
                                </a>
                            </td>
                            @if (!$compactView)
                                <td class="d-lg-table-cell d-none">
                                    <span class="email-address">{{ $operator->email }}</span>
                                </td>
                                <td class="d-xl-table-cell d-none">
                                    <span class="phone-number">{{ $operator->phone ?? '-' }}</span>
                                </td>
                            @else
                                <td>
                                    <div class="contact-info">
                                        <div class="email-address">{{ $operator->email }}</div>
                                        @if ($operator->phone)
                                            <small class="phone-number">{{ $operator->phone }}</small>
                                        @endif
                                    </div>
                                </td>
                            @endif
                            <td class="d-lg-table-cell d-none">
                                @include('components.general-status-badge', ['status' => $operator->status])
                            </td>
                            <td class="d-xl-table-cell d-none">
                                <div class="districts-list">
                                    @if ($operator->districts->count() > 0)
                                        @foreach ($operator->districts->take(2) as $district)
                                            <span class="district-badge">{{ $district->name }}</span>
                                        @endforeach
                                        @if ($operator->districts->count() > 2)
                                            <small class="text-muted">+{{ $operator->districts->count() - 2 }}
                                                more</small>
                                        @endif
                                    @else
                                        <span class="text-muted">No districts</span>
                                    @endif
                                </div>
                            </td>
                            <td class="d-xl-table-cell d-none">
                                <div class="performance-metrics">
                                    <small class="d-block">
                                        <i class="fa-solid fa-map-marker-alt me-1"></i>
                                        {{ $operator->siteVisits()->count() }} visits
                                    </small>
                                    <small class="d-block">
                                        <i class="fa-solid fa-users me-1"></i>
                                        {{ $operator->approvedFundis()->count() }} fundis
                                    </small>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                <i class="fa-solid fa-user-tie me-2"></i>
                                No operators found matching your criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Mobile Cards -->
        <div class="mobile-cards d-md-none">
            @forelse ($operators as $operator)
                <div class="operator-card" wire:key="mobile-{{ $operator->id }}">
                    <div class="card-header">
                        <div class="operator-avatar">
                            <a href="{{ route('operator', [$operator]) }}" class="avatar-circle">
                                @if ($operator->profile_photo)
                                    <img src="{{ asset('storage/' . $operator->profile_photo) }}"
                                        alt="{{ $operator->full_name }}" class="avatar-img">
                                @else
                                    <i class="fa-solid fa-user-tie"></i>
                                @endif
                            </a>
                        </div>
                        <div class="operator-details">
                            <a href="{{ route('operator', $operator) }}" class="text-decoration-none">
                                <h6 class="operator-name mb-1">{{ $operator->full_name }}</h6>
                            </a>
                            <div class="operator-meta">
                                {{-- @include('components.role-badge', ['role' => $operator->role]) --}}
                                @include('components.general-status-badge', ['status' => $operator->status])
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="contact-row">
                            <i class="fa-solid fa-envelope me-2 text-muted"></i>
                            <span>{{ $operator->email }}</span>
                        </div>
                        @if ($operator->phone)
                            <div class="contact-row">
                                <i class="fa-solid fa-phone me-2 text-muted"></i>
                                <span>{{ $operator->phone }}</span>
                            </div>
                        @endif
                        <div class="districts-row">
                            <i class="fa-solid fa-map-marker-alt me-2 text-muted"></i>
                            <span>
                                @if ($operator->districts->count() > 0)
                                    {{ $operator->districts->pluck('name')->take(2)->join(', ') }}
                                    @if ($operator->districts->count() > 2)
                                        <small class="text-muted">(+{{ $operator->districts->count() - 2 }}
                                            more)</small>
                                    @endif
                                @else
                                    No districts assigned
                                @endif
                            </span>
                        </div>
                        <div class="performance-row">
                            <div class="performance-metrics">
                                <div class="metric">
                                    <i class="fa-solid fa-map-marker-alt me-1"></i>
                                    <span>{{ $operator->siteVisits()->count() }} visits</span>
                                </div>
                                <div class="metric">
                                    <i class="fa-solid fa-users me-1"></i>
                                    <span>{{ $operator->approvedFundis()->count() }} fundis</span>
                                </div>
                                <div class="metric">
                                    <i class="fa-solid fa-coins me-1"></i>
                                    <span>TSh {{ number_format($operator->incentives()->sum('amount')) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="empty-state text-center py-5">
                    <i class="fa-solid fa-user-tie fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No operators found</h5>
                    <p class="text-muted">Try adjusting your search criteria.</p>
                </div>
            @endforelse
        </div>
    </div>

    <!-- Pagination -->
    <div class="pagination-wrapper d-flex justify-content-center mt-3">
        {{ $operators->links() }}
    </div>
</div>

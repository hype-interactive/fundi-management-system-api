<div>
    <!-- Filters Section -->
    <div class="filter mb-1">
        <div class="row">
            <div class="col-md-3">
                <div class="search">
                    <input wire:model.live.debounce.300ms="search_key" type="text" id="search">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </div>
            </div>
            <div class="col-md-9">
                <div class="row">
                    @if (isRoot())
                        <div class="col">
                            <div class="select">
                                @include('components.selects.select_region')
                            </div>
                        </div>
                        <div class="col">
                            <div class="select">
                                @include('components.selects.select_district')
                            </div>
                        </div>
                    @endif
                    <div class="col">
                        <div class="select">
                            @include('components.selects.select_ward')
                        </div>
                    </div>
                    <div class="col">
                        <div class="select">
                            @include('components.selects.select_ranking')
                        </div>
                    </div>
                    <div class="col">
                        <div class="select">
                            @include('components.selects.select_approval')
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
            <button wire:click="downloadFundiPDF" class="btn btn-outline-danger btn-sm">
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
                            <th scope="col" class="d-lg-table-cell d-none">Phone</th>
                            <th scope="col" class="d-xl-table-cell d-none">Trans. Phone</th>
                        @else
                            <th scope="col">Contact</th>
                        @endif
                        <th scope="col" class="d-lg-table-cell d-none">Ranking</th>
                        <th scope="col" class="d-lg-table-cell d-none">Status</th>
                        <th scope="col" class="d-xl-table-cell d-none">Registrar</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($fundis as $fundi)
                        <tr>
                            <th scope="row">
                                <a href="{{ route('fundi', [$fundi]) }}" class="avatar-circle">
                                    <i class="fa-solid fa-user"></i>
                                </a>
                            </th>
                            <td>
                                <a href="{{ route('fundi', $fundi) }}" class="text-decoration-none">
                                    <div class="fundi-name">
                                        <span class="fw-semibold">{{ $fundi->fname }} {{ $fundi->lname }}</span>
                                        @if ($fundi->mname)
                                            <small class="text-muted d-block">{{ $fundi->mname }}</small>
                                        @endif
                                    </div>
                                </a>
                            </td>
                            @if (!$compactView)
                                <td class="d-lg-table-cell d-none">
                                    <span class="phone-number">{{ $fundi->primary_phone }}</span>
                                </td>
                                <td class="d-xl-table-cell d-none">
                                    <span class="phone-number">{{ $fundi->transaction_phone ?? '-' }}</span>
                                </td>
                            @else
                                <td>
                                    <div class="contact-info">
                                        <div class="phone-number">{{ $fundi->primary_phone }}</div>
                                        @if ($fundi->transaction_phone && $fundi->transaction_phone !== $fundi->primary_phone)
                                            <small class="text-muted">{{ $fundi->transaction_phone }}</small>
                                        @endif
                                    </div>
                                </td>
                            @endif
                            <td class="d-lg-table-cell d-none">
                                @include('components.ranking-badge', ['ranking' => $fundi->ranking])
                            </td>
                            <td class="d-lg-table-cell d-none">
                                @include('components.approval-status-badge', [
                                    'approved_at' => $fundi->approved_at,
                                ])
                            </td>
                            <td class="d-xl-table-cell d-none">
                                <span class="creator-name">{{ $fundi->creator->full_name ?? 'N/A' }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                <i class="fa-solid fa-users me-2"></i>
                                No fundis found matching your criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Mobile Cards -->
        <div class="mobile-cards d-md-none">
            @forelse ($fundis as $fundi)
                <div class="fundi-card" wire:key="mobile-{{ $fundi->id }}">
                    <div class="card-header">
                        <div class="fundi-avatar">
                            <a href="{{ route('fundi', [$fundi]) }}" class="avatar-circle">
                                <i class="fa-solid fa-user"></i>
                            </a>
                        </div>
                        <div class="fundi-details">
                            <a href="{{ route('fundi', $fundi) }}" class="text-decoration-none">
                                <h6 class="fundi-name mb-1">{{ $fundi->full_name }}</h6>
                            </a>
                            <div class="fundi-meta">
                                @include('components.ranking-badge', ['ranking' => $fundi->ranking])
                                @include('components.approval-status-badge', [
                                    'approved_at' => $fundi->approved_at,
                                ])
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="contact-row">
                            <i class="fa-solid fa-phone me-2 text-muted"></i>
                            <span>{{ $fundi->primary_phone }}</span>
                        </div>
                        @if ($fundi->transaction_phone && $fundi->transaction_phone !== $fundi->primary_phone)
                            <div class="contact-row">
                                <i class="fa-solid fa-mobile-alt me-2 text-muted"></i>
                                <span>{{ $fundi->transaction_phone }}</span>
                            </div>
                        @endif
                        <div class="location-row">
                            <i class="fa-solid fa-map-marker-alt me-2 text-muted"></i>
                            <span>{{ $fundi->ward->name ?? 'N/A' }}</span>
                        </div>
                        @if ($fundi->creator)
                            <div class="creator-row">
                                <i class="fa-solid fa-user-plus me-2 text-muted"></i>
                                <small class="text-muted">Registered by {{ $fundi->creator->full_name }}</small>
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="empty-state text-center py-5">
                    <i class="fa-solid fa-users fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No fundis found</h5>
                    <p class="text-muted">Try adjusting your search criteria.</p>
                </div>
            @endforelse
        </div>
    </div>

    <!-- Pagination -->
    <div class="pagination-wrapper d-flex justify-content-center mt-3">
        {{ $fundis->links() }}
    </div>
</div>

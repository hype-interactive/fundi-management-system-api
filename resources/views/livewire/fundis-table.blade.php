<div>
    <!-- Add polling when download is in progress -->
    @if ($bulkDownloadInProgress)
        <div wire:poll.2s="checkBulkDownloadStatus"></div>
    @endif
    @if ($pdfExportInProgress)
        <div wire:poll.2s="checkPdfExportStatus"></div>
    @endif
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
                    {{-- @if (isRoot()) --}}
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
                    {{-- @endif --}}
                    <div class="col">
                        <div class="select">
                            @include('components.selects.select_ward')
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
        <div class="row g-2 mt-2 align-items-end">
            <div class="col-12 col-sm-6 col-md-3">
                <label for="fundi-date-from" class="form-label small mb-1">Registered from</label>
                <input id="fundi-date-from" wire:model.live="date_from" type="date"
                    class="form-control" max="{{ $date_to ?: now()->toDateString() }}">
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <label for="fundi-date-to" class="form-label small mb-1">Registered to</label>
                <input id="fundi-date-to" wire:model.live="date_to" type="date"
                    class="form-control" min="{{ $date_from }}" max="{{ now()->toDateString() }}">
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <label for="fundi-site-visit-min" class="form-label small mb-1">Min site visits</label>
                <input id="fundi-site-visit-min" wire:model.live="site_visit_min" type="number" min="0"
                    class="form-control">
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <label for="fundi-site-visit-max" class="form-label small mb-1">Max site visits</label>
                <input id="fundi-site-visit-max" wire:model.live="site_visit_max" type="number" min="0"
                    class="form-control">
            </div>
        </div>
    </div>
    {{-- Download Status Alert --}}
    @if ($bulkDownloadInProgress)
        <div class="alert alert-info d-flex justify-content-between align-items-center mb-3" role="alert">
            <div>
                <i class="fa-solid fa-spinner fa-spin me-2"></i>
                <strong>Generating ID cards...</strong> This may take a few minutes. Please wait.
            </div>
            <button wire:click="resetDownload" class="btn btn-sm btn-outline-secondary">
                Cancel
            </button>
        </div>
    @endif

    @if ($downloadUrl)
        <div class="alert alert-success mb-3">
            <div class="d-flex justify-content-between align-items-center">
                <span>
                    <i class="fa-solid fa-check-circle me-2"></i>
                    Your ID cards are ready!
                </span>
                <a href="{{ $downloadUrl }}" download class="btn btn-success btn-sm">
                    <i class="fa-solid fa-download me-1"></i>
                    Download ZIP
                    ({{ round(Storage::disk('public')->size('downloads/' . basename($downloadUrl)) / 1024 / 1024, 2) }}
                    MB)
                </a>
            </div>
        </div>
    @endif

    {{-- PDF Export Status Alert --}}
    @if ($pdfExportInProgress)
        <div class="alert alert-info d-flex justify-content-between align-items-center mb-3" role="alert">
            <div>
                <i class="fa-solid fa-spinner fa-spin me-2"></i>
                <strong>Generating PDF report...</strong> This may take a few minutes. Please wait.
            </div>
            <button wire:click="resetPdfExport" class="btn btn-sm btn-outline-secondary">
                Cancel
            </button>
        </div>
    @endif

    @if ($pdfDownloadUrl)
        <div class="alert alert-success mb-3">
            <div class="d-flex justify-content-between align-items-center">
                <span>
                    <i class="fa-solid fa-check-circle me-2"></i>
                    Your PDF report is ready!
                </span>
                <a href="{{ $pdfDownloadUrl }}" download class="btn btn-success btn-sm">
                    <i class="fa-solid fa-download me-1"></i>
                    Download PDF
                    ({{ round(Storage::disk('public')->size('downloads/' . basename($pdfDownloadUrl)) / 1024 / 1024, 2) }}
                    MB)
                </a>
            </div>
        </div>
    @endif

    {{-- Bulk Actions Bar --}}
    @if (count($selectedFundis) > 0)
        <div class="alert alert-info d-flex justify-content-between align-items-center mb-3" role="alert">
            <div>
                <i class="fa-solid fa-check-double me-2"></i>
                <strong>{{ count($selectedFundis) }}</strong> fundi(s) selected
            </div>
            <div>
                <button wire:click="bulkDelete" class="btn btn-danger btn-sm">
                    <i class="fa-solid fa-trash me-1"></i>
                    Delete Selected
                </button>
            </div>
        </div>
    @endif



    <!-- Desktop Actions -->
    <div class="results-summary mb-2 d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <div class="d-flex flex-wrap gap-2">
            <div class="results-card">
                <i class="fa-solid fa-users me-2"></i>
                <span>{{ number_format($fundis->total()) }} Fundi{{ $fundis->total() !== 1 ? 's' : '' }}</span>
            </div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <button wire:click="$toggle('compactView')"
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
            <button wire:click="downloadFundisPDF" class="btn btn-outline-warning btn-sm"
                @if ($pdfExportInProgress) disabled @endif>
                <i class="fa-solid fa-file-pdf me-1"></i>
                Download PDF
            </button>

            {{-- Bulk Download ID Cards Button --}}
            <button wire:click="bulkDownloadIdCards" class="btn btn-outline-danger btn-sm"
                @if ($bulkDownloadInProgress) disabled @endif>
                <i class="fa-solid fa-id-card me-1"></i>
                Download ID Cards ({{ $fundis->total() }})
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
                        {{-- <th scope="col">
                            <input type="checkbox" wire:model.live="selectAll" class="form-check-input"
                                @if (count($fundis) === 0) disabled @endif>
                        </th> --}}
                        <th scope="col">
                            <a wire:click="refresh">
                                <i class="fa-solid fa-arrows-rotate"></i>
                            </a>
                        </th>
                        <th scope="col">Name</th>
                        @if (!$compactView)
                            <th scope="col" class="d-lg-table-cell d-none">Phone</th>
                        @else
                            <th scope="col">Contact</th>
                        @endif
                        <th scope="col" class="d-lg-table-cell d-none">Ranking</th>
                        <th scope="col" class="d-lg-table-cell d-none">Status</th>
                        <th scope="col" class="d-xl-table-cell d-none">Sites</th>
                        <th scope="col" class="d-xl-table-cell d-none">Putty Bags</th>
                        <th scope="col" class="d-xl-table-cell d-none">Location</th>
                        <th scope="col" class="d-xl-table-cell d-none">Registrar</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($fundis as $fundi)
                        <tr>
                            {{-- <td>
                                <input type="checkbox" wire:model.live="selectedFundis" value="{{ $fundi->id }}"
                                    class="form-check-input">
                            </td> --}}
                            <th scope="row">
                                <a href="{{ route('fundi', [$fundi]) }}" class="avatar-circle">
                                    <i class="fa-solid fa-user"></i>
                                </a>
                            </th>
                            <td>
                                <a href="{{ route('fundi', $fundi) }}" class="text-decoration-none">
                                    <div class="fundi-name">
                                        <span class="fw-semibold">{{ $fundi->fname }} {{ $fundi->lname }}</span>
                                        {{-- @if ($fundi->mname)
                                            <small class="text-muted d-block">{{ $fundi->mname }}</small>
                                        @endif --}}
                                    </div>
                                </a>
                            </td>
                            @if (!$compactView)
                                <td class="d-lg-table-cell d-none">
                                    <span class="phone-number">{{ $fundi->primary_phone }}</span>
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
                                    'rejected_at' => $fundi->rejected_at,
                                ])
                            </td>
                            <td class="d-xl-table-cell d-none">
                                <span class="phone-number">{{ $fundi->site_visits_count ?? '0' }}</span>
                            </td>
                             <td class="d-xl-table-cell d-none">
                                <span class="phone-number">{{ $fundi->bags_total ?? '0' }}</span>
                            </td>
                            <td class="d-xl-table-cell d-none">
                                <span class="phone-number">{{ $fundi->address ?? '-' }}</span>
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
                        <div class="fundi-checkbox">
                            <input type="checkbox" wire:model.live="selectedFundis" value="{{ $fundi->id }}"
                                class="form-check-input">
                        </div>
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
                                    'rejected_at' => $fundi->rejected_at,
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
    @script
        <script>
            // Auto-trigger download when ready
            $wire.on('trigger-download', (event) => {
                console.log('Triggering download:', event.url);

                // Create temporary link and click it
                const link = document.createElement('a');
                link.href = event.url;
                link.download = '';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);

                // Optional: Show success notification
                alert('Download started! Check your Downloads folder.');
            });
        </script>
    @endscript
</div>

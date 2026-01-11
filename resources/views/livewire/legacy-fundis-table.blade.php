<div class="legacy-fundis-table-wrapper">
    <!-- Filter Section -->
    {{-- <div class="filters-section mb-4">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="search">
                            <input wire:model.live.debounce.300ms="search" type="text" class="form-control"
                                placeholder="Search name, phone, NIDA...">
                            <i class="fa-solid fa-search"></i>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <select wire:model.live="region" class="select">
                            <option value="">All Regions</option>
                            @foreach ($regions as $region)
                                <option value="{{ $region }}">{{ $region }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select wire:model.live="district" class="select">
                            <option value="">All Districts</option>
                            @foreach ($districts as $district)
                                <option value="{{ $district }}">{{ $district }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select wire:model.live="status" class="select">
                            <option value="">All Status</option>
                            @foreach ($statusOptions as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select wire:model.live="matchStatus" class="select">
                            <option value="">All Matches</option>
                            @foreach ($matchStatusOptions as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-1">
                        <button wire:click="clearFilters" class="btn btn-outline-secondary w-100" title="Clear Filters">
                            <i class="fa-solid fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div> --}}

    <!-- Stats Cards -->
    <div class="stats-cards mb-4">
        <div class="row">
            <div class="col-md-3">
                <div class="stat-card matched">
                    <div class="stat-icon">
                        <i class="fa-solid fa-check-circle"></i>
                    </div>
                    <div class="stat-content">
                        <h4>{{ number_format($stats['matched']) }}</h4>
                        <p>Matched</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card potential">
                    <div class="stat-icon">
                        <i class="fa-solid fa-exclamation-triangle"></i>
                    </div>
                    <div class="stat-content">
                        <h4>{{ number_format($stats['potential']) }}</h4>
                        <p>Potential Matches</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card unmatched">
                    <div class="stat-icon">
                        <i class="fa-solid fa-times-circle"></i>
                    </div>
                    <div class="stat-content">
                        <h4>{{ number_format($stats['unmatched']) }}</h4>
                        <p>Unmatched</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card total">
                    <div class="stat-icon">
                        <i class="fa-solid fa-database"></i>
                    </div>
                    <div class="stat-content">
                        <h4>{{ number_format($stats['total']) }}</h4>
                        <p>Total Legacy</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Bar -->
    <div class="action-bar mb-3">
        <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex gap-2 align-items-center">
                <!-- Bulk Selection -->
                @if (!empty($selectedItems))
                    <div class="bulk-actions">
                        <span class="badge bg-primary">{{ count($selectedItems) }} selected</span>
                        <button wire:click="bulkLink" class="btn btn-sm btn-success ms-2">
                            <i class="fa-solid fa-link mr-1"></i>
                            Bulk Link
                        </button>
                    </div>
                @endif

                <!-- View Toggle -->
                <button wire:click="$toggle('compactView')"
                    class="btn btn-sm {{ $compactView ? 'btn-primary' : 'btn-outline-primary' }}">
                    <i class="fa-solid fa-{{ $compactView ? 'expand' : 'compress' }} mr-1"></i>
                    {{ $compactView ? 'Expand' : 'Compact' }}
                </button>
            </div>

            <div class="d-flex gap-2">
                <!-- Auto Match -->
                {{-- <button wire:click="runBulkAutoMatch" class="btn btn-sm btn-success mr-3"
                    onclick="return confirm('Run auto-matching for high confidence matches?')">
                    <i class="fa-solid fa-magic mr-1"></i>
                    Auto Match
                </button> --}}
                <select wire:model.live="region" class="select mr-3">
                            <option value="">All Regions</option>
                            @foreach ($regions as $region)
                                <option value="{{ $region }}">{{ $region }}</option>
                            @endforeach
                        </select>

                <!-- Export -->
                <button wire:click="export" class="btn btn-sm btn-outline-primary">
                    <i class="fa-solid fa-download mr-1"></i>
                    Export
                </button>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="table-responsive custom-table">
        <div class="d-none d-md-block">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th width="40">
                            <input type="checkbox" wire:model.live="selectAll" class="form-check-input">
                        </th>
                        <th>ID</th>
                        <th>Name</th>
                        @if (!$compactView)
                            <th>Phone</th>
                            <th>NIDA</th>
                            <th>Location</th>
                        @else
                            <th>Contact & Location</th>
                        @endif
                        <th>Status</th>
                        <th>Match Status</th>
                        {{-- <th width="150">Actions</th> --}}
                    </tr>
                </thead>
                <tbody>
                    @forelse($legacyFundis as $legacy)
                        <tr class="legacy-row {{ $legacy->match_status }}" wire:key="legacy-{{ $legacy->id }}">
                            <td>
                                <input type="checkbox" wire:model.live="selectedItems" value="{{ $legacy->id }}"
                                    class="form-check-input">
                            </td>
                            <td>
                                <span class="badge bg-secondary">#{{ $legacy->id }}</span>
                            </td>
                            <td>
                                <div class="user-info">
                                    <strong>{{ $legacy->name }}</strong>
                                    <small class="d-block text-muted">{{ ucfirst($legacy->gender) }}</small>
                                </div>
                            </td>
                            @if (!$compactView)
                                <td>
                                    <div class="contact-info">
                                        <span>{{ $legacy->primary_phone }}</span>
                                        @if ($legacy->transaction_phone && $legacy->transaction_phone !== $legacy->primary_phone)
                                            <small class="d-block text-muted">{{ $legacy->transaction_phone }}</small>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <span class="text-muted">{{ $legacy->nida_number ?? 'N/A' }}</span>
                                </td>
                                <td>
                                    <div class="location-info">
                                        @if ($legacy->ward)
                                            <span>{{ $legacy->ward }}</span><br>
                                        @endif
                                        @if ($legacy->district)
                                            <small class="text-muted">{{ $legacy->district }}</small><br>
                                        @endif
                                        @if ($legacy->region)
                                            <small class="text-muted">{{ $legacy->region }}</small>
                                        @endif
                                    </div>
                                </td>
                            @else
                                <td>
                                    <div class="compact-info">
                                        <span class="d-block">{{ $legacy->primary_phone }}</span>
                                        @if ($legacy->nida_number)
                                            <small class="text-muted">NIDA: {{ $legacy->nida_number }}</small><br>
                                        @endif
                                        @if ($legacy->region)
                                            <small class="text-muted">{{ $legacy->region }}</small>
                                        @endif
                                    </div>
                                </td>
                            @endif
                            <td>
                                <span
                                    class="badge bg-{{ $legacy->status === 'active' ? 'success' : ($legacy->status === 'pending' ? 'warning' : 'secondary') }}">
                                    {{ ucfirst($legacy->status) }}
                                </span>
                            </td>
                            <td>
                                @if ($legacy->is_matched)
                                    <div class="match-status-display">
                                        <span class="badge bg-success mb-1">
                                            <i class="fa-solid fa-check mr-1"></i>
                                            Matched
                                        </span>
                                        <small class="d-block text-muted">
                                            <a href="{{ route('fundi', $legacy->matchedFundi) }}" target="_blank"
                                                class="text-decoration-none">
                                                Fundi #{{ $legacy->matchedFundi->id }}
                                                <i class="fa-solid fa-external-link fa-xs"></i>
                                            </a>
                                        </small>
                                    </div>
                                @else
                                    @php
                                        $potentialCount = $legacy->getPotentialMatchesCount();
                                    @endphp
                                    @if ($potentialCount > 0)
                                        <span class="badge bg-warning">
                                            <i class="fa-solid fa-exclamation-triangle mr-1"></i>
                                            {{ $potentialCount }} Potential
                                        </span>
                                    @else
                                        <span class="badge bg-danger">
                                            <i class="fa-solid fa-times mr-1"></i>
                                            No Matches
                                        </span>
                                    @endif
                                @endif
                            </td>
                            {{-- <td>
                            <div class="action-buttons">
                                @if ($legacy->is_matched)
                                    <button wire:click="unlinkFundi({{ $legacy->id }})"
                                        class="btn btn-sm btn-outline-danger"
                                        onclick="return confirm('Unlink this legacy fundi?')" title="Unlink">
                                        <i class="fa-solid fa-unlink"></i>
                                    </button>
                                @else
                                    @if ($legacy->getPotentialMatchesCount() > 0)
                                        <button wire:click="showMatches({{ $legacy->id }})"
                                            class="btn btn-sm btn-outline-primary" title="View Matches">
                                            <i class="fa-solid fa-search mr-1"></i>
                                            View
                                        </button>
                                    @else
                                        <button class="btn btn-sm btn-outline-secondary" disabled
                                            title="No matches found">
                                            <i class="fa-solid fa-times"></i>
                                        </button>
                                    @endif
                                @endif
                            </div>
                        </td> --}}
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $compactView ? 7 : 9 }}" class="text-center py-4">
                                <div class="empty-state">
                                    <i class="fa-solid fa-database fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">No legacy fundis found</h5>
                                    <p class="text-muted">Try adjusting your search criteria or import some data.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <div class="pagination-wrapper">
        {{ $legacyFundis->links() }}
    </div>

    <!-- Potential Matches Modal -->
    @if ($showMatchesModal)
        <div class="modal fade show d-block" style="background-color: rgba(0,0,0,0.5);"
            wire:click.self="closeMatches">
            <div class="modal-dialog modal-xl" wire:click.stop>
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fa-solid fa-search mr-2"></i>
                            Potential Matches for Legacy Fundi #{{ $selectedLegacyId }}
                        </h5>
                        <button type="button" class="btn-close" wire:click="closeMatches"></button>
                    </div>
                    <div class="modal-body">
                        @if ($loadingMatches)
                            <div class="text-center py-4">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="mt-2">Finding potential matches...</p>
                            </div>
                        @elseif(!empty($potentialMatches))
                            <div class="matches-grid">
                                @foreach ($potentialMatches as $match)
                                    @php
                                        $fundi = $match['fundi'];
                                        $confidence = $match['confidence'];
                                        $matchType = $match['match_type'];
                                        $details = $match['details'];
                                    @endphp
                                    <div class="match-card mb-3">
                                        <div class="card">
                                            <div class="card-header d-flex justify-content-between align-items-center">
                                                <div class="match-info">
                                                    <h6 class="mb-1">
                                                        <a href="{{ route('fundi', $fundi) }}" target="_blank"
                                                            class="text-decoration-none">
                                                            {{ $fundi->full_name }}
                                                            <i class="fa-solid fa-external-link fa-xs ms-1"></i>
                                                        </a>
                                                    </h6>
                                                    <div class="match-badges">
                                                        <span
                                                            class="badge bg-{{ $confidence >= 90 ? 'success' : ($confidence >= 70 ? 'warning' : 'info') }}">
                                                            {{ $confidence }}% confidence
                                                        </span>
                                                        <span class="badge bg-secondary">{{ ucfirst($matchType) }}
                                                            match</span>
                                                    </div>
                                                </div>
                                                <button
                                                    wire:click="linkFundis({{ $selectedLegacyId }}, {{ $fundi->id }})"
                                                    class="btn btn-success btn-sm"
                                                    onclick="return confirm('Link this legacy fundi with {{ $fundi->full_name }}?')">
                                                    <i class="fa-solid fa-link mr-1"></i>
                                                    Link
                                                </button>
                                            </div>
                                            <div class="card-body">
                                                <p class="text-muted mb-2">{{ $details }}</p>
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <strong>Contact Information:</strong>
                                                        <ul class="list-unstyled mb-0">
                                                            <li>Primary: {{ $fundi->primary_phone }}</li>
                                                            @if ($fundi->transaction_phone)
                                                                <li>Transaction: {{ $fundi->transaction_phone }}</li>
                                                            @endif
                                                            @if ($fundi->nida_number)
                                                                <li>NIDA: {{ $fundi->nida_number }}</li>
                                                            @endif
                                                        </ul>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <strong>Location:</strong>
                                                        <ul class="list-unstyled mb-0">
                                                            <li>Ward: {{ $fundi->ward->name ?? 'N/A' }}</li>
                                                            <li>District: {{ $fundi->ward->district->name ?? 'N/A' }}
                                                            </li>
                                                            <li>Region:
                                                                {{ $fundi->ward->district->region->name ?? 'N/A' }}
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-4">
                                <i class="fa-solid fa-search fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No potential matches found</h5>
                                <p class="text-muted">This legacy fundi doesn't have any potential matches in the
                                    current database.</p>
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="closeMatches">Close</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Loading Overlay -->
    <div wire:loading.delay.long class="loading-overlay">
        <div class="loading-content">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Processing...</p>
        </div>
    </div>

    <!-- Toast Container -->
    <div id="toast-container" class="position-fixed top-0 end-0 p-3" style="z-index: 1055;"></div>
</div>

@push('scripts')
    <script>
        document.addEventListener('livewire:initialized', () => {
            // Handle toast notifications
            Livewire.on('showToast', (data) => {
                showToast(data.type, data.message);
            });

            function showToast(type, message) {
                const toastContainer = document.getElementById('toast-container');
                const toastId = 'toast-' + Date.now();

                const toastHtml = `
            <div id="${toastId}" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header">
                    <i class="fa-solid fa-${getToastIcon(type)} text-${type} mr-2"></i>
                    <strong class="mr-auto">${getToastTitle(type)}</strong>
                    <button type="button" class="btn-close" data-dismiss="toast"></button>
                </div>
                <div class="toast-body">
                    ${message}
                </div>
            </div>
        `;

                toastContainer.insertAdjacentHTML('beforeend', toastHtml);

                const toastElement = document.getElementById(toastId);

                // Use Bootstrap 4 toast initialization since you're using Bootstrap 4
                $(toastElement).toast({
                    autohide: true,
                    delay: type === 'error' ? 5000 : 3000
                });

                $(toastElement).toast('show');

                // Remove toast element after it's hidden
                $(toastElement).on('hidden.bs.toast', function() {
                    $(this).remove();
                });
            }

            function getToastIcon(type) {
                const icons = {
                    'success': 'check-circle',
                    'error': 'times-circle',
                    'warning': 'exclamation-triangle',
                    'info': 'info-circle'
                };
                return icons[type] || 'info-circle';
            }

            function getToastTitle(type) {
                const titles = {
                    'success': 'Success',
                    'error': 'Error',
                    'warning': 'Warning',
                    'info': 'Information'
                };
                return titles[type] || 'Notification';
            }
        });
    </script>
@endpush

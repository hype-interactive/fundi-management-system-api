<div class="legacy-fundis-wrapper">
    <!-- Filters Section -->
    <div class="filter mb-4">
        <div class="row">
            <div class="col-md-3">
                <div class="search">
                    <input wire:model.live.debounce.300ms="search_key" type="text" placeholder="Search name, phone, NIDA...">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </div>
            </div>
            <div class="col-md-9">
                <div class="row">
                    <div class="col">
                        <select wire:model.live="region" class="form-select">
                            <option value="">All Regions</option>
                            @foreach($regions as $region)
                                <option value="{{ $region }}">{{ $region }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col">
                        <select wire:model.live="district" class="form-select">
                            <option value="">All Districts</option>
                            @foreach($districts as $district)
                                <option value="{{ $district }}">{{ $district }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col">
                        <select wire:model.live="ward" class="form-select">
                            <option value="">All Wards</option>
                            @foreach($wards as $ward)
                                <option value="{{ $ward }}">{{ $ward }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col">
                        <select wire:model.live="match_status" class="form-select">
                            <option value="">All Match Status</option>
                            @foreach($match_status_options as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Additional Filters Row -->
        <div class="row mt-2">
            <div class="col-md-3">
                <select wire:model.live="gender" class="form-select">
                    <option value="">All Genders</option>
                    @foreach($gender_options as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select wire:model.live="ranking" class="form-select">
                    <option value="">All Rankings</option>
                    @foreach($ranking_options as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select wire:model.live="status" class="form-select">
                    <option value="">All Status</option>
                    @foreach($status_options as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <!-- Empty for balance -->
            </div>
        </div>
    </div>

    <!-- Actions Bar -->
    <div class="actions-bar mb-3 d-flex justify-content-between align-items-center">
        <div class="d-flex gap-2">
            <button wire:click="$toggle('compactView')"
                class="btn btn-sm {{ $compactView ? 'btn-primary' : 'btn-outline-primary' }}">
                <i class="fa-solid fa-{{ $compactView ? 'expand' : 'compress' }}"></i>
                {{ $compactView ? 'Expand' : 'Compact' }}
            </button>

            <button wire:click="runAutoMatch"
                class="btn btn-sm btn-success"
                onclick="return confirm('Run auto-matching for high confidence matches? This cannot be undone.')">
                <i class="fa-solid fa-magic me-1"></i>
                Auto Match
            </button>
        </div>

        <div class="d-flex gap-2">
            <button wire:click="refresh" class="btn btn-outline-secondary btn-sm">
                <i class="fa-solid fa-arrows-rotate me-1"></i>
                Clear Filters
            </button>
            <button wire:click="exportAnalysis" class="btn btn-outline-success btn-sm">
                <i class="fa-solid fa-file-excel me-1"></i>
                Export Analysis
            </button>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="stats-cards mb-4">
        <div class="row">
            <div class="col-md-3">
                <div class="stat-card matched">
                    <div class="stat-icon">
                        <i class="fa-solid fa-check-circle"></i>
                    </div>
                    <div class="stat-content">
                        <h3>{{ App\Models\LegacyFundi::whereIn('id', function($q) { $q->select('legacy_id')->from('fundis')->whereNotNull('legacy_id'); })->count() }}</h3>
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
                        <h3>{{ App\Models\LegacyFundi::whereNotIn('id', function($q) { $q->select('legacy_id')->from('fundis')->whereNotNull('legacy_id'); })->get()->filter(function($l) { return $l->findPotentialMatches()->count() > 0; })->count() }}</h3>
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
                        <h3>{{ App\Models\LegacyFundi::whereNotIn('id', function($q) { $q->select('legacy_id')->from('fundis')->whereNotNull('legacy_id'); })->get()->filter(function($l) { return $l->findPotentialMatches()->count() == 0; })->count() }}</h3>
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
                        <h3>{{ App\Models\LegacyFundi::count() }}</h3>
                        <p>Total Legacy</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Table Container -->
    <div class="custom-table">
        <!-- Desktop Table -->
        <div class="d-none d-md-block">
            <table class="table">
                <thead class="thead-light">
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">Name</th>
                        @if (!$compactView)
                            <th scope="col">Phone</th>
                            <th scope="col">NIDA</th>
                            <th scope="col">Location</th>
                        @else
                            <th scope="col">Contact & Location</th>
                        @endif
                        <th scope="col">Ranking</th>
                        <th scope="col">Match Status</th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($legacyFundis as $legacy)
                        <tr class="legacy-row {{ $legacy->match_status }}">
                            <td>
                                <span class="legacy-id">#{{ $legacy->id }}</span>
                            </td>
                            <td>
                                <div class="legacy-name">
                                    <span class="fw-semibold">{{ $legacy->name }}</span>
                                    <small class="text-muted d-block">{{ ucfirst($legacy->gender) }}</small>
                                </div>
                            </td>
                            @if (!$compactView)
                                <td>
                                    <div class="phone-info">
                                        <span class="phone-number">{{ $legacy->primary_phone }}</span>
                                        @if ($legacy->transaction_phone && $legacy->transaction_phone !== $legacy->primary_phone)
                                            <small class="text-muted d-block">{{ $legacy->transaction_phone }}</small>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <span class="nida-number">{{ $legacy->nida_number ?? 'N/A' }}</span>
                                </td>
                                <td>
                                    <div class="location-info">
                                        @if ($legacy->ward)
                                            <span class="fw-semibold">{{ $legacy->ward }}</span>
                                        @endif
                                        @if ($legacy->district)
                                            <small class="text-muted d-block">{{ $legacy->district }}</small>
                                        @endif
                                        @if ($legacy->region)
                                            <small class="text-muted d-block">{{ $legacy->region }}</small>
                                        @endif
                                    </div>
                                </td>
                            @else
                                <td>
                                    <div class="compact-info">
                                        <div class="phone-number">{{ $legacy->primary_phone }}</div>
                                        @if ($legacy->nida_number)
                                            <small class="text-muted">NIDA: {{ $legacy->nida_number }}</small>
                                        @endif
                                        @if ($legacy->ward)
                                            <small class="text-muted d-block">{{ $legacy->ward }}</small>
                                        @endif
                                    </div>
                                </td>
                            @endif
                            <td>
                                <span class="badge ranking-badge bg-{{ $legacy->ranking === 'fundi_kuu' ? 'primary' : 'secondary' }}">
                                    {{ $ranking_options[$legacy->ranking] ?? 'N/A' }}
                                </span>
                            </td>
                            <td>
                                @if ($legacy->is_matched)
                                    <div class="match-status matched">
                                        <span class="badge bg-success">
                                            <i class="fa-solid fa-check me-1"></i>
                                            Matched
                                        </span>
                                        <small class="text-muted d-block">
                                            Fundi #{{ $legacy->matchedFundi->id }}
                                        </small>
                                    </div>
                                @else
                                    @php
                                        $potentialCount = $legacy->findPotentialMatches()->count();
                                    @endphp
                                    @if ($potentialCount > 0)
                                        <span class="badge bg-warning">
                                            <i class="fa-solid fa-exclamation-triangle me-1"></i>
                                            {{ $potentialCount }} Potential
                                        </span>
                                    @else
                                        <span class="badge bg-danger">
                                            <i class="fa-solid fa-times me-1"></i>
                                            No Matches
                                        </span>
                                    @endif
                                @endif
                            </td>
                            <td>
                                <div class="action-buttons">
                                    @if ($legacy->is_matched)
                                        <button wire:click="unlinkFundi({{ $legacy->id }})"
                                            class="btn btn-sm btn-outline-danger"
                                            onclick="return confirm('Unlink this legacy fundi?')">
                                            <i class="fa-solid fa-unlink"></i>
                                        </button>
                                    @else
                                        @if ($legacy->findPotentialMatches()->count() > 0)
                                            <button wire:click="showMatches({{ $legacy->id }})"
                                                class="btn btn-sm btn-outline-primary">
                                                <i class="fa-solid fa-search"></i>
                                                View Matches
                                            </button>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                <i class="fa-solid fa-database me-2"></i>
                                No legacy fundis found matching your criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Mobile Cards -->
        <div class="mobile-cards d-md-none">
            @forelse ($legacyFundis as $legacy)
                <div class="legacy-card {{ $legacy->match_status }}" wire:key="mobile-{{ $legacy->id }}">
                    <div class="card-header">
                        <div class="legacy-info">
                            <h6 class="legacy-name">#{{ $legacy->id }} - {{ $legacy->name }}</h6>
                            <div class="legacy-meta">
                                <span class="badge ranking-badge bg-{{ $legacy->ranking === 'fundi_kuu' ? 'primary' : 'secondary' }}">
                                    {{ $ranking_options[$legacy->ranking] ?? 'N/A' }}
                                </span>

                                @if ($legacy->is_matched)
                                    <span class="badge bg-success">
                                        <i class="fa-solid fa-check me-1"></i>
                                        Matched
                                    </span>
                                @else
                                    @php
                                        $potentialCount = $legacy->findPotentialMatches()->count();
                                    @endphp
                                    @if ($potentialCount > 0)
                                        <span class="badge bg-warning">
                                            <i class="fa-solid fa-exclamation-triangle me-1"></i>
                                            {{ $potentialCount }} Potential
                                        </span>
                                    @else
                                        <span class="badge bg-danger">
                                            <i class="fa-solid fa-times me-1"></i>
                                            No Matches
                                        </span>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="contact-row">
                            <i class="fa-solid fa-phone me-2 text-muted"></i>
                            <span>{{ $legacy->primary_phone }}</span>
                        </div>
                        @if ($legacy->transaction_phone && $legacy->transaction_phone !== $legacy->primary_phone)
                            <div class="contact-row">
                                <i class="fa-solid fa-mobile-alt me-2 text-muted"></i>
                                <span>{{ $legacy->transaction_phone }}</span>
                            </div>
                        @endif
                        @if ($legacy->nida_number)
                            <div class="contact-row">
                                <i class="fa-solid fa-id-card me-2 text-muted"></i>
                                <span>{{ $legacy->nida_number }}</span>
                            </div>
                        @endif
                        @if ($legacy->ward || $legacy->district || $legacy->region)
                            <div class="location-row">
                                <i class="fa-solid fa-map-marker-alt me-2 text-muted"></i>
                                <span>
                                    @if ($legacy->ward){{ $legacy->ward }}@endif
                                    @if ($legacy->district), {{ $legacy->district }}@endif
                                    @if ($legacy->region), {{ $legacy->region }}@endif
                                </span>
                            </div>
                        @endif

                        <div class="action-buttons mt-3">
                            @if ($legacy->is_matched)
                                <button wire:click="unlinkFundi({{ $legacy->id }})"
                                    class="btn btn-sm btn-outline-danger w-100"
                                    onclick="return confirm('Unlink this legacy fundi?')">
                                    <i class="fa-solid fa-unlink me-1"></i>
                                    Unlink
                                </button>
                                <small class="text-muted d-block text-center mt-1">
                                    Linked to Fundi #{{ $legacy->matchedFundi->id }}
                                </small>
                            @else
                                @if ($legacy->findPotentialMatches()->count() > 0)
                                    <button wire:click="showMatches({{ $legacy->id }})"
                                        class="btn btn-sm btn-outline-primary w-100">
                                        <i class="fa-solid fa-search me-1"></i>
                                        View Potential Matches
                                    </button>
                                @else
                                    <button class="btn btn-sm btn-outline-secondary w-100" disabled>
                                        <i class="fa-solid fa-times me-1"></i>
                                        No Matches Found
                                    </button>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="empty-state text-center py-5">
                    <i class="fa-solid fa-database fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No legacy fundis found</h5>
                    <p class="text-muted">Try adjusting your search criteria.</p>
                </div>
            @endforelse
        </div>
    </div>

    <!-- Pagination -->
    <div class="pagination-wrapper d-flex justify-content-center mt-4">
        {{ $legacyFundis->links() }}
    </div>

    <!-- Potential Matches Modal -->
    @if ($showMatches)
        <div class="modal fade show d-block" style="background-color: rgba(0,0,0,0.5);" wire:click="closeMatches">
            <div class="modal-dialog modal-lg" wire:click.stop>
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fa-solid fa-search me-2"></i>
                            Potential Matches for Legacy Fundi #{{ $selectedLegacyId }}
                        </h5>
                        <button type="button" class="btn-close" wire:click="closeMatches"></button>
                    </div>
                    <div class="modal-body">
                        @if (count($potentialMatches) > 0)
                            <div class="matches-list">
                                @foreach ($potentialMatches as $match)
                                    @php
                                        $fundi = $match['fundi'];
                                        $matchType = $match['match_type'];
                                        $confidence = $match['confidence'];
                                        $details = $match['details'];
                                    @endphp
                                    <div class="match-item">
                                        <div class="match-header">
                                            <div class="match-info">
                                                <h6 class="mb-1">
                                                    <a href="{{ route('fundi', $fundi) }}" target="_blank" class="text-decoration-none">
                                                        {{ $fundi->full_name }}
                                                        <i class="fa-solid fa-external-link fa-xs ms-1"></i>
                                                    </a>
                                                </h6>
                                                <div class="match-meta">
                                                    <span class="badge bg-{{ $confidence >= 90 ? 'success' : ($confidence >= 70 ? 'warning' : 'info') }}">
                                                        {{ $confidence }}% confidence
                                                    </span>
                                                    <span class="badge bg-secondary">{{ ucfirst($matchType) }} match</span>
                                                    <span class="match-details text-muted">{{ $details }}</span>
                                                </div>
                                            </div>
                                            <div class="match-actions">
                                                <button wire:click="linkFundis({{ $selectedLegacyId }}, {{ $fundi->id }})"
                                                    class="btn btn-sm btn-success"
                                                    onclick="return confirm('Link this legacy fundi with {{ $fundi->full_name }}?')">
                                                    <i class="fa-solid fa-link me-1"></i>
                                                    Link
                                                </button>
                                            </div>
                                        </div>
                                        <div class="match-details-row">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <strong>Contact:</strong>
                                                    <div>Primary: {{ $fundi->primary_phone }}</div>
                                                    @if ($fundi->transaction_phone)
                                                        <div>Transaction: {{ $fundi->transaction_phone }}</div>
                                                    @endif
                                                    @if ($fundi->nida_number)
                                                        <div>NIDA: {{ $fundi->nida_number }}</div>
                                                    @endif
                                                </div>
                                                <div class="col-md-6">
                                                    <strong>Location:</strong>
                                                    <div>{{ $fundi->ward->name ?? 'N/A' }}</div>
                                                    <div>{{ $fundi->ward->district->name ?? 'N/A' }}</div>
                                                    <div>{{ $fundi->ward->district->region->name ?? 'N/A' }}</div>
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
                                <p class="text-muted">This legacy fundi doesn't have any potential matches in the current database.</p>
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

    <!-- Loading States -->
    <div wire:loading.delay.longer class="loading-overlay">
        <div class="loading-content">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Processing legacy data...</p>
        </div>
    </div>
</div>

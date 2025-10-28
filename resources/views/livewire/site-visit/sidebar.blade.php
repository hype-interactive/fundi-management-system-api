<div class="site-visit-sidebar">
    @if (session()->has('message'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('message') }}
            <button type="button" class="btn-close" data-dismiss="alert"></button>
        </div>
    @endif

    <!-- Quick Stats Card -->
    <div class="stats-card">
        <h5 class="card-title">
            <i class="fa-solid fa-chart-simple mr-2"></i>
            Quick Stats
        </h5>
        <div class="stats-grid">
            <div class="stat-item">
                <div class="stat-number">{{ $siteVisit->photos->count() }}</div>
                <div class="stat-label">Photos</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">{{ $siteVisit->products->count() }}</div>
                <div class="stat-label">Products</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">{{ $siteVisit->fundis->count() }}</div>
                <div class="stat-label">Mafundi</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">
                    @if ($siteVisit->gps_latitude && $siteVisit->gps_longitude)
                        <i class="fa-solid fa-check text-success"></i>
                    @else
                        <i class="fa-solid fa-times text-danger"></i>
                    @endif
                </div>
                <div class="stat-label">GPS</div>
            </div>
        </div>
    </div>

    <!-- Actions Card -->
    <div class="actions-card">
        <h5 class="card-title">
            <i class="fa-solid fa-cogs mr-2"></i>
            Actions
        </h5>
        <div class="action-buttons">
            @if ($siteVisit->status === 'pending')
                <button class="btn btn-success btn-sm" wire:click="confirmApprove">
                    <i class="fa-solid fa-check me-1"></i>
                    Approve Visit
                </button>
                <button class="btn btn-danger btn-sm" wire:click="confirmReject">
                    <i class="fa-solid fa-times me-1"></i>
                    Reject Visit
                </button>
            @endif

            {{-- <a href="{{ route('site-visit.edit', $siteVisit) }}" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-edit me-1"></i>
                Edit Visit
            </a> --}}

            {{-- <button class="btn btn-info btn-sm" wire:click="exportVisit">
                <i class="fa-solid fa-download me-1"></i>
                Export PDF
            </button> --}}

            <button
                class="btn btn-info btn-sm"
                wire:click="exportVisit"
                wire:loading.attr="disabled"
                wire:target="exportVisit"
            >
                <span wire:loading.remove wire:target="exportVisit">
                    <i class="fa-solid fa-download me-1"></i>
                    Export PDF
                </span>
                <span wire:loading wire:target="exportVisit">
                    <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                    Generating PDF...
                </span>
            </button>

            <button class="btn btn-danger btn-sm" wire:click="confirmDelete">
                <i class="fa-solid fa-trash me-1"></i>
                Delete Visit
            </button>
        </div>
    </div>

    <!-- Timeline Card -->
    <div class="timeline-card">
        <h5 class="card-title">
            <i class="fa-solid fa-clock mr-2"></i>
            Timeline
        </h5>
        <div class="timeline">
            <div class="timeline-item">
                <div class="timeline-marker"></div>
                <div class="timeline-content">
                    <h6>Visit Created</h6>
                    <small class="text-muted">{{ $siteVisit->created_at->format('M d, Y g:i A') }}</small>
                </div>
            </div>

            @if ($siteVisit->approved_at)
                <div class="timeline-item">
                    <div class="timeline-marker bg-success"></div>
                    <div class="timeline-content">
                        <h6>Visit Approved</h6>
                        <small class="text-muted">{{ $siteVisit->approved_at->format('M d, Y g:i A') }}</small>
                        <div class="text-muted">by {{ $siteVisit->approver->full_name }}</div>
                    </div>
                </div>
            @endif

            @if ($siteVisit->status === 'rejected')
                <div class="timeline-item">
                    <div class="timeline-marker bg-danger"></div>
                    <div class="timeline-content">
                        <h6>Visit Rejected</h6>
                        <small class="text-muted">{{ $siteVisit->updated_at->format('M d, Y g:i A') }}</small>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <livewire:components.confirmation-modal />
</div>
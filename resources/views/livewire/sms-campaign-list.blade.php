<div class="sms-campaigns-wrapper">
    <!-- Campaign Actions & Filters -->
    <div class="filter my-3">
        <div class="row">
            <div class="col-md-3">
                <div class="search">
                    <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search campaigns...">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </div>
            </div>
            <div class="col-md-9">
                <div class="row">
                    <div class="col-lg-3 col-md-4 col-6">
                        <div class="select">
                            <select wire:model.live="status" class="form-control">
                                <option value="">All Status</option>
                                <option value="draft">Draft</option>
                                <option value="scheduled">Scheduled</option>
                                <option value="sending">Sending</option>
                                <option value="sent">Sent</option>
                                <option value="failed">Failed</option>
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
            <button wire:click="refresh" class="btn btn-outline-secondary btn-sm">
                <i class="fa-solid fa-arrows-rotate me-1"></i>
                Clear Filters
            </button>
        </div>
        <div class="d-flex gap-2">
            {{-- <button wire:click="exportToExcel" class="btn btn-outline-success btn-sm">
                <i class="fa-solid fa-file-excel me-1"></i>
                Export Excel
            </button>
            <button wire:click="downloadOperatorPDF" class="btn btn-outline-danger btn-sm">
                <i class="fa-solid fa-file-pdf me-1"></i>
                Download PDF
            </button> --}}
            <button wire:click="refresh" class="btn btn-primary btn-sm" type="button" data-toggle="modal"
                data-target="#campaignComposeModal">
                <i class="fa-solid fa-plus me-1"></i>
                Create New Campaign
            </button>
        </div>
    </div>

    <!-- Campaigns List -->
    <div class="campaigns-section">
        <!-- Desktop Table -->
        <div class="d-none d-md-block">
            <div class="custom-table">
                <table class="table">
                    <thead class="thead-light">
                        <tr>
                            <th>Campaign</th>
                            <th>Recipients</th>
                            <th>Status</th>
                            <th>Scheduled</th>
                            <th>Sent</th>
                            {{-- <th>Actions</th> --}}
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($campaigns as $campaign)
                            <tr wire:key="campaign-{{ $campaign->id }}">
                                <td>
                                    <div class="campaign-info">
                                        <h6 class="mb-1">{{ $campaign->title }}</h6>
                                        <small class="text-muted">{{ Str::limit($campaign->message, 60) }}</small>
                                    </div>
                                </td>
                                <td>
                                    <span class="text-info">{{ ucfirst($campaign->recipient_type) }}</span>
                                </td>
                                <td>
                                    @if ($campaign->status === 'completed')
                                        <span class="text-success">Sent</span>
                                    @elseif($campaign->status === 'processing')
                                        <span class="text-warning">Processing</span>
                                    @elseif($campaign->status === 'scheduled')
                                        <span class="text-primary">Scheduled</span>
                                    @elseif($campaign->status === 'cancelled')
                                        <span class="text-danger">Failed</span>
                                    @else
                                        <span class="text-secondary">{{ ucfirst($campaign->status) }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($campaign->scheduled_at)
                                        {{ $campaign->scheduled_at->format('M d, Y H:i') }}
                                    @else
                                        <span class="text-muted">Immediate</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($campaign->sent_at)
                                        {{ $campaign->sent_at->format('M d, Y H:i') }}
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                {{-- <td>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-primary btn-sm" title="View Details">
                                            <i class="fa-solid fa-eye"></i>
                                        </button>
                                        @if ($campaign->status !== 'sent' && $campaign->status !== 'sending')
                                            <button wire:click="deleteCampaign({{ $campaign->id }})"
                                                class="btn btn-outline-danger btn-sm" title="Delete"
                                                onclick="return confirm('Are you sure?')">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td> --}}
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    <i class="fa-solid fa-envelope me-2"></i>
                                    No campaigns found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Mobile Cards -->
        <div class="mobile-cards d-md-none">
            @forelse($campaigns as $campaign)
                <div class="campaign-card mb-3" wire:key="mobile-{{ $campaign->id }}">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">{{ $campaign->title }}</h6>
                            @if ($campaign->status === 'sent')
                                <span class="badge bg-success">Sent</span>
                            @elseif($campaign->status === 'sending')
                                <span class="badge bg-warning">Sending</span>
                            @elseif($campaign->status === 'scheduled')
                                <span class="badge bg-primary">Scheduled</span>
                            @elseif($campaign->status === 'failed')
                                <span class="badge bg-danger">Failed</span>
                            @else
                                <span class="badge bg-secondary">{{ ucfirst($campaign->status) }}</span>
                            @endif
                        </div>
                        <div class="card-body">
                            <p class="card-text">{{ Str::limit($campaign->message, 100) }}</p>
                            <div class="campaign-meta">
                                <small class="text-muted d-block">
                                    <i class="fa-solid fa-users me-1"></i>
                                    Recipients: {{ ucfirst($campaign->recipient_type) }}
                                </small>
                                @if ($campaign->scheduled_at)
                                    <small class="text-muted d-block">
                                        <i class="fa-solid fa-clock me-1"></i>
                                        Scheduled: {{ $campaign->scheduled_at->format('M d, Y H:i') }}
                                    </small>
                                @endif
                                @if ($campaign->sent_at)
                                    <small class="text-muted d-block">
                                        <i class="fa-solid fa-check me-1"></i>
                                        Sent: {{ $campaign->sent_at->format('M d, Y H:i') }}
                                    </small>
                                @endif
                            </div>
                        </div>
                        {{-- <div class="card-footer d-flex justify-content-end gap-2">
                            <button class="btn btn-outline-primary btn-sm">
                                <i class="fa-solid fa-eye me-1"></i>
                                View
                            </button>
                            @if ($campaign->status !== 'sent' && $campaign->status !== 'sending')
                                <button wire:click="deleteCampaign({{ $campaign->id }})"
                                    class="btn btn-outline-danger btn-sm" onclick="return confirm('Are you sure?')">
                                    <i class="fa-solid fa-trash me-1"></i>
                                    Delete
                                </button>
                            @endif
                        </div> --}}
                    </div>
                </div>
            @empty
                <div class="empty-state text-center py-5">
                    <i class="fa-solid fa-envelope fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No campaigns found</h5>
                    <p class="text-muted">Create your first SMS campaign to get started.</p>
                    <button wire:click="openCampaignModal" class="btn btn-primary">
                        <i class="fa-solid fa-plus me-1"></i>
                        Create Campaign
                    </button>
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        <div class="pagination-wrapper d-flex justify-content-center mt-3">
            {{ $campaigns->links() }}
        </div>
    </div>

    <!-- Campaign Compose Modal -->
    @include('components.modals.campaign-compose-modal')
</div>

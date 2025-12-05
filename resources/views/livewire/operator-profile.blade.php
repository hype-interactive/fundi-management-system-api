<div wire:poll.keep-live class="section-right profile">
    <div class="avatar-wrapper mb-4">
        <div class="light-border">
            <div class="avatar">
                @if ($operator->profile_photo_public_id)
                    <x-cloudinary::image
                        public-id="{{ $operator->profile_photo_public_id }}"
                        width="80"
                        height="80"
                        crop="fill"
                        quality="auto"
                        fetch-format="auto"
                        class="rounded-full object-cover"
                        alt="{{ $operator->fname }} {{ $operator->lname }}"
                    />
                @else
                    <img src="{{ asset('images/avatar.png') }}" alt="Default Avatar" class="rounded-full">
                @endif
            </div>
        </div>
    </div>
    <h6>Operator's Profile</h6>
    <div class="operator-info">
        <div class="row">
            <div class="col-md-12">
                <div class="title">Personal Information</div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-5">
                <div class="label">Full Name</div>
            </div>
            <div class="col-md-7">
                {{ $operator->full_name }}
            </div>
        </div>

        <div class="row">
            <div class="col-md-5">
                <div class="label">Email</div>
            </div>
            <div class="col-md-7">
                {{ $operator->email }}
            </div>
        </div>

        <div class="row">
            <div class="col-md-5">
                <div class="label">Phone</div>
            </div>
            <div class="col-md-7">
                {{ $operator->phone ?: 'Not provided' }}
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-12">
                <div class="title">Role & Access</div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-5">
                <div class="label">Role</div>
            </div>
            <div class="col-md-7">
                <span class="text-primary">{{ $operator->role?->name ?? 'No Role Assigned' }}</span>
            </div>
        </div>

        <div class="row">
            <div class="col-md-5">
                <div class="label">Status</div>
            </div>
            <div class="col-md-7">
                <span class="{{ $operator->status === 'active' ? 'text-success' : 'text-warning' }}">
                    {{ ucfirst($operator->status) }}
                </span>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-12">
                <div class="title">Fundi Registration Summary</div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-5">
                <div class="label">Today</div>
            </div>

            <div class="col-md-7">
                {{ $this->getFundiRegistrationSummary('today') }}
            </div>
        </div>
        <div class="row">
            <div class="col-md-5">
                <div class="label">This Week</div>
            </div>
            
            <div class="col-md-7">
                {{ $this->getFundiRegistrationSummary('week') }}
            </div>
        </div>
        <div class="row">
            <div class="col-md-5">
                <div class="label">This Month</div>
            </div>
            
            <div class="col-md-7">
                {{ $this->getFundiRegistrationSummary('month') }}
            </div>
        </div>
        <div class="row">
            <div class="col-md-5">
                <div class="label">All Time</div>
            </div>
            
            <div class="col-md-7">
                {{ $this->getFundiRegistrationSummary('all_time') }}
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-12">
                <div class="title">Site Visit Summary</div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-5">
                <div class="label">Total Site Visits</div>
            </div>
            <div class="col-md-7">
                {{ $this->getTotalSiteVisitsAttribute() }}
            </div>
        </div>

        <div class="row">
            <div class="col-md-5">
                <div class="label">Completed Visits</div>
            </div>
            <div class="col-md-7">
                {{ $this->getCompletedSiteVisitsAttribute() }}
            </div>
        </div>
        {{-- <div class="row">
            <div class="col-md-5">
                <div class="label">Total Incentives</div>
            </div>
            <div class="col-md-7">
                TZS {{ number_format($this->getTotalIncentivesAttribute()) }}
            </div>
        </div> --}}

    <div class="row mt-4">
        <div class="col-md-12">
            <div class="title">Assigned Districts</div>
        </div>
    </div>

    @if(count($districts) > 0)
        <div class="row">
            <div class="col-md-12">
                <div class="d-flex flex-wrap gap-2">
                    @foreach($districts as $district)
                        <span class="badge bg-secondary m-2">{{ $district->name }}</span>
                    @endforeach
                </div>
            </div>
        </div>
    @else
        <div class="row">
            <div class="col-md-12">
                <small class="text-muted">No districts assigned</small>
            </div>
        </div>
    @endif
        
    </div>

    <!-- Actions Section -->
    @if (Auth::user()->isRoot() || Auth::user()->role->name === 'admin')
        <div class="actions-card mt-4">
            <h6 class="card-title">
                <i class="fa-solid fa-cogs mr-2"></i>
                Actions
            </h6>
            <div class="action-buttons">
                @if($operator->status !== 'active')
                    <button class="btn btn-success btn-sm" wire:click="confirmActivate">
                        <i class="fa-solid fa-check me-1"></i>
                        Activate Operator
                    </button>
                @endif
                @if($operator->status === 'active')
                    <button class="btn btn-warning btn-sm" wire:click="confirmDeactivate">
                        <i class="fa-solid fa-pause me-1"></i>
                        Deactivate Operator
                    </button>
                @endif
            </div>
        </div>
    @endif

    <!-- Recent Site Visits Section -->
    @if (count($siteVisits) > 0)
        <div class="site-visits mt-4">
            <h6>Recent Site Visits</h6>
            @foreach ($siteVisits as $visit)
                <div class="visit-item">
                    <div class="site-name">{{ $visit->site_name ?? 'Unknown Site' }}</div>
                    <div class="date">{{ $visit->visit_date?->format('d M, Y') ?? $visit->created_at->format('d M, Y') }}</div>
                    <div class="row mt-1">
                        <div class="col-6">
                            <small>{{ $visit->purpose ?? 'Site Visit' }}</small>
                        </div>
                        <div class="col-6 text-end">
                            <span class="status {{ $visit->status == 'completed' ? 'completed' : 'pending' }}">
                                {{ ucfirst($visit->status) }}
                            </span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <!-- Recent Incentives Section -->
    @if (count($incentives) > 0)
        <div class="incentives-history mt-4">
            <h6>Recent Incentives</h6>
            @foreach ($incentives as $incentive)
                <div class="tile-special">
                    <div class="row">
                        <div class="col-6 text-start">
                            <div>TZS {{ number_format($incentive->amount) }}</div>
                            <small class="text-muted">{{ $incentive->created_at->format('d M, Y') }}</small>
                        </div>
                        <div class="col-6 text-end">
                            <span class="badge {{ $incentive->status == 'paid' ? 'bg-success' : ($incentive->status == 'approved' ? 'bg-info' : 'bg-warning') }}">
                                {{ ucfirst($incentive->status) }}
                            </span>
                            <div>
                                <i class="fa-solid fa-money-bill-wave"></i>
                            </div>
                        </div>
                    </div>
                    @if($incentive->description)
                        <div class="row mt-1">
                            <div class="col-12">
                                <small class="text-muted">{{ Str::limit($incentive->description, 50) }}</small>
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    <!-- Recent Activity Section -->
    {{-- @if (count($recentActivity) > 0)
        <div class="recent-activity mt-4">
            <h6>Recent Activity</h6>
            @foreach ($recentActivity as $activity)
                <div class="activity-item">
                    <div class="activity-action">
                        <i class="fa-solid fa-circle-info me-1"></i>
                        {{ $activity->action }}
                    </div>
                    <div class="activity-date">
                        <small class="text-muted">{{ $activity->created_at->diffForHumans() }}</small>
                    </div>
                    @if($activity->description)
                        <div class="activity-description">
                            <small>{{ Str::limit($activity->description, 60) }}</small>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif --}}

    <livewire:components.confirmation-modal />
</div>

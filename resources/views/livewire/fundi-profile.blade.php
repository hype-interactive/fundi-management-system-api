<div wire:poll.keep-live class="section-right profile">
    <div class="avatar-wrapper mb-4">
        <div class="light-border">
            <div class="avatar">
                @if ($fundi->profile_picture_url)
                    <img src="{{ asset('storage/' . $fundi->profile_picture) }}" alt="Profile Picture">
                @else
                    <img src="{{ asset('images/avatar.png') }}" alt="Default Avatar">
                @endif
                {{-- <img src="{{ asset('images/avatar.png') }}" alt="Default Avatar"> --}}
            </div>
        </div>
    </div>
    <h6>Fundi's Profile</h6>
    <div class="fundi-info">
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
                {{ $fundi->full_name }}
            </div>
        </div>

        <div class="row">
            <div class="col-md-5">
                <div class="label">Gender</div>
            </div>
            <div class="col-md-7">
                {{ ucfirst($fundi->gender) }}
            </div>
        </div>

        <div class="row">
            <div class="col-md-5">
                <div class="label">NIDA Number</div>
            </div>
            <div class="col-md-7">
                {{ $fundi->nida_number ?: 'Not provided' }}
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="title">Contact Information</div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-5">
                <div class="label">Primary Phone</div>
            </div>
            <div class="col-md-7">
                {{ $fundi->primary_phone }}
            </div>
        </div>

        <div class="row">
            <div class="col-md-5">
                <div class="label">Transaction Phone</div>
            </div>
            <div class="col-md-7">
                {{ $fundi->transaction_phone ?: 'Same as primary' }}
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="title">Location</div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-5">
                <div class="label">Ward</div>
            </div>
            <div class="col-md-7">
                {{ $fundi->ward->name }}
            </div>
        </div>

        <div class="row">
            <div class="col-md-5">
                <div class="label">District</div>
            </div>
            <div class="col-md-7">
                {{ $fundi->ward->district->name }}
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="title">Fundi Details</div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-5">
                <div class="label">Ranking</div>
            </div>
            <div class="col-md-7">
                {{ $fundi->ranking == 'fundi_kuu' ? 'Fundi Mkuu' : 'Fundi Msaidizi' }}
            </div>
        </div>

        <div class="row">
            <div class="col-md-5">
                <div class="label">Status</div>
            </div>
            <div class="col-md-7">
                <span class="badge {{ $fundi->approved_at ? 'bg-success' : 'bg-warning' }}">
                    {{ $fundi->approved_at ? 'Approved' : 'Pending Approval' }}
                </span>
            </div>
        </div>

        <div class="row">
            <div class="col-md-5">
                <div class="label">Added By</div>
            </div>
            <div class="col-md-7">
                {{ $fundi->creator->full_name ?? 'N/A' }}
            </div>
        </div>

        @if ($fundi->approved_at)
            <div class="row">
                <div class="col-md-5">
                    <div class="label">Approved By</div>
                </div>
                <div class="col-md-7">
                    {{ $fundi->approver->full_name ?? 'N/A' }}
                </div>
            </div>
        @endif
    </div>

<!-- Actions Section -->
@if (!$fundi->approved_at || !$fundi->rejected_at)
    <div class="actions-card mt-4">
        <h6 class="card-title">
            <i class="fa-solid fa-cogs mr-2"></i>
            Actions
        </h6>
        <div class="action-buttons">
            @if (!$fundi->approved_at)
                <button class="btn btn-success btn-sm" wire:click="confirmApprove">
                    <i class="fa-solid fa-check me-1"></i>
                    Approve 
                </button>
                <button class="btn btn-warning btn-sm" wire:click="confirmReject">
                    <i class="fa-solid fa-times me-1"></i>
                    Reject 
                </button>
            @endif
            <button class="btn btn-danger btn-sm" wire:click="confirmDelete">
                <i class="fa-solid fa-trash me-1"></i>
                Delete
            </button>
        </div>
    </div>
@endif

    <!-- Legacy Match Section -->
    @if ($legacyMatch)
        <div class="legacy-match">
            <div class="legacy-title">Legacy System Match</div>
            <div class="row">
                <div class="col-md-5">
                    <div class="label">Legacy ID</div>
                </div>
                <div class="col-md-7">
                    {{ $legacyData['legacy_id'] }}
                </div>
            </div>
            <div class="row">
                <div class="col-md-5">
                    <div class="label">Registered</div>
                </div>
                <div class="col-md-7">
                    {{ \Carbon\Carbon::parse($legacyData['registration_date'])->format('d M, Y') }}
                </div>
            </div>
        </div>
    @endif
    <!-- End of Legacy Match Section -->

    <!-- Transactions and Site Visits Section -->
    @if (count($transactions) > 0)
        <div class="transactions-history mt-4">
            <h6>Recent Transactions</h6>
            @foreach ($transactions as $transaction)
                <div class="tile-special">
                    <div class="row">
                        <div class="col-6 text-start">
                            <div>TZS {{ number_format($transaction->amount) }}</div>
                            <small class="text-muted">{{ $transaction->created_at->format('d M, Y') }}</small>
                        </div>
                        <div class="col-6 text-end">
                            <span
                                class="badge {{ $transaction->status == 'completed' ? 'bg-success' : 'bg-warning' }}">
                                {{ ucfirst($transaction->status) }}
                            </span>
                            <div>
                                <i class="fa-solid fa-money-bill-transfer"></i>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
    <!-- End of Transactions Section -->

    <!-- Site Visits Section -->
    @if (count($siteVisits) > 0)
        <div class="site-visits">
            <h6>Recent Site Visits</h6>
            @foreach ($siteVisits as $visit)
                <div class="visit-item">
                    <div class="site-name">{{ $visit->site_name ?? 'Unknown Site' }}</div>
                    <div class="date">{{ $visit->visit_date->format('d M, Y') }}</div>
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

    <livewire:components.confirmation-modal />
</div>

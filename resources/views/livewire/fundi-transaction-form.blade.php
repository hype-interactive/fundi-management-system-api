<div>
    @if (session()->has('message'))
        <div class="alert alert-success">
            {{ session('message') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

    <div class="mb-5">
        <div class="btn-group" role="group">
            <button type="button" class="btn {{ $activeTab == 'transactions' ? 'btn-primary' : 'btn-secondary' }}"
                wire:click="switchTab('transactions')">
                <i class="fa-solid fa-money-bill-transfer"></i> Transactions
            </button>
            <button type="button" class="btn {{ $activeTab == 'site_visits' ? 'btn-primary' : 'btn-secondary' }}"
                wire:click="switchTab('site_visits')">
                <i class="fa-solid fa-hard-hat"></i> Site Visits
            </button>
        </div>
    </div>

    @if ($activeTab == 'transactions')
        @if ($canViewTransactions)
            <!-- Transaction Summary -->
            <div class="row mb-4 ">
                <div class="col-md-3">
                    <div class="rounded-sm border border-success">
                        <div class="p-2">
                            <div class="text-xs text-grey font-thin">Total Received</div>
                            <div class="text-xl font-semibold">
                                {{ number_format($transactionSummary['total_received']) }} TZS</div>
                        </div>
                    </div>
                </div>
                {{-- <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <h6 class="card-title">Pending</h6>
                            <h4>{{ number_format($transactionSummary['total_pending']) }} TZS</h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <h6 class="card-title">Processing</h6>
                            <h4>{{ number_format($transactionSummary['total_processing']) }} TZS</h4>
                        </div>
                    </div>
                </div> --}}
                <div class="col-md-3">
                    <div class="rounded-sm border border-danger">
                        <div class="p-2">
                            <div class=" text-xs text-grey font-thin">Failed</div>
                            <div class="text-xl font-semibold">{{ number_format($transactionSummary['total_failed']) }}
                                TZS</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Transactions List -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Disbursement Transaction History</h5>
                </div>
                <div class="card-body">
                    <!-- Filters -->
                    {{-- <div class="mb-3">
                        <div class="form-row">
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="transaction_status">Status</label>
                                    <select class="form-control" id="transaction_status" wire:model="transaction_status">
                                        <option value="">All Statuses</option>
                                        <option value="pending">Pending</option>
                                        <option value="processing">Processing</option>
                                        <option value="completed">Completed</option>
                                        <option value="failed">Failed</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="disbursement_batch_id">Disburse. Batch</label>
                                    <select class="form-control" id="disbursement_batch_id" wire:model="disbursement_batch_id">
                                        <option value="">All Batches</option>
                                        @foreach ($disbursementBatches as $batch)
                                            <option value="{{ $batch->id }}">{{ $batch->title }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="processed_date_from">Processed From</label>
                                    <input type="date" class="form-control" id="processed_date_from"
                                        wire:model="processed_date_from">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="processed_date_to">Processed To</label>
                                    <input type="date" class="form-control" id="processed_date_to"
                                        wire:model="processed_date_to">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <button class="btn btn-secondary form-control" wire:click="resetTransactionFilters">
                                        <i class="fa-solid fa-filter-circle-xmark"></i> Clear
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div> --}}

                    <!-- Transactions Table -->
                    @if (count($transactions) > 0)
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Batch</th>
                                        <th>Amount (TZS)</th>
                                        <th>Reference</th>
                                        <th>Status</th>
                                        <th>Processed Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($transactions as $transaction)
                                        <tr>
                                            <td>
                                                <div>
                                                    <strong>{{ $transaction->disbursementBatch->title }}</strong>
                                                    <br>
                                                    <small class="text-muted">
                                                        {{ $transaction->disbursementBatch->budgetAllocation->title }}
                                                    </small>
                                                </div>
                                            </td>
                                            <td>
                                                <strong>{{ number_format($transaction->amount) }}</strong>
                                            </td>
                                            <td>
                                                {{ $transaction->transaction_reference ?? 'N/A' }}
                                            </td>
                                            <td>
                                                <span
                                                    class="badge {{ $transaction->status == 'completed'
                                                        ? 'bg-success'
                                                        : ($transaction->status == 'processing'
                                                            ? 'bg-info'
                                                            : ($transaction->status == 'pending'
                                                                ? 'bg-warning'
                                                                : 'bg-danger')) }}">
                                                    {{ ucfirst($transaction->status) }}
                                                </span>
                                            </td>
                                            <td>
                                                {{ $transaction->processed_at ? $transaction->processed_at->format('d M, Y H:i') : 'Not processed' }}
                                            </td>
                                            <td>
                                                @if ($transaction->status == 'failed' && $transaction->failure_reason)
                                                    <button class="btn btn-sm btn-outline-danger" data-toggle="tooltip"
                                                        title="{{ $transaction->failure_reason }}">
                                                        <i class="fa-solid fa-exclamation-triangle"></i>
                                                    </button>
                                                @endif
                                                @if ($transaction->mno_response)
                                                    <button class="btn btn-sm btn-outline-info" data-toggle="tooltip"
                                                        title="MNO Response: {{ $transaction->mno_response }}">
                                                        <i class="fa-solid fa-info-circle"></i>
                                                    </button>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3">
                            {{ $transactions->links() }}
                        </div>
                    @else
                        <div class="alert alert-info">
                            <i class="fa-solid fa-info-circle"></i>
                            No disbursement transactions found for this fundi. Transactions are created automatically
                            when disbursement batches are processed by administrators.
                        </div>
                    @endif
                </div>
            </div>
        @else
            <div class="alert alert-warning">
                <i class="fa-solid fa-lock"></i>
                You do not have permission to view transaction details.
            </div>
        @endif
    @endif

    @if ($activeTab == 'site_visits')
        <!-- Site Visits List -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Site Visit History</h5>
            </div>
            <div class="card-body">
                <!-- Filters -->
                {{-- <div class="mb-3">
                    <div class="form-row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="site_visit_status">Status</label>
                                <select class="form-control" id="site_visit_status" wire:model="site_visit_status">
                                    <option value="">All Statuses</option>
                                    <option value="completed">Completed</option>
                                    <option value="pending">Pending</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="site_visit_date_from">From Date</label>
                                <input type="date" class="form-control" id="site_visit_date_from"
                                    wire:model="site_visit_date_from">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="site_visit_date_to">To Date</label>
                                <input type="date" class="form-control" id="site_visit_date_to"
                                    wire:model="site_visit_date_to">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <button class="btn btn-secondary form-control" wire:click="resetSiteVisitFilters">
                                    <i class="fa-solid fa-filter-circle-xmark"></i> Clear Filters
                                </button>
                            </div>
                        </div>
                    </div>
                </div> --}}

                <!-- Site Visits Table -->
                @if (count($siteVisits) > 0)
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Site</th>
                                    <th>Date</th>
                                    <th>Purpose</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($siteVisits as $visit)
                                    <tr>
                                        <td>
                                            <a href="{{ route('site-visit', $visit) }}" class="text-decoration-none">
                                                <div class="visit-details">
                                                    <span class="fw-semibold site-name">{{ $visit->site_name }}</span>
                                                </div>
                                            </a>
                                        </td>
                                        <td>{{ $visit->visit_date->format('d M, Y') }}</td>
                                        <td>{{ $visit->purpose ?? 'Site Visit' }}</td>
                                        <td>
                                            <span
                                                class="badge {{ $visit->status == 'completed' ? 'bg-success' : ($visit->status == 'pending' ? 'bg-warning' : 'bg-danger') }}">
                                                {{ ucfirst($visit->status) }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        {{ $siteVisits->links() }}
                    </div>
                @else
                    <div class="alert alert-info">
                        No site visits found for this fundi.
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>

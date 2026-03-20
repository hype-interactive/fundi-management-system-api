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
            <button type="button" class="btn {{ $activeTab == 'site_visits' ? 'btn-primary' : 'btn-secondary' }}"
                wire:click="switchTab('site_visits')">
                <i class="fa-solid fa-hard-hat"></i> Site Visits
            </button>
            <button type="button" class="btn {{ $activeTab == 'transactions' ? 'btn-primary' : 'btn-secondary' }}"
                wire:click="switchTab('transactions')">
                <i class="fa-solid fa-money-bill-transfer"></i> Transactions
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
                @if (count($siteVisits) > 0)
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Site</th>
                                    <th>Bags</th>
                                    <th>Inspector</th>
                                    <th>Date</th>
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
                                        <td>{{ $visit->fundis->first()->total_product_quantity ?? '' }}</td>
                                        <td>{{ $visit->operator->full_name ?? '' }}</td>
                                        <td>{{ $visit->visit_date->format('d M, Y') }}</td>
                                        <td>
                                            <span
                                                class=" {{ $visit->status == 'completed' ? 'text-success' : ($visit->status == 'pending' ? 'text-warning' : 'text-danger') }}">
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

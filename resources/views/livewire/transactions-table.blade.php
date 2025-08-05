<div>
    <!-- Filters Section -->
    <div class="filter mb-3">
        <div class="row">
            <div class="col-md-3">
                <div class="search">
                    <input wire:model.live.debounce.300ms="search_key" type="text" placeholder="Search transactions...">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </div>
            </div>
            <div class="col-md-9">
                <div class="row">
                    <div class="col">
                        <select wire:model.live="status" class="form-control">
                            <option value="">All Status</option>
                            @foreach($statusOptions as $key => $value)
                                <option value="{{ $key }}">{{ $value }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col">
                        <select wire:model.live="fundi_id" class="form-control">
                            <option value="">All Fundis</option>
                            @foreach($fundis as $fundi)
                                <option value="{{ $fundi->id }}">{{ $fundi->full_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col">
                        <input wire:model.live="date_from" type="date" class="form-control" placeholder="From Date">
                    </div>
                    <div class="col">
                        <input wire:model.live="date_to" type="date" class="form-control" placeholder="To Date">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Actions -->
    <div class="results-summary mb-2 d-flex justify-content-between align-items-center">
        <div class="d-flex gap-2">
            <button wire:click="$toggle('compactView')" class="btn btn-sm {{ $compactView ? 'btn-primary' : 'btn-outline-primary' }}">
                <i class="fa-solid fa-{{ $compactView ? 'expand' : 'compress' }}"></i>
                {{ $compactView ? 'Expand' : 'Compact' }}
            </button>
        </div>
        <div class="d-flex gap-2">
            <button wire:click="refresh" class="btn btn-outline-secondary btn-sm">
                <i class="fa-solid fa-arrows-rotate me-1"></i>
                Clear Filters
            </button>
            <button class="btn btn-outline-success btn-sm">
                <i class="fa-solid fa-file-excel me-1"></i>
                Export
            </button>
        </div>
    </div>

    <!-- Table -->
    <div class="custom-table">
        <div class="d-none d-md-block">
            <table class="table">
                <thead class="thead-light">
                    <tr>
                        <th scope="col">
                            <a wire:click="refresh">
                                <i class="fa-solid fa-arrows-rotate"></i>
                            </a>
                        </th>
                        <th scope="col">Transaction</th>
                        @if (!$compactView)
                            <th scope="col" class="d-lg-table-cell d-none">Fundi</th>
                            <th scope="col" class="d-lg-table-cell d-none">Amount</th>
                            <th scope="col" class="d-xl-table-cell d-none">Batch</th>
                        @else
                            <th scope="col">Details</th>
                        @endif
                        <th scope="col" class="d-lg-table-cell d-none">Status</th>
                        <th scope="col" class="d-xl-table-cell d-none">Date</th>
                        <th scope="col" class="d-xl-table-cell d-none">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($transactions as $transaction)
                        <tr>
                            <th scope="row">
                                <div class="avatar-circle {{ $transaction->status === 'completed' ? 'bg-success' : ($transaction->status === 'failed' ? 'bg-danger' : 'bg-warning') }}">
                                    <i class="fa-solid fa-receipt"></i>
                                </div>
                            </th>
                            <td>
                                <div class="transaction-info">
                                    <span class="fw-semibold">{{ $transaction->transaction_reference }}</span>
                                    @if($transaction->failure_reason)
                                        <small class="text-danger d-block">{{ Str::limit($transaction->failure_reason, 50) }}</small>
                                    @endif
                                </div>
                            </td>
                            @if (!$compactView)
                                <td class="d-lg-table-cell d-none">
                                    <div class="fundi-info">
                                        <span class="fundi-name">{{ $transaction->fundi->full_name ?? 'N/A' }}</span>
                                        <small class="text-muted d-block">{{ $transaction->fundi->primary_phone ?? 'N/A' }}</small>
                                    </div>
                                </td>
                                <td class="d-lg-table-cell d-none">
                                    <span class="font-weight-bold">{{ number_format($transaction->amount) }} TZS</span>
                                </td>
                                <td class="d-xl-table-cell d-none">
                                    <span class="batch-name">{{ $transaction->disbursementBatch->title ?? 'N/A' }}</span>
                                </td>
                            @else
                                <td>
                                    <div class="transaction-details">
                                        <div class="font-weight-bold">{{ number_format($transaction->amount) }} TZS</div>
                                        <small class="text-muted">{{ $transaction->fundi->full_name ?? 'N/A' }}</small>
                                        <div class="mt-1">
                                            <small class="text-info">{{ $transaction->disbursementBatch->title ?? 'N/A' }}</small>
                                        </div>
                                    </div>
                                </td>
                            @endif
                            <td class="d-lg-table-cell d-none">
                                @include('components.transaction-status-badge', ['status' => $transaction->status])
                            </td>
                            <td class="d-xl-table-cell d-none">
                                <div class="date-info">
                                    <span>{{ $transaction->created_at->format('M d, Y') }}</span>
                                    <small class="text-muted d-block">{{ $transaction->created_at->format('H:i') }}</small>
                                </div>
                            </td>
                            <td class="d-xl-table-cell d-none">
                                <div class="btn-group" role="group">
                                    <button class="btn btn-sm btn-outline-primary" title="View Details">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                    @if($transaction->status === 'failed')
                                        <button class="btn btn-sm btn-outline-warning"
                                                wire:click="retryTransaction({{ $transaction->id }})"
                                                title="Retry Transaction">
                                            <i class="fa-solid fa-redo"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                <i class="fa-solid fa-receipt me-2"></i>
                                No transactions found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Mobile Cards -->
        <div class="mobile-cards d-md-none">
            @forelse ($transactions as $transaction)
                <div class="transaction-card" wire:key="mobile-{{ $transaction->id }}">
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="card-title small">{{ $transaction->transaction_reference }}</h6>
                                    <p class="card-text">{{ $transaction->fundi->full_name ?? 'N/A' }}</p>
                                </div>
                                @include('components.transaction-status-badge', ['status' => $transaction->status])
                            </div>
                            <div class="row mt-3">
                                <div class="col-6">
                                    <small class="text-muted">Amount</small>
                                    <div class="font-weight-bold">{{ number_format($transaction->amount) }} TZS</div>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">Date</small>
                                    <div>{{ $transaction->created_at->format('M d, Y') }}</div>
                                </div>
                            </div>
                            <div class="mt-2">
                                <small class="text-muted">Batch: {{ $transaction->disbursementBatch->title ?? 'N/A' }}</small>
                            </div>
                            @if($transaction->failure_reason)
                                <div class="mt-2">
                                    <small class="text-danger">{{ $transaction->failure_reason }}</small>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="empty-state text-center py-5">
                    <i class="fa-solid fa-receipt fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No transactions found</h5>
                </div>
            @endforelse
        </div>
    </div>

    <!-- Pagination -->
    <div class="pagination-wrapper d-flex justify-content-center mt-3">
        {{ $transactions->links() }}
    </div>
</div>

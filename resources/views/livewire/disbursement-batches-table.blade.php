<div>
    <!-- Filters Section -->
    <div class="filter mb-3">
        <div class="row">
            <div class="col-md-3">
                <div class="search">
                    <input wire:model.live.debounce.300ms="search_key" type="text" placeholder="Search disbursements...">
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
                        <select wire:model.live="budget_allocation_id" class="form-control">
                            <option value="">All Budgets</option>
                            @foreach($budgetAllocations as $budget)
                                <option value="{{ $budget->id }}">{{ Str::limit($budget->title, 30) }}</option>
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
                        <th scope="col">Disbursement</th>
                        @if (!$compactView)
                            <th scope="col" class="d-lg-table-cell d-none">Budget</th>
                            <th scope="col" class="d-lg-table-cell d-none">Amount</th>
                            <th scope="col" class="d-xl-table-cell d-none">Transactions</th>
                        @else
                            <th scope="col">Details</th>
                        @endif
                        <th scope="col" class="d-lg-table-cell d-none">Status</th>
                        <th scope="col" class="d-xl-table-cell d-none">Created</th>
                        <th scope="col" class="d-xl-table-cell d-none">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($batches as $batch)
                        <tr>
                            <th scope="row">
                                <div class="avatar-circle bg-info">
                                    <i class="fa-solid fa-paper-plane"></i>
                                </div>
                            </th>
                            <td>
                                <div class="disbursement-info">
                                    <span class="fw-semibold">{{ $batch->title }}</span>
                                    @if($batch->description)
                                        <small class="text-muted d-block">{{ Str::limit($batch->description, 50) }}</small>
                                    @endif
                                </div>
                            </td>
                            @if (!$compactView)
                                <td class="d-lg-table-cell d-none">
                                    <span class="budget-name">{{ $batch->budgetAllocation->title ?? 'N/A' }}</span>
                                </td>
                                <td class="d-lg-table-cell d-none">
                                    <span class="font-weight-bold">{{ number_format($batch->total_amount) }} TZS</span>
                                </td>
                                <td class="d-xl-table-cell d-none">
                                    @php
                                        $totalTx = $batch->transactions->count();
                                        $successTx = $batch->transactions->where('status', 'completed')->count();
                                        $failedTx = $batch->transactions->where('status', 'failed')->count();
                                    @endphp
                                    <div class="transaction-summary">
                                        <div>{{ $totalTx }} total</div>
                                        <small class="text-success">{{ $successTx }} success</small>
                                        @if($failedTx > 0)
                                            <small class="text-danger">{{ $failedTx }} failed</small>
                                        @endif
                                    </div>
                                </td>
                            @else
                                <td>
                                    <div class="batch-details">
                                        <div class="font-weight-bold">{{ number_format($batch->total_amount) }} TZS</div>
                                        <small class="text-muted">{{ $batch->budgetAllocation->title ?? 'N/A' }}</small>
                                        <div class="mt-1">
                                            <small class="text-info">{{ $batch->transactions->count() }} transactions</small>
                                        </div>
                                    </div>
                                </td>
                            @endif
                            <td class="d-lg-table-cell d-none">
                                @include('components.status-badge', ['status' => $batch->status])
                            </td>
                            <td class="d-xl-table-cell d-none">
                                <div class="creator-info">
                                    <span class="creator-name">{{ $batch->creator->full_name ?? 'N/A' }}</span>
                                    <small class="text-muted d-block">{{ $batch->created_at->format('M d, Y') }}</small>
                                </div>
                            </td>
                            <td class="d-xl-table-cell d-none">
                                <div class="btn-group" role="group">
                                    <button class="btn btn-sm btn-outline-primary" title="View Details">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                    @if($batch->transactions->where('status', 'failed')->count() > 0)
                                        <button class="btn btn-sm btn-outline-warning"
                                                wire:click="retryFailedBatch({{ $batch->id }})"
                                                title="Retry Failed">
                                            <i class="fa-solid fa-redo"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                <i class="fa-solid fa-paper-plane me-2"></i>
                                No disbursement batches found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Mobile Cards -->
        <div class="mobile-cards d-md-none">
            @forelse ($batches as $batch)
                <div class="disbursement-card" wire:key="mobile-{{ $batch->id }}">
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="card-title">{{ $batch->title }}</h6>
                                    <p class="card-text small text-muted">{{ Str::limit($batch->description, 60) }}</p>
                                </div>
                                @include('components.status-badge', ['status' => $batch->status])
                            </div>
                            <div class="row mt-3">
                                <div class="col-6">
                                    <small class="text-muted">Amount</small>
                                    <div class="font-weight-bold">{{ number_format($batch->total_amount) }} TZS</div>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">Transactions</small>
                                    <div>{{ $batch->transactions->count() }}</div>
                                </div>
                            </div>
                            <div class="mt-2">
                                <small class="text-muted">Budget: {{ $batch->budgetAllocation->title ?? 'N/A' }}</small>
                            </div>
                            <div class="mt-2">
                                <small class="text-muted">By {{ $batch->creator->full_name ?? 'N/A' }} • {{ $batch->created_at->diffForHumans() }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="empty-state text-center py-5">
                    <i class="fa-solid fa-paper-plane fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No disbursement batches found</h5>
                </div>
            @endforelse
        </div>
    </div>

    <!-- Pagination -->
    <div class="pagination-wrapper d-flex justify-content-center mt-3">
        {{ $batches->links() }}
    </div>
</div>

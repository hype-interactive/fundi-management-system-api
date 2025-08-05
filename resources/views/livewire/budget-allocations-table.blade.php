<div>
    <!-- Filters Section -->
    <div class="filter mb-3">
        <div class="row">
            <div class="col-md-3">
                <div class="search">
                    <input wire:model.live.debounce.300ms="search_key" type="text" placeholder="Search budgets...">
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
                        <select wire:model.live="created_by" class="form-control">
                            <option value="">All Creators</option>
                            @foreach($creators as $creator)
                                <option value="{{ $creator->id }}">{{ $creator->full_name }}</option>
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
            <button wire:click="exportToExcel" class="btn btn-outline-success btn-sm">
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
                        <th scope="col">Budget Title</th>
                        @if (!$compactView)
                            <th scope="col" class="d-lg-table-cell d-none">Total Amount</th>
                            <th scope="col" class="d-lg-table-cell d-none">Remaining</th>
                            <th scope="col" class="d-xl-table-cell d-none">Utilization</th>
                        @else
                            <th scope="col">Amount & Usage</th>
                        @endif
                        <th scope="col" class="d-lg-table-cell d-none">Status</th>
                        <th scope="col" class="d-xl-table-cell d-none">Created By</th>
                        <th scope="col" class="d-xl-table-cell d-none">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($budgets as $budget)
                        <tr>
                            <th scope="row">
                                <div class="avatar-circle bg-primary">
                                    <i class="fa-solid fa-wallet"></i>
                                </div>
                            </th>
                            <td>
                                <div class="budget-info">
                                    <span class="fw-semibold">{{ $budget->title }}</span>
                                    @if($budget->description)
                                        <small class="text-muted d-block">{{ Str::limit($budget->description, 50) }}</small>
                                    @endif
                                </div>
                            </td>
                            @if (!$compactView)
                                <td class="d-lg-table-cell d-none">
                                    <span class="font-weight-bold">{{ number_format($budget->total_amount) }} TZS</span>
                                </td>
                                <td class="d-lg-table-cell d-none">
                                    <span class="text-success">{{ number_format($budget->remaining_amount) }} TZS</span>
                                </td>
                                <td class="d-xl-table-cell d-none">
                                    @php
                                        $utilizationRate = $budget->total_amount > 0
                                            ? round((($budget->total_amount - $budget->remaining_amount) / $budget->total_amount) * 100, 2)
                                            : 0;
                                    @endphp
                                    <div class="progress" style="width: 60px; height: 8px;">
                                        <div class="progress-bar bg-primary" style="width: {{ $utilizationRate }}%"></div>
                                    </div>
                                    <small>{{ $utilizationRate }}%</small>
                                </td>
                            @else
                                <td>
                                    <div class="amount-info">
                                        <div class="font-weight-bold">{{ number_format($budget->total_amount) }} TZS</div>
                                        <small class="text-muted">{{ number_format($budget->remaining_amount) }} remaining</small>
                                    </div>
                                </td>
                            @endif
                            <td class="d-lg-table-cell d-none">
                                @include('components.status-badge', ['status' => $budget->status])
                            </td>
                            <td class="d-xl-table-cell d-none">
                                <span class="creator-name">{{ $budget->creator->full_name ?? 'N/A' }}</span>
                            </td>
                            <td class="d-xl-table-cell d-none">
                                <div class="btn-group" role="group">
                                    <button class="btn btn-sm btn-outline-primary" data-toggle="modal" data-target="#editBudgetModal" wire:click="$dispatch('editBudget', { budgetId: {{ $budget->id }} })">
                                        <i class="fa-solid fa-edit"></i>
                                    </button>
                                    @if($budget->disbursementBatches->count() == 0)
                                        <button class="btn btn-sm btn-outline-danger" onclick="confirm('Delete this budget?') || event.stopImmediatePropagation()" wire:click="deleteBudget({{ $budget->id }})">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                <i class="fa-solid fa-wallet me-2"></i>
                                No budget allocations found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Mobile Cards -->
        <div class="mobile-cards d-md-none">
            @forelse ($budgets as $budget)
                <div class="budget-card" wire:key="mobile-{{ $budget->id }}">
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="card-title">{{ $budget->title }}</h6>
                                    <p class="card-text small text-muted">{{ Str::limit($budget->description, 60) }}</p>
                                </div>
                                @include('components.status-badge', ['status' => $budget->status])
                            </div>
                            <div class="row mt-3">
                                <div class="col-6">
                                    <small class="text-muted">Total Amount</small>
                                    <div class="font-weight-bold">{{ number_format($budget->total_amount) }} TZS</div>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">Remaining</small>
                                    <div class="text-success">{{ number_format($budget->remaining_amount) }} TZS</div>
                                </div>
                            </div>
                            <div class="mt-2">
                                <small class="text-muted">Created by {{ $budget->creator->full_name ?? 'N/A' }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="empty-state text-center py-5">
                    <i class="fa-solid fa-wallet fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No budget allocations found</h5>
                </div>
            @endforelse
        </div>
    </div>

    <!-- Pagination -->
    <div class="pagination-wrapper d-flex justify-content-center mt-3">
        {{ $budgets->links() }}
    </div>
</div>

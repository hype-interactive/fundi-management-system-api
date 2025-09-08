<div>
    <!-- Dashboard Summary Cards -->
    <div class="row mb-4">
        <!-- Period Selector -->
        <div class="col-12 mb-3">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Financial Overview</h5>
                <div class="d-flex align-items-center gap-2">
                    <select wire:model.live="selectedPeriod" class="form-control form-control-sm" style="width: auto;">
                        <option value="7">Last 7 days</option>
                        <option value="30">Last 30 days</option>
                        <option value="90">Last 90 days</option>
                        <option value="365">Last year</option>
                    </select>
                    <button wire:click="refreshDashboard" class="btn btn-outline-primary btn-sm">
                        <i class="fa-solid fa-refresh{{ $refreshing ? ' fa-spin' : '' }}"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Budget Summary -->
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card dashboard-card budget-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="card-subtitle text-muted mb-2">Total Budgets</h6>
                            <h4 class="card-title mb-1">{{ $dashboardData['budget_summary']['total_budgets'] ?? 0 }}</h4>
                            <div class="progress" style="height: 4px;">
                                <div class="progress-bar bg-primary" style="width: {{ $dashboardData['budget_summary']['utilization_rate'] ?? 0 }}%"></div>
                            </div>
                            <small class="text-muted">{{ $dashboardData['budget_summary']['utilization_rate'] ?? 0 }}% utilized</small>
                        </div>
                        <div class="card-icon bg-primary">
                            <i class="fa-solid fa-wallet"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Allocated -->
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card dashboard-card allocated-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="card-subtitle text-muted mb-2">Total Allocated</h6>
                            <h4 class="card-title mb-1">{{ number_format($dashboardData['budget_summary']['total_allocated'] ?? 0) }}</h4>
                            <small class="text-success">TZS</small>
                        </div>
                        <div class="card-icon bg-success">
                            <i class="fa-solid fa-coins"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Disbursements -->
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card dashboard-card disbursement-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="card-subtitle text-muted mb-2">Disbursements</h6>
                            <h4 class="card-title mb-1">{{ $dashboardData['disbursement_summary']['total_batches'] ?? 0 }}</h4>
                            <small class="text-info">{{ $dashboardData['disbursement_summary']['completed_batches'] ?? 0 }} completed</small>
                        </div>
                        <div class="card-icon bg-info">
                            <i class="fa-solid fa-paper-plane"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transactions -->
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card dashboard-card transaction-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="card-subtitle text-muted mb-2">Transactions</h6>
                            <h4 class="card-title mb-1">{{ $dashboardData['transaction_summary']['total_transactions'] ?? 0 }}</h4>
                            <small class="text-{{ ($dashboardData['transaction_summary']['success_rate'] ?? 0) >= 90 ? 'success' : 'warning' }}">
                                {{ $dashboardData['transaction_summary']['success_rate'] ?? 0 }}% success
                            </small>
                        </div>
                        <div class="card-icon bg-warning">
                            <i class="fa-solid fa-receipt"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts and Recent Activity -->
    <div class="row">
        <!-- Recent Budgets -->
        <div class="col-lg-4 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Recent Budget Allocations</h6>
                    <a href="{{ route('financial.budgets') }}" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    @forelse($dashboardData['recent_budgets'] ?? [] as $budget)
                        <div class="list-item">
                            <div class="d-flex justify-content-between align-items-center p-3 border-bottom">
                                <div>
                                    <h6 class="mb-1">{{ $budget->title }}</h6>
                                    <small class="text-muted">{{ $budget->creator->full_name ?? 'N/A' }}</small>
                                </div>
                                <div class="text-right">
                                    <div class="font-weight-bold">{{ number_format($budget->total_amount) }} TZS</div>
                                    <small class="text-muted">{{ $budget->created_at->diffForHumans() }}</small>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center p-4 text-muted">
                            <i class="fa-solid fa-wallet fa-2x mb-2"></i>
                            <p>No recent budgets</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Recent Disbursements -->
        <div class="col-lg-4 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Recent Disbursements</h6>
                    <a href="{{ route('financial.disbursements') }}" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    @forelse($dashboardData['recent_disbursements'] ?? [] as $disbursement)
                        <div class="list-item">
                            <div class="d-flex justify-content-between align-items-center p-3 border-bottom">
                                <div>
                                    <h6 class="mb-1">{{ $disbursement->title }}</h6>
                                    <small class="text-muted">{{ $disbursement->budgetAllocation->title ?? 'N/A' }}</small>
                                </div>
                                <div class="text-right">
                                    <div class="font-weight-bold">{{ number_format($disbursement->total_amount) }} TZS</div>
                                    @include('components.status-badge', ['status' => $disbursement->status])
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center p-4 text-muted">
                            <i class="fa-solid fa-paper-plane fa-2x mb-2"></i>
                            <p>No recent disbursements</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Recent Transactions -->
        <div class="col-lg-4 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Recent Transactions</h6>
                    <a href="{{ route('financial.transactions') }}" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body p-0" style="max-height: 400px; overflow-y: auto;">
                    @forelse($dashboardData['recent_transactions'] ?? [] as $transaction)
                        <div class="list-item">
                            <div class="d-flex justify-content-between align-items-center p-2 border-bottom">
                                <div>
                                    <h6 class="mb-1 small">{{ $transaction->fundi->full_name ?? 'N/A' }}</h6>
                                    <small class="text-muted">{{ $transaction->transaction_reference }}</small>
                                </div>
                                <div class="text-right">
                                    <div class="font-weight-bold small">{{ number_format($transaction->amount) }} TZS</div>
                                    @include('components.transaction-status-badge', ['status' => $transaction->status])
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center p-4 text-muted">
                            <i class="fa-solid fa-receipt fa-2x mb-2"></i>
                            <p>No recent transactions</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

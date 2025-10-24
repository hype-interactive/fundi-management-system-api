<?php

namespace App\Livewire;

use App\Models\BudgetAllocation;
use App\Models\DisbursementBatch;
use App\Models\Transaction;
use App\Traits\HasAlerts;
use Livewire\Component;
use Carbon\Carbon;

class FinancialDashboard extends Component
{
    use HasAlerts;

    public $dashboardData = [];
    public $selectedPeriod = '30'; // Days
    public $refreshing = false;

    public function mount()
    {
        $this->loadDashboardData();
        $this->displayFlashedAlerts();
    }

    public function updatedSelectedPeriod()
    {
        $this->loadDashboardData();
    }

    public function loadDashboardData()
    {
        $this->refreshing = true;

        $dateFrom = Carbon::now()->subDays($this->selectedPeriod);
        $dateTo = Carbon::now();

        // Budget Summary
        $budgetSummary = [
            'total_budgets' => BudgetAllocation::where('status', 'active')->count(),
            'total_allocated' => BudgetAllocation::where('status', 'active')->sum('total_amount'),
            'total_remaining' => BudgetAllocation::where('status', 'active')->sum('remaining_amount'),
            'utilization_rate' => 0,
        ];

        if ($budgetSummary['total_allocated'] > 0) {
            $budgetSummary['utilization_rate'] = round(
                (($budgetSummary['total_allocated'] - $budgetSummary['total_remaining']) / $budgetSummary['total_allocated']) * 100,
                2
            );
        }

        // Disbursement Summary
        $disbursementSummary = [
            'total_batches' => DisbursementBatch::whereBetween('created_at', [$dateFrom, $dateTo])->count(),
            'total_amount' => DisbursementBatch::whereBetween('created_at', [$dateFrom, $dateTo])->sum('total_amount'),
            'completed_batches' => DisbursementBatch::whereBetween('created_at', [$dateFrom, $dateTo])
                ->where('status', 'completed')->count(),
            'processing_batches' => DisbursementBatch::whereBetween('created_at', [$dateFrom, $dateTo])
                ->whereIn('status', ['pending', 'processing'])->count(),
        ];

        // Transaction Summary
        $transactionSummary = [
            'total_transactions' => Transaction::whereBetween('created_at', [$dateFrom, $dateTo])->count(),
            'successful_transactions' => Transaction::whereBetween('created_at', [$dateFrom, $dateTo])
                ->where('status', 'completed')->count(),
            'failed_transactions' => Transaction::whereBetween('created_at', [$dateFrom, $dateTo])
                ->where('status', 'failed')->count(),
            'total_successful_amount' => Transaction::whereBetween('created_at', [$dateFrom, $dateTo])
                ->where('status', 'completed')->sum('amount'),
        ];

        $transactionSummary['success_rate'] = $transactionSummary['total_transactions'] > 0
            ? round(($transactionSummary['successful_transactions'] / $transactionSummary['total_transactions']) * 100, 2)
            : 0;

        // Recent Activities
        $recentBudgets = BudgetAllocation::with('creator')
            ->latest()
            ->limit(5)
            ->get();

        $recentDisbursements = DisbursementBatch::with(['budgetAllocation', 'creator'])
            ->latest()
            ->limit(5)
            ->get();

        $recentTransactions = Transaction::with(['fundi', 'disbursementBatch'])
            ->latest()
            ->limit(10)
            ->get();

        $this->dashboardData = [
            'budget_summary' => $budgetSummary,
            'disbursement_summary' => $disbursementSummary,
            'transaction_summary' => $transactionSummary,
            'recent_budgets' => $recentBudgets,
            'recent_disbursements' => $recentDisbursements,
            'recent_transactions' => $recentTransactions,
            'period_from' => $dateFrom,
            'period_to' => $dateTo,
        ];

        $this->refreshing = false;
    }

    public function refreshDashboard()
    {
        $this->loadDashboardData();
        $this->showSuccessToast('Refreshed', 'Dashboard data updated successfully');
    }

    public function render()
    {
        return view('livewire.financial-dashboard');
    }
}

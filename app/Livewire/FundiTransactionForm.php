<?php

namespace App\Livewire;

use App\Models\Fundi;
use Livewire\Component;
use App\Models\Transaction;
use Livewire\WithPagination;

class FundiTransactionForm extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $fundi;
    public $transactionsPerPage = 10;
    public $siteVisitsPerPage = 10;
    public $activeTab = 'transactions';

    // Filters for transactions
    public $transaction_status = null;
    public $processed_date_from = null;
    public $processed_date_to = null;
    public $disbursement_batch_id = null;

    // Filters for site visits
    public $site_visit_status = null;
    public $site_visit_date_from = null;
    public $site_visit_date_to = null;

    public function mount(Fundi $fundi)
    {
        $this->fundi = $fundi;
    }

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
    }

    // Reset pagination when filters change
    public function updatedTransactionStatus()
    {
        $this->resetPage('transactions');
    }

    public function updatedProcessedDateFrom()
    {
        $this->resetPage('transactions');
    }

    public function updatedProcessedDateTo()
    {
        $this->resetPage('transactions');
    }

    public function updatedDisbursementBatchId()
    {
        $this->resetPage('transactions');
    }

    public function updatedSiteVisitStatus()
    {
        $this->resetPage('siteVisits');
    }

    public function updatedSiteVisitDateFrom()
    {
        $this->resetPage('siteVisits');
    }

    public function updatedSiteVisitDateTo()
    {
        $this->resetPage('siteVisits');
    }

    public function resetTransactionFilters()
    {
        $this->transaction_status = null;
        $this->processed_date_from = null;
        $this->processed_date_to = null;
        $this->disbursement_batch_id = null;
        $this->resetPage('transactions');
    }

    public function resetSiteVisitFilters()
    {
        $this->site_visit_status = null;
        $this->site_visit_date_from = null;
        $this->site_visit_date_to = null;
        $this->resetPage('siteVisits');
    }

    public function getTransactionsProperty()
    {
        return $this->fundi->transactions()
            ->with(['disbursementBatch', 'disbursementBatch.budgetAllocation'])
            ->when($this->transaction_status, function($query, $status) {
                return $query->where('status', $status);
            })
            ->when($this->processed_date_from, function($query, $date) {
                return $query->whereDate('processed_at', '>=', $date);
            })
            ->when($this->processed_date_to, function($query, $date) {
                return $query->whereDate('processed_at', '<=', $date);
            })
            ->when($this->disbursement_batch_id, function($query, $batchId) {
                return $query->where('disbursement_batch_id', $batchId);
            })
            ->latest('created_at')
            ->paginate($this->transactionsPerPage, ['*'], 'transactions');
    }

    public function getSiteVisitsProperty()
    {
        return $this->fundi->siteVisits()
            ->when($this->site_visit_status, function($query, $status) {
                return $query->where('status', $status);
            })
            ->when($this->site_visit_date_from, function($query, $date) {
                return $query->whereDate('visit_date', '>=', $date);
            })
            ->when($this->site_visit_date_to, function($query, $date) {
                return $query->whereDate('visit_date', '<=', $date);
            })
            ->latest('visit_date')
            ->paginate($this->siteVisitsPerPage, ['*'], 'siteVisits');
    }

    public function getDisbursementBatchesProperty()
    {
        // Get all disbursement batches that have transactions for this fundi
        return $this->fundi->transactions()
            ->with('disbursementBatch')
            ->get()
            ->pluck('disbursementBatch')
            ->unique('id')
            ->filter()
            ->sortByDesc('disbursement_date');
    }

    public function getTransactionSummaryProperty()
    {
        $transactions = $this->fundi->transactions();

        return [
            'total_received' => $transactions->where('status', 'completed')->sum('amount'),
            'total_pending' => $transactions->where('status', 'pending')->sum('amount'),
            'total_processing' => $transactions->where('status', 'processing')->sum('amount'),
            'total_failed' => $transactions->where('status', 'failed')->sum('amount'),
            'total_count' => $transactions->count(),
        ];
    }

    public function canViewTransactions()
    {
        $currentUser = currentUser();
        return $currentUser && in_array($currentUser->role->name, ['admin', 'root', 'finance', 'viewer']);
    }

    public function render()
    {
        return view('livewire.fundi-transaction-form', [
            'transactions' => $this->transactions,
            'siteVisits' => $this->siteVisits,
            'disbursementBatches' => $this->disbursementBatches,
            'transactionSummary' => $this->transactionSummary,
            'canViewTransactions' => $this->canViewTransactions(),
        ]);
    }
}

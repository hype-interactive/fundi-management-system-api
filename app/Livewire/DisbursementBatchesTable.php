<?php

namespace App\Livewire;

use App\Models\BudgetAllocation;
use App\Models\DisbursementBatch;
use App\Traits\HasAlerts;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class DisbursementBatchesTable extends Component
{
    use HasAlerts, WithPagination;

    protected $paginationTheme = 'bootstrap';

    #[Url]
    public $search_key = '';

    public $perPage = 10;

    #[Url]
    public $status = '';

    #[Url]
    public $budget_allocation_id = '';

    #[Url]
    public $created_by = '';

    #[Url]
    public $date_from = '';

    #[Url]
    public $date_to = '';

    public $showMobileFilters = false;

    public $compactView = false;

    public function mount()
    {
        $this->displayFlashedAlerts();
    }

    public function updatedSearchKey()
    {
        $this->resetPage();
    }

    public function updatedStatus()
    {
        $this->resetPage();
    }

    public function updatedBudgetAllocationId()
    {
        $this->resetPage();
    }

    public function getDisbursementQuery()
    {
        $query = DisbursementBatch::with(['budgetAllocation', 'creator', 'transactions']);

        return $query
            ->when($this->search_key, function ($query, $search_key) {
                $query->where('title', 'like', '%'.$search_key.'%')
                    ->orWhere('description', 'like', '%'.$search_key.'%');
            })
            ->when($this->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($this->budget_allocation_id, function ($query, $budget_allocation_id) {
                $query->where('budget_allocation_id', $budget_allocation_id);
            })
            ->when($this->created_by, function ($query, $created_by) {
                $query->where('created_by', $created_by);
            })
            ->when($this->date_from, function ($query, $date_from) {
                $query->whereDate('created_at', '>=', $date_from);
            })
            ->when($this->date_to, function ($query, $date_to) {
                $query->whereDate('created_at', '<=', $date_to);
            })
            ->latest('created_at');
    }

    public function fetch()
    {
        return $this->getDisbursementQuery()->paginate($this->perPage);
    }

    public function retryFailedBatch($batchId)
    {
        $result = $this->handleOperation(
            function () use ($batchId) {
                $batch = DisbursementBatch::findOrFail($batchId);

                // Logic to retry failed transactions
                // TODO: Add your retry logic here

                return true;
            },
            'Success',
            'Failed transactions are being retried.',
            'Error',
            'Failed to retry batch transactions.',
            true // Use toast for quick feedback
        );
    }

    public function render()
    {
        return view('livewire.disbursement-batches-table', [
            'batches' => $this->fetch(),
            'budgetAllocations' => $this->getBudgetAllocations(),
            'creators' => $this->getCreators(),
            'statusOptions' => $this->getStatusOptions(),
        ]);
    }

    private function getBudgetAllocations()
    {
        return BudgetAllocation::where('status', 'active')->get();
    }

    private function getCreators()
    {
        return \App\Models\User::whereIn('id', function ($query) {
            $query->select('created_by')
                ->from('disbursement_batches')
                ->whereNotNull('created_by')
                ->distinct();
        })->get();
    }

    private function getStatusOptions()
    {
        return [
            'pending' => 'Pending',
            'processing' => 'Processing',
            'completed' => 'Completed',
            'failed' => 'Failed',
            'partial' => 'Partial',
        ];
    }
}

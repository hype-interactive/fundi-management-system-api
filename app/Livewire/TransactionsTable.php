<?php

namespace App\Livewire;

use Exception;
use App\Models\Fundi;
use Livewire\Component;
use App\Traits\HasAlerts;
use App\Models\Transaction;
use Livewire\Attributes\Url;
use Livewire\WithPagination;
use App\Models\DisbursementBatch;

class TransactionsTable extends Component
{
    use WithPagination, HasAlerts;

    protected $paginationTheme = 'bootstrap';

    #[Url]
    public $search_key = '';

    public $perPage = 15;

    #[Url]
    public $status = '';

    #[Url]
    public $fundi_id = '';

    #[Url]
    public $batch_id = '';

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

    public function getTransactionQuery()
    {
        $query = Transaction::with(['fundi', 'disbursementBatch.budgetAllocation']);

        return $query
            ->when($this->search_key, function ($query, $search_key) {
                $query->where('transaction_reference', 'like', '%' . $search_key . '%')
                    ->orWhereHas('fundi', function ($q) use ($search_key) {
                        $q->whereRaw("concat(fname, ' ', lname) like ?", ['%' . $search_key . '%'])
                          ->orWhere('primary_phone', 'like', '%' . $search_key . '%');
                    });
            })
            ->when($this->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($this->fundi_id, function ($query, $fundi_id) {
                $query->where('fundi_id', $fundi_id);
            })
            ->when($this->batch_id, function ($query, $batch_id) {
                $query->where('disbursement_batch_id', $batch_id);
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
        return $this->getTransactionQuery()->paginate($this->perPage);
    }

    public function retryTransaction($transactionId)
    {
        try {
            $transaction = Transaction::findOrFail($transactionId);

            if ($transaction->status !== 'failed') {
                $this->showWarningAlert('Warning', 'Only failed transactions can be retried.');
                return;
            }

            // Logic to retry transaction
            $this->showSuccessAlert('Success', 'Transaction has been marked for retry.');
        } catch (Exception $e) {
            $this->showErrorAlert('Error', 'Failed to retry transaction.');
        }
    }

    public function render()
    {
        return view('livewire.transactions-table', [
            'transactions' => $this->fetch(),
            'fundis' => $this->getFundis(),
            'batches' => $this->getBatches(),
            'statusOptions' => $this->getStatusOptions(),
        ]);
    }

    private function getFundis()
    {
        return Fundi::whereIn('id', function ($query) {
            $query->select('fundi_id')
                ->from('transactions')
                ->whereNotNull('fundi_id')
                ->distinct();
        })->limit(100)->get();
    }

    private function getBatches()
    {
        return DisbursementBatch::whereIn('id', function ($query) {
            $query->select('disbursement_batch_id')
                ->from('transactions')
                ->whereNotNull('disbursement_batch_id')
                ->distinct();
        })->limit(50)->get();
    }

    private function getStatusOptions()
    {
        return [
            'pending' => 'Pending',
            'processing' => 'Processing',
            'sent' => 'Sent',
            'completed' => 'Completed',
            'failed' => 'Failed'
        ];
    }
}

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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

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

    // Bulk action properties
    public $selectedTransactions = [];
    public $selectAll = false;

    protected $listeners = [
        'refreshTransactions' => '$refresh',
        'transactionUpdated' => 'handleTransactionUpdate',
    ];

    public function mount()
    {
        // Display any flashed alerts using trait method
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

    public function updatedFundiId()
    {
        $this->resetPage();
    }

    public function updatedBatchId()
    {
        $this->resetPage();
    }

    public function updatedDateFrom()
    {
        $this->resetPage();
    }

    public function updatedDateTo()
    {
        $this->resetPage();
    }

    /**
     * Build the transaction query with filters
     */
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

    /**
     * Fetch paginated transactions
     */
    public function fetch()
    {
        return $this->getTransactionQuery()->paginate($this->perPage);
    }

    /**
     * Clear all filters
     */
    public function clearFilters()
    {
        $this->reset([
            'search_key',
            'status',
            'fundi_id',
            'batch_id',
            'date_from',
            'date_to'
        ]);

        $this->resetPage();

        // Using trait method
        $this->showInfoToast('Filters Cleared', 'All filters have been reset.');
    }

    /**
     * Toggle mobile filters visibility
     */
    public function toggleMobileFilters()
    {
        $this->showMobileFilters = !$this->showMobileFilters;
    }

    /**
     * Toggle compact view
     */
    public function toggleCompactView()
    {
        $this->compactView = !$this->compactView;

        // Using trait method
        $this->showInfoToast(
            $this->compactView ? 'Compact View' : 'Normal View',
            'View mode changed successfully.'
        );
    }

    /**
     * Retry a failed transaction
     */
    public function retryTransaction($transactionId)
    {
        // Using trait method for operation handling
        $this->handleOperation(
            function () use ($transactionId) {
                $transaction = Transaction::findOrFail($transactionId);

                if ($transaction->status !== 'failed') {
                    // Using trait method
                    $this->showWarningAlert('Cannot Retry', 'Only failed transactions can be retried.');
                    return false;
                }

                // Update transaction status to pending for retry
                $transaction->update([
                    'status' => 'pending',
                    'retry_count' => ($transaction->retry_count ?? 0) + 1,
                    'last_retry_at' => now(),
                ]);

                Log::info('Transaction marked for retry', [
                    'transaction_id' => $transactionId,
                    'retry_count' => $transaction->retry_count
                ]);

                return true;
            },
            'Retry Scheduled',
            'Transaction has been marked for retry and will be processed shortly.',
            'Retry Failed',
            'Failed to schedule transaction for retry. Please try again.',
            true // Use toast
        );
    }

    /**
     * Cancel a pending transaction
     */
    public function cancelTransaction($transactionId)
    {
        // Using trait method for confirmation
        $this->showConfirmation(
            'Cancel Transaction',
            'Are you sure you want to cancel this transaction? This action cannot be undone.',
            'confirmCancelTransaction',
            ['transactionId' => $transactionId],
            '',
            [],
            'Yes, cancel it',
            'No, keep it'
        );
    }

    /**
     * Confirm transaction cancellation
     */
    public function confirmCancelTransaction($transactionId)
    {
        // Using trait method for operation handling
        $this->handleOperation(
            function () use ($transactionId) {
                $transaction = Transaction::findOrFail($transactionId);

                if (!in_array($transaction->status, ['pending', 'processing'])) {
                    // Using trait method
                    $this->showWarningAlert('Cannot Cancel', 'Only pending or processing transactions can be cancelled.');
                    return false;
                }

                $transaction->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                    'cancelled_by' => Auth::id(),
                ]);

                Log::info('Transaction cancelled', [
                    'transaction_id' => $transactionId,
                    'cancelled_by' => Auth::id()
                ]);

                return true;
            },
            'Transaction Cancelled',
            'The transaction has been cancelled successfully.',
            'Cancellation Failed',
            'Failed to cancel the transaction. Please try again.',
            true // Use toast
        );
    }

    /**
     * View transaction details
     */
    public function viewTransaction($transactionId)
    {
        try {
            $transaction = Transaction::with(['fundi', 'disbursementBatch'])->findOrFail($transactionId);

            // Emit event to open transaction details modal
            $this->dispatch('openTransactionDetails', transactionId: $transactionId);

        } catch (Exception $e) {
            // Using trait method
            $this->showErrorToast('Error', 'Failed to load transaction details.');
            Log::error('Failed to view transaction: ' . $e->getMessage());
        }
    }

    /**
     * Export transactions to CSV
     */
    public function exportTransactions()
    {
        try {
            $transactions = $this->getTransactionQuery()->get();

            if ($transactions->isEmpty()) {
                // Using trait method
                $this->showWarningAlert('No Data', 'No transactions found to export.');
                return;
            }

            // Show loading alert using trait method
            $this->showLoadingToast('Exporting', 'Preparing your export file...');

            // Trigger export job or direct download
            $this->dispatch('downloadTransactionsExport', transactions: $transactions->pluck('id')->toArray());

            // Using trait method
            $this->showSuccessToast('Export Ready', 'Your export is being prepared.');

        } catch (Exception $e) {
            // Using trait method
            $this->showErrorAlert('Export Failed', 'Failed to export transactions. Please try again.');
            Log::error('Transaction export failed: ' . $e->getMessage());
        }
    }

    /**
     * Toggle transaction selection
     */
    public function toggleTransaction($transactionId)
    {
        if (in_array($transactionId, $this->selectedTransactions)) {
            $this->selectedTransactions = array_diff($this->selectedTransactions, [$transactionId]);
        } else {
            $this->selectedTransactions[] = $transactionId;
        }
    }

    /**
     * Toggle select all transactions
     */
    public function toggleSelectAll()
    {
        if ($this->selectAll) {
            $this->selectedTransactions = $this->getTransactionQuery()
                ->pluck('id')
                ->toArray();
        } else {
            $this->selectedTransactions = [];
        }
    }

    /**
     * Bulk retry selected failed transactions
     */
    public function bulkRetryTransactions()
    {
        if (empty($this->selectedTransactions)) {
            // Using trait method
            $this->showWarningAlert('No Selection', 'Please select transactions to retry.');
            return;
        }

        $count = count($this->selectedTransactions);

        // Using trait method for bulk confirmation
        $this->showConfirmation(
            'Retry Multiple Transactions',
            "You are about to retry {$count} selected transaction(s). Continue?",
            'confirmBulkRetry',
            [],
            '',
            [],
            "Yes, retry {$count} transaction(s)",
            'Cancel'
        );
    }

    /**
     * Confirm bulk retry
     */
    public function confirmBulkRetry()
    {
        // Using trait method for operation handling
        $this->handleOperation(
            function () {
                $count = Transaction::whereIn('id', $this->selectedTransactions)
                    ->where('status', 'failed')
                    ->update([
                        'status' => 'pending',
                        'retry_count' => DB::raw('COALESCE(retry_count, 0) + 1'),
                        'last_retry_at' => now(),
                    ]);

                if ($count === 0) {
                    // Using trait method
                    $this->showWarningAlert('No Failed Transactions', 'None of the selected transactions are in failed status.');
                    return false;
                }

                Log::info('Bulk transaction retry', [
                    'count' => $count,
                    'user_id' => Auth::id()
                ]);

                // Clear selection
                $this->selectedTransactions = [];
                $this->selectAll = false;

                return true;
            },
            'Retry Scheduled',
            count($this->selectedTransactions) . ' transaction(s) have been marked for retry.',
            'Retry Failed',
            'Failed to schedule transactions for retry. Please try again.'
        );
    }

    /**
     * Bulk cancel selected transactions
     */
    public function bulkCancelTransactions()
    {
        if (empty($this->selectedTransactions)) {
            // Using trait method
            $this->showWarningAlert('No Selection', 'Please select transactions to cancel.');
            return;
        }

        $count = count($this->selectedTransactions);

        // Using trait method for bulk delete confirmation
        $this->showBulkDeleteConfirmation(
            $count,
            'confirmBulkCancel',
            []
        );
    }

    /**
     * Confirm bulk cancel
     */
    public function confirmBulkCancel()
    {
        // Using trait method for operation handling
        $this->handleOperation(
            function () {
                $count = Transaction::whereIn('id', $this->selectedTransactions)
                    ->whereIn('status', ['pending', 'processing'])
                    ->update([
                        'status' => 'cancelled',
                        'cancelled_at' => now(),
                        'cancelled_by' => Auth::id(),
                    ]);

                if ($count === 0) {
                    // Using trait method
                    $this->showWarningAlert('Cannot Cancel', 'None of the selected transactions can be cancelled.');
                    return false;
                }

                Log::info('Bulk transaction cancellation', [
                    'count' => $count,
                    'user_id' => Auth::id()
                ]);

                // Clear selection
                $this->selectedTransactions = [];
                $this->selectAll = false;

                return true;
            },
            'Transactions Cancelled',
            count($this->selectedTransactions) . ' transaction(s) have been cancelled successfully.',
            'Cancellation Failed',
            'Failed to cancel transactions. Please try again.'
        );
    }

    /**
     * Handle transaction update event
     */
    public function handleTransactionUpdate($message = null)
    {
        // Refresh the component
        $this->render();

        if ($message) {
            // Using trait method
            $this->showSuccessToast('Updated', $message);
        }
    }

    /**
     * Refresh transactions manually
     */
    public function refreshTransactions()
    {
        // Using trait method
        $this->showInfoToast('Refreshed', 'Transaction list updated.');
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

    /**
     * Get fundis that have transactions
     */
    private function getFundis()
    {
        return Fundi::whereIn('id', function ($query) {
            $query->select('fundi_id')
                ->from('transactions')
                ->whereNotNull('fundi_id')
                ->distinct();
        })
        ->orderBy('fname')
        ->limit(100)
        ->get();
    }

    /**
     * Get batches that have transactions
     */
    private function getBatches()
    {
        return DisbursementBatch::whereIn('id', function ($query) {
            $query->select('disbursement_batch_id')
                ->from('transactions')
                ->whereNotNull('disbursement_batch_id')
                ->distinct();
        })
        ->orderBy('created_at', 'desc')
        ->limit(50)
        ->get();
    }

    /**
     * Get available status options
     */
    private function getStatusOptions()
    {
        return [
            'pending' => 'Pending',
            'processing' => 'Processing',
            'sent' => 'Sent',
            'completed' => 'Completed',
            'failed' => 'Failed',
            'cancelled' => 'Cancelled'
        ];
    }
}

<?php

namespace App\Services\Financial;

use App\Jobs\ProcessDisbursementJob;
use App\Models\BudgetAllocation;
use App\Models\DisbursementBatch;
use App\Models\Transaction;
use App\Services\Payment\SelcomService;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FinancialManagementService
{
    protected SelcomService $selcomService;

    public function __construct(SelcomService $selcomService)
    {
        $this->selcomService = $selcomService;
    }

    /**
     * Process disbursement batch with balance check and async processing
     */
    public function processDisbursementBatch(int $budgetAllocationId, array $data): DisbursementBatch
    {
        return DB::transaction(function () use ($budgetAllocationId, $data) {

            Log::info('=== PROCESSING DISBURSEMENT BATCH ===', [
                'budget_allocation_id' => $budgetAllocationId,
                'total_amount' => $data['total_amount'],
                'recipients_count' => count($data['recipients']),
            ]);

            // 1. Validate budget allocation
            $budgetAllocation = BudgetAllocation::lockForUpdate()->findOrFail($budgetAllocationId);

            if ($budgetAllocation->status !== 'active') {
                throw new Exception('Budget allocation is not active');
            }

            if ($budgetAllocation->remaining_amount < $data['total_amount']) {
                throw new Exception('Insufficient budget remaining');
            }

            // 2. Check SELCOM balance
            $balanceCheck = $this->selcomService->checkBalance();

            if (! $balanceCheck['success']) {
                throw new Exception('Unable to verify payment provider balance: '.($balanceCheck['error'] ?? 'Unknown error'));
            }

            $providerBalance = $balanceCheck['balance'];

            Log::info('SELCOM balance check', [
                'provider_balance' => $providerBalance,
                'required_amount' => $data['total_amount'],
            ]);

            if ($providerBalance < $data['total_amount']) {
                throw new Exception(sprintf(
                    'Insufficient provider balance. Required: %s, Available: %s',
                    number_format($data['total_amount']),
                    number_format($providerBalance)
                ));
            }

            // 3. Create disbursement batch
            $batch = DisbursementBatch::create([
                'budget_allocation_id' => $budgetAllocationId,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'total_amount' => $data['total_amount'],
                'disbursement_date' => $data['disbursement_date'],
                'status' => 'pending',
                'created_by' => Auth::id(),
            ]);

            Log::info('Disbursement batch created', [
                'batch_id' => $batch->id,
            ]);

            // 4. Create transaction records
            $transactions = [];
            foreach ($data['recipients'] as $recipient) {
                $transaction = Transaction::create([
                    'disbursement_batch_id' => $batch->id,
                    'fundi_id' => $recipient['fundi_id'],
                    'amount' => $recipient['amount'],
                    'status' => 'pending',
                ]);

                $transactions[] = $transaction;
            }

            Log::info('Transactions created', [
                'batch_id' => $batch->id,
                'transaction_count' => count($transactions),
            ]);

            // 5. Update budget allocation
            $budgetAllocation->decrement('remaining_amount', $data['total_amount']);

            Log::info('Budget allocation updated', [
                'budget_allocation_id' => $budgetAllocation->id,
                'new_remaining_amount' => $budgetAllocation->fresh()->remaining_amount,
            ]);

            // 6. Dispatch async jobs to process disbursements
            $this->dispatchDisbursementJobs($transactions);

            // 7. Update batch status to processing
            $batch->update(['status' => 'processing']);

            Log::info('=== DISBURSEMENT BATCH PROCESSING INITIATED ===', [
                'batch_id' => $batch->id,
                'jobs_dispatched' => count($transactions),
            ]);

            return $batch;
        });
    }

    /**
     * Dispatch jobs to process individual disbursements
     */
    protected function dispatchDisbursementJobs(array $transactions): void
    {
        foreach ($transactions as $index => $transaction) {
            // Stagger job dispatch to avoid overwhelming the API
            $delay = now()->addSeconds($index * 5); // 5 seconds between each

            ProcessDisbursementJob::dispatch($transaction)
                ->delay($delay)
                ->onQueue('disbursements');

            Log::info('Disbursement job dispatched', [
                'transaction_id' => $transaction->id,
                'delay_seconds' => $index * 5,
            ]);
        }
    }

    /**
     * Get disbursement batch statistics
     */
    public function getBatchStatistics(DisbursementBatch $batch): array
    {
        $transactions = $batch->transactions;

        return [
            'total_count' => $transactions->count(),
            'completed_count' => $transactions->where('status', 'completed')->count(),
            'failed_count' => $transactions->where('status', 'failed')->count(),
            'pending_count' => $transactions->whereIn('status', ['pending', 'processing'])->count(),
            'total_amount' => $batch->total_amount,
            'completed_amount' => $transactions->where('status', 'completed')->sum('amount'),
            'failed_amount' => $transactions->where('status', 'failed')->sum('amount'),
        ];
    }

    /**
     * Retry failed transactions in a batch
     */
    public function retryFailedTransactions(DisbursementBatch $batch): int
    {
        $failedTransactions = $batch->transactions()
            ->where('status', 'failed')
            ->get();

        if ($failedTransactions->isEmpty()) {
            return 0;
        }

        Log::info('Retrying failed transactions', [
            'batch_id' => $batch->id,
            'failed_count' => $failedTransactions->count(),
        ]);

        // Check SELCOM balance before retrying
        $balanceCheck = $this->selcomService->checkBalance();

        if (! $balanceCheck['success']) {
            throw new Exception('Unable to verify provider balance before retry');
        }

        $requiredAmount = $failedTransactions->sum('amount');

        if ($balanceCheck['balance'] < $requiredAmount) {
            throw new Exception('Insufficient provider balance for retry');
        }

        // Reset transactions and dispatch jobs
        foreach ($failedTransactions as $transaction) {
            $transaction->update([
                'status' => 'pending',
                'failure_reason' => null,
            ]);
        }

        $this->dispatchDisbursementJobs($failedTransactions->all());

        // Update batch status
        $batch->update(['status' => 'processing']);

        return $failedTransactions->count();
    }

    /**
     * Cancel pending batch
     */
    public function cancelBatch(DisbursementBatch $batch): bool
    {
        if (! in_array($batch->status, ['pending', 'processing'])) {
            throw new Exception('Only pending or processing batches can be cancelled');
        }

        return DB::transaction(function () use ($batch) {
            // Cancel pending transactions
            $batch->transactions()
                ->whereIn('status', ['pending'])
                ->update([
                    'status' => 'cancelled',
                    'failure_reason' => 'Batch cancelled by user',
                ]);

            // Refund budget allocation for cancelled transactions
            $cancelledAmount = $batch->transactions()
                ->where('status', 'cancelled')
                ->sum('amount');

            if ($cancelledAmount > 0) {
                $batch->budgetAllocation->increment('remaining_amount', $cancelledAmount);

                Log::info('Budget refunded for cancelled transactions', [
                    'batch_id' => $batch->id,
                    'refunded_amount' => $cancelledAmount,
                ]);
            }

            // Update batch status
            $batch->update(['status' => 'cancelled']);

            return true;
        });
    }
}

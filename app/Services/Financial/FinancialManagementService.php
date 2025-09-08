<?php

namespace App\Services\Financial;

use Exception;
use Carbon\Carbon;
use App\Models\Fundi;
use App\Models\Transaction;
use App\Models\BudgetAllocation;
use App\Models\DisbursementBatch;
use Illuminate\Support\Facades\DB;
use App\Exceptions\SelcomException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Services\Payment\Selcom\FinancialSelcomService;

class FinancialManagementService
{
    private FinancialSelcomService $selcomService;

    public function __construct(FinancialSelcomService $selcomService)
    {
        $this->selcomService = $selcomService;
    }

    /**
     * Create a new budget allocation
     */
    public function createBudgetAllocation(array $data): BudgetAllocation
    {
        return BudgetAllocation::create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'total_amount' => $data['total_amount'],
            'remaining_amount' => $data['total_amount'],
            'created_by' => Auth::id(),
            'status' => 'active',
        ]);
    }

    /**
     * Create and process a disbursement batch
     */
    public function processDisbursementBatch(int $budgetAllocationId, array $data): DisbursementBatch
    {
        return DB::transaction(function () use ($budgetAllocationId, $data) {
            $budget = BudgetAllocation::findOrFail($budgetAllocationId);

            // Validate budget has sufficient funds
            if ($data['total_amount'] > $budget->remaining_amount) {
                throw new Exception('Insufficient budget remaining for this disbursement');
            }

            // Create disbursement batch
            $batch = DisbursementBatch::create([
                'budget_allocation_id' => $budgetAllocationId,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'total_amount' => $data['total_amount'],
                'disbursement_date' => $data['disbursement_date'] ?? Carbon::now(),
                'status' => 'pending',
                'created_by' => Auth::id(),
            ]);

            // Create individual transactions
            foreach ($data['recipients'] as $recipient) {
                Transaction::create([
                    'disbursement_batch_id' => $batch->id,
                    'fundi_id' => $recipient['fundi_id'],
                    'amount' => $recipient['amount'],
                    'transaction_reference' => $this->generateTransactionReference(),
                    'status' => 'pending',
                ]);
            }

            // Update budget remaining amount
            $budget->decrement('remaining_amount', $data['total_amount']);

            // Process transactions asynchronously
            dispatch(function () use ($batch) {
                $this->processTransactions($batch);
            })->delay(now()->addSeconds(10));

            return $batch;
        });
    }

    /**
     * Process transactions in a batch
     */
    public function processTransactions(DisbursementBatch $batch): void
    {
        $batch->update(['status' => 'processing']);

        $transactions = $batch->transactions()->where('status', 'pending')->get();

        foreach ($transactions as $transaction) {
            try {
                $this->processTransaction($transaction);
            } catch (Exception $e) {
                Log::error('Transaction processing failed', [
                    'transaction_id' => $transaction->id,
                    'error' => $e->getMessage(),
                ]);

                $transaction->update([
                    'status' => 'failed',
                    'failure_reason' => $e->getMessage(),
                ]);
            }
        }

        // Update batch status
        $this->updateBatchStatus($batch);
    }

    /**
     * Process individual transaction
     */
    private function processTransaction(Transaction $transaction): void
    {
        $transaction->update(['status' => 'processing']);

        try {
            // Validate fundi is eligible for disbursement
            $validation = $this->selcomService->validateFundiForDisbursement($transaction->fundi);

            if (!$validation['valid']) {
                $issues = array_keys(array_filter($validation['validation'], fn($v) => !$v));
                throw new Exception('Fundi validation failed: ' . implode(', ', $issues));
            }

            // Process payment through Selcom
            $response = $this->selcomService->processCashin($transaction);

            if ($response->isSuccessful()) {
                $transaction->update([
                    'status' => 'sent',
                    'mno_response' => json_encode($response->getData()),
                    'processed_at' => Carbon::now(),
                ]);

                Log::info('Transaction sent successfully', [
                    'transaction_id' => $transaction->id,
                    'selcom_reference' => $response->getReference(),
                ]);
            } else {
                throw new Exception($response->getMessage());
            }

        } catch (SelcomException $e) {
            $transaction->update([
                'status' => 'failed',
                'failure_reason' => $e->getMessage(),
                'mno_response' => json_encode(['error' => $e->getMessage()]),
            ]);

            throw $e;
        }
    }

    /**
     * Handle payment callback from Selcom
     */
    public function handlePaymentCallback(array $callbackData): void
    {
        $transactionRef = $callbackData['transid'] ?? null;
        $result = $callbackData['result'] ?? 'UNKNOWN';

        if (!$transactionRef) {
            Log::warning('Payment callback received without transaction reference', $callbackData);
            return;
        }

        $transaction = Transaction::where('transaction_reference', $transactionRef)->first();

        if (!$transaction) {
            Log::warning('Payment callback for unknown transaction', [
                'transaction_ref' => $transactionRef,
                'callback_data' => $callbackData,
            ]);
            return;
        }

        DB::transaction(function () use ($transaction, $callbackData, $result) {
            if ($result === 'SUCCESS' && ($callbackData['resultcode'] ?? null) === '000') {
                $transaction->update([
                    'status' => 'completed',
                    'mno_response' => json_encode($callbackData),
                    'processed_at' => Carbon::now(),
                ]);

                Log::info('Transaction completed successfully', [
                    'transaction_id' => $transaction->id,
                    'amount' => $transaction->amount,
                    'fundi_id' => $transaction->fundi_id,
                ]);
            } else {
                $transaction->update([
                    'status' => 'failed',
                    'failure_reason' => $callbackData['message'] ?? 'Payment failed',
                    'mno_response' => json_encode($callbackData),
                ]);

                // Release funds back to budget
                $batch = $transaction->disbursementBatch;
                $batch->budgetAllocation->increment('remaining_amount', $transaction->amount);

                Log::warning('Transaction failed', [
                    'transaction_id' => $transaction->id,
                    'failure_reason' => $callbackData['message'] ?? 'Unknown',
                ]);
            }

            // Update batch status
            $this->updateBatchStatus($transaction->disbursementBatch);
        });
    }

    /**
     * Update batch status based on transaction results
     */
    private function updateBatchStatus(DisbursementBatch $batch): void
    {
        $transactions = $batch->transactions;

        $completed = $transactions->where('status', 'completed')->count();
        $failed = $transactions->where('status', 'failed')->count();
        $pending = $transactions->whereIn('status', ['pending', 'processing', 'sent'])->count();

        if ($pending > 0) {
            $status = 'processing';
        } elseif ($failed === 0) {
            $status = 'completed';
        } elseif ($completed === 0) {
            $status = 'failed';
        } else {
            $status = 'partial';
        }

        $batch->update(['status' => $status]);
    }

    /**
     * Generate unique transaction reference
     */
    private function generateTransactionReference(): string
    {
        return 'TXN_' . strtoupper(uniqid()) . '_' . time();
    }

    /**
     * Get dashboard summary
     */
    public function getDashboardSummary(): array
    {
        // Budget Summary
        $budgetSummary = [
            'total_budgets' => BudgetAllocation::where('status', 'active')->count(),
            'total_allocated' => BudgetAllocation::where('status', 'active')->sum('total_amount'),
            'total_remaining' => BudgetAllocation::where('status', 'active')->sum('remaining_amount'),
        ];

        $budgetSummary['utilization_rate'] = $budgetSummary['total_allocated'] > 0
            ? round((($budgetSummary['total_allocated'] - $budgetSummary['total_remaining']) / $budgetSummary['total_allocated']) * 100, 2)
            : 0;

        // Transaction Summary
        $transactionSummary = [
            'total_transactions' => Transaction::count(),
            'completed_transactions' => Transaction::where('status', 'completed')->count(),
            'failed_transactions' => Transaction::where('status', 'failed')->count(),
            'total_successful_amount' => Transaction::where('status', 'completed')->sum('amount'),
        ];

        $transactionSummary['success_rate'] = $transactionSummary['total_transactions'] > 0
            ? round(($transactionSummary['completed_transactions'] / $transactionSummary['total_transactions']) * 100, 2)
            : 0;

        // Recent Activities
        $recentTransactions = Transaction::with(['fundi', 'disbursementBatch'])
            ->latest()
            ->limit(10)
            ->get()
            ->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'reference' => $transaction->transaction_reference,
                    'amount' => $transaction->amount,
                    'status' => $transaction->status,
                    'fundi_name' => $transaction->fundi->full_name ?? 'Unknown',
                    'fundi_mobile' => $transaction->fundi->primary_phone ?? 'Unknown',
                    'batch_title' => $transaction->disbursementBatch->title,
                    'created_at' => $transaction->created_at,
                    'processed_at' => $transaction->processed_at,
                ];
            })
            ->toArray();

        return [
            'budget_summary' => $budgetSummary,
            'transaction_summary' => $transactionSummary,
            'recent_transactions' => $recentTransactions,
        ];
    }

    /**
     * Retry failed transaction
     */
    public function retryTransaction(Transaction $transaction): void
    {
        if ($transaction->status !== 'failed') {
            throw new Exception('Only failed transactions can be retried');
        }

        $transaction->update([
            'status' => 'pending',
            'failure_reason' => null,
            'mno_response' => null,
        ]);

        // Process the transaction
        $this->processTransaction($transaction);
    }
}

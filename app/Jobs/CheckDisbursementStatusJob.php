<?php

namespace App\Jobs;

use App\Models\Transaction;
use App\Services\Payment\SelcomService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckDisbursementStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 5;
    public $timeout = 60;

    protected Transaction $transaction;
    protected int $checkCount;

    /**
     * Create a new job instance.
     */
    public function __construct(Transaction $transaction, int $checkCount = 1)
    {
        $this->transaction = $transaction;
        $this->checkCount = $checkCount;
    }

    /**
     * Execute the job.
     */
    public function handle(SelcomService $selcomService): void
    {
        Log::info('Checking disbursement status', [
            'transaction_id' => $this->transaction->id,
            'check_count' => $this->checkCount,
            'current_status' => $this->transaction->status,
        ]);

        // Only check if still pending
        if (!in_array($this->transaction->status, ['pending', 'processing'])) {
            Log::info('Transaction not in checkable status, skipping', [
                'transaction_id' => $this->transaction->id,
                'status' => $this->transaction->status,
            ]);
            return;
        }

        // Query SELCOM for status
        $result = $selcomService->queryDisbursement($this->transaction->transaction_reference);

        if (!$result['success']) {
            Log::warning('Status check failed', [
                'transaction_id' => $this->transaction->id,
                'check_count' => $this->checkCount,
            ]);

            // Schedule another check if not max attempts
            if ($this->checkCount < 5) {
                $delay = $this->calculateDelay($this->checkCount);
                self::dispatch($this->transaction, $this->checkCount + 1)
                    ->delay($delay);
            }
            return;
        }

        $status = $result['status'];

        Log::info('Status check result', [
            'transaction_id' => $this->transaction->id,
            'new_status' => $status,
        ]);

        // Update transaction
        $this->transaction->update([
            'status' => $status,
            'mno_response' => json_encode($result['response']),
        ]);

        // If still pending and not max checks, schedule another check
        if ($status === 'pending' && $this->checkCount < 5) {
            $delay = $this->calculateDelay($this->checkCount);
            self::dispatch($this->transaction, $this->checkCount + 1)
                ->delay($delay);
        }

        // Update batch status
        $this->updateBatchStatus();
    }

    /**
     * Calculate delay for next check
     */
    protected function calculateDelay(int $checkCount): \DateTimeInterface
    {
        // Progressive delays: 2min, 5min, 10min, 30min
        $minutes = match($checkCount) {
            1 => 2,
            2 => 5,
            3 => 10,
            default => 30,
        };

        return now()->addMinutes($minutes);
    }

    /**
     * Update disbursement batch status based on transactions
     */
    protected function updateBatchStatus(): void
    {
        $batch = $this->transaction->disbursementBatch;

        if (!$batch) {
            return;
        }

        $transactions = $batch->transactions;
        $totalCount = $transactions->count();
        $completedCount = $transactions->where('status', 'completed')->count();
        $failedCount = $transactions->where('status', 'failed')->count();
        $pendingCount = $transactions->whereIn('status', ['pending', 'processing'])->count();

        // Determine batch status
        $newStatus = 'processing';

        if ($completedCount === $totalCount) {
            $newStatus = 'completed';
        } elseif ($failedCount === $totalCount) {
            $newStatus = 'failed';
        } elseif ($completedCount + $failedCount === $totalCount) {
            $newStatus = 'partially_completed';
        } elseif ($pendingCount === 0 && ($completedCount > 0 || $failedCount > 0)) {
            $newStatus = 'partially_completed';
        }

        Log::info('Updating batch status', [
            'batch_id' => $batch->id,
            'old_status' => $batch->status,
            'new_status' => $newStatus,
            'completed' => $completedCount,
            'failed' => $failedCount,
            'pending' => $pendingCount,
            'total' => $totalCount,
        ]);

        $batch->update(['status' => $newStatus]);
    }
}

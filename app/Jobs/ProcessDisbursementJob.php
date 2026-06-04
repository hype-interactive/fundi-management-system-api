<?php

namespace App\Jobs;

use App\Models\Transaction;
use App\Services\Payment\SelcomService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessDisbursementJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $timeout = 120;

    public $backoff = [60, 300, 900]; // 1min, 5min, 15min

    protected Transaction $transaction;

    /**
     * Create a new job instance.
     */
    public function __construct(Transaction $transaction)
    {
        $this->transaction = $transaction;
    }

    /**
     * Execute the job.
     */
    public function handle(SelcomService $selcomService): void
    {
        try {
            Log::info('Processing disbursement job', [
                'transaction_id' => $this->transaction->id,
                'attempt' => $this->attempts(),
            ]);

            // Update status to processing
            $this->transaction->update([
                'status' => 'processing',
            ]);

            // Initiate disbursement with SELCOM
            $result = $selcomService->initiateDisbursement($this->transaction);

            if (! $result['success']) {
                throw new Exception($result['error'] ?? 'Disbursement failed');
            }

            // Update transaction with SELCOM response
            $this->transaction->update([
                'status' => 'pending', // Will be updated by webhook/status check
                'transaction_reference' => $result['reference'],
                'mno_response' => json_encode($result['response']),
                'processed_at' => now(),
            ]);

            Log::info('Disbursement job completed successfully', [
                'transaction_id' => $this->transaction->id,
                'reference' => $result['reference'],
            ]);

            // Dispatch status check job after 2 minutes
            CheckDisbursementStatusJob::dispatch($this->transaction)
                ->delay(now()->addMinutes(2));

        } catch (Exception $e) {
            Log::error('Disbursement job failed', [
                'transaction_id' => $this->transaction->id,
                'attempt' => $this->attempts(),
                'error' => $e->getMessage(),
            ]);

            // Update transaction with failure
            $this->transaction->update([
                'status' => 'failed',
                'failure_reason' => $e->getMessage(),
            ]);

            // If max attempts reached, don't retry
            if ($this->attempts() >= $this->tries) {
                Log::error('Disbursement job max attempts reached', [
                    'transaction_id' => $this->transaction->id,
                ]);

                // Notify admin or dispatch notification
                return;
            }

            // Re-throw to trigger retry
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Exception $exception): void
    {
        Log::error('Disbursement job permanently failed', [
            'transaction_id' => $this->transaction->id,
            'error' => $exception->getMessage(),
        ]);

        $this->transaction->update([
            'status' => 'failed',
            'failure_reason' => 'Max retries exceeded: '.$exception->getMessage(),
        ]);
    }
}

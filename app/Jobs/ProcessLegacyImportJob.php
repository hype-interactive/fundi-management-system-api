<?php

namespace App\Jobs;

use App\Models\LegacyFundi;
use App\Models\User;
use App\Imports\LegacyFundiImport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ProcessLegacyImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600; // 1 hour timeout
    public $tries = 3;

    protected $filePath;
    protected $userId;
    protected $autoMatch;
    protected $jobId;

    /**
     * Create a new job instance.
     */
    public function __construct(string $filePath, int $userId, bool $autoMatch = false, string $jobId = null)
    {
        $this->filePath = $filePath;
        $this->userId = $userId;
        $this->autoMatch = $autoMatch;
        $this->jobId = $jobId ?? uniqid('import_');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $this->updateProgress(0, 'Starting import...');

            DB::beginTransaction();

            // Import the data
            $this->updateProgress(10, 'Processing file...');
            $import = new LegacyFundiImport();
            Excel::import($import, Storage::path($this->filePath));

            $stats = $import->getStats();
            $this->updateProgress(60, 'Import completed. Processing matches...');

            // Run auto-matching if requested
            $matchedCount = 0;
            if ($this->autoMatch && $stats['imported'] > 0) {
                $matchedCount = $this->runBulkAutoMatch();
                $this->updateProgress(90, 'Auto-matching completed.');
            }

            DB::commit();

            // Final update
            $message = "Successfully imported {$stats['imported']} records.";
            if ($stats['skipped'] > 0) {
                $message .= " Skipped {$stats['skipped']} duplicates.";
            }
            if ($matchedCount > 0) {
                $message .= " Auto-matched {$matchedCount} records.";
            }

            $this->updateProgress(100, $message, 'completed', $stats);

            // Clean up the uploaded file
            Storage::delete($this->filePath);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Legacy import job failed', [
                'job_id' => $this->jobId,
                'user_id' => $this->userId,
                'error' => $e->getMessage(),
                'file' => $this->filePath
            ]);

            $this->updateProgress(0, 'Import failed: ' . $e->getMessage(), 'failed');

            // Clean up the uploaded file
            Storage::delete($this->filePath);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        $this->updateProgress(0, 'Import failed: ' . $exception->getMessage(), 'failed');

        // Clean up the uploaded file
        Storage::delete($this->filePath);
    }

    /**
     * Update job progress in cache
     */
    private function updateProgress(int $percentage, string $message, string $status = 'processing', array $stats = []): void
    {
        $progress = [
            'job_id' => $this->jobId,
            'user_id' => $this->userId,
            'percentage' => $percentage,
            'message' => $message,
            'status' => $status, // processing, completed, failed
            'stats' => $stats,
            'updated_at' => now()->toISOString(),
        ];

        cache()->put("import_progress_{$this->jobId}", $progress, now()->addHour());
    }

    /**
     * Run bulk auto-matching
     */
    private function runBulkAutoMatch(int $threshold = 90): int
    {
        $unmatchedLegacy = LegacyFundi::unmatched()->get();
        $matchedCount = 0;
        $total = $unmatchedLegacy->count();

        foreach ($unmatchedLegacy as $index => $legacy) {
            // Update progress periodically
            if ($index % 50 === 0) {
                $percentage = 60 + (($index / $total) * 30); // 60-90% range
                $this->updateProgress($percentage, "Auto-matching: {$index}/{$total} processed...");
            }

            if ($legacy->autoLink()) {
                $matchedCount++;
            }
        }

        return $matchedCount;
    }

    /**
     * Get the unique identifier for the job
     */
    public function getJobId(): string
    {
        return $this->jobId;
    }
}

<?php

namespace App\Jobs;

use App\Imports\LegacyFundiImport;
use App\Models\LegacyFundi;
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

    protected $confidence;

    protected $skipEmptyColumns;

    /**
     * Create a new job instance.
     */
    public function __construct(
        string $filePath,
        int $userId,
        bool $autoMatch = false,
        ?string $jobId = null,
        int $confidence = 90,
        bool $skipEmptyColumns = true
    ) {
        $this->filePath = $filePath;
        $this->userId = $userId;
        $this->autoMatch = $autoMatch;
        $this->jobId = $jobId ?? uniqid('import_');
        $this->confidence = $confidence;
        $this->skipEmptyColumns = $skipEmptyColumns;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $this->updateProgress(0, 'Starting import...');

            DB::beginTransaction();

            // Analyze file structure first
            $this->updateProgress(5, 'Analyzing file structure...');
            $fileAnalysis = $this->analyzeFileStructure();

            if (! $fileAnalysis['success']) {
                throw new \Exception($fileAnalysis['error']);
            }

            // Import the data with enhanced processing
            $this->updateProgress(10, 'Processing file with enhanced logic...');
            $import = new LegacyFundiImport($fileAnalysis, $this->skipEmptyColumns, $this->jobId);

            // Set progress callback
            $import->setProgressCallback(function ($percentage, $message) {
                $adjustedPercentage = 10 + ($percentage * 0.5); // 10-60% range
                $this->updateProgress($adjustedPercentage, $message);
            });

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

            // Final update with comprehensive stats
            $message = $this->buildSuccessMessage($stats, $matchedCount);
            $finalStats = array_merge($stats, [
                'matched' => $matchedCount,
                'file_analysis' => $fileAnalysis,
                'confidence_threshold' => $this->confidence,
            ]);

            $this->updateProgress(100, $message, 'completed', $finalStats);

            // Clean up the uploaded file
            Storage::delete($this->filePath);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Legacy import job failed', [
                'job_id' => $this->jobId,
                'user_id' => $this->userId,
                'error' => $e->getMessage(),
                'file' => $this->filePath,
                'trace' => $e->getTraceAsString(),
            ]);

            $this->updateProgress(0, 'Import failed: '.$e->getMessage(), 'failed');

            // Clean up the uploaded file
            Storage::delete($this->filePath);

            throw $e;
        }
    }

    /**
     * Analyze file structure (Excel or CSV)
     */
    private function analyzeFileStructure(): array
    {
        try {
            $filePath = Storage::path($this->filePath);
            $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

            if (in_array($extension, ['xlsx', 'xls'])) {
                return $this->analyzeExcelStructure($filePath);
            } else {
                return $this->analyzeCsvStructure($filePath);
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to analyze file structure: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Analyze Excel file structure
     */
    private function analyzeExcelStructure(string $filePath): array
    {
        $workbook = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
        $worksheet = $workbook->getActiveSheet();

        // Get the first row to analyze headers
        $firstRow = [];
        $highestColumn = $worksheet->getHighestColumn();
        $columnIterator = $worksheet->getRowIterator(1, 1)->current()->getCellIterator('A', $highestColumn);

        foreach ($columnIterator as $cell) {
            $firstRow[] = $cell->getValue();
        }

        $hasHeaders = $this->detectHeaders($firstRow);
        $headers = $this->processHeaders($firstRow);
        $mapping = $this->createColumnMapping($headers);

        // Count total rows
        $totalRows = $worksheet->getHighestRow();
        $dataRows = $hasHeaders ? $totalRows - 1 : $totalRows;

        return [
            'success' => true,
            'file_type' => 'excel',
            'has_headers' => $hasHeaders,
            'raw_headers' => $firstRow,
            'processed_headers' => $headers,
            'mapping' => $mapping,
            'data_start_row' => $hasHeaders ? 1 : 0,
            'total_columns' => count($firstRow),
            'total_rows' => $dataRows,
            'useful_columns' => count(array_filter($headers, fn ($h) => ! str_starts_with($h, 'empty_column_'))),
        ];
    }

    /**
     * Analyze CSV file structure
     */
    private function analyzeCsvStructure(string $filePath): array
    {
        $handle = fopen($filePath, 'r');
        if (! $handle) {
            return ['success' => false, 'error' => 'Could not open CSV file'];
        }

        $firstRow = fgetcsv($handle);
        if ($firstRow === false) {
            fclose($handle);

            return ['success' => false, 'error' => 'CSV file is empty'];
        }

        // Count total rows
        $totalRows = 0;
        while (fgetcsv($handle) !== false) {
            $totalRows++;
        }
        fclose($handle);

        $hasHeaders = $this->detectHeaders($firstRow);
        $headers = $this->processHeaders($firstRow);
        $mapping = $this->createColumnMapping($headers);
        $dataRows = $hasHeaders ? $totalRows : $totalRows + 1;

        return [
            'success' => true,
            'file_type' => 'csv',
            'has_headers' => $hasHeaders,
            'raw_headers' => $firstRow,
            'processed_headers' => $headers,
            'mapping' => $mapping,
            'data_start_row' => $hasHeaders ? 1 : 0,
            'total_columns' => count($firstRow),
            'total_rows' => $dataRows,
            'useful_columns' => count(array_filter($headers, fn ($h) => ! str_starts_with($h, 'empty_column_'))),
        ];
    }

    /**
     * Process headers to handle empty ones and duplicates
     */
    private function processHeaders(array $rawHeaders): array
    {
        $processed = [];
        $emptyCount = 1;
        $seen = [];

        foreach ($rawHeaders as $index => $header) {
            $cleanHeader = trim($header);

            if (empty($cleanHeader)) {
                $newHeader = "empty_column_{$emptyCount}";
                $emptyCount++;
            } else {
                $newHeader = preg_replace('/[^a-zA-Z0-9_]/', '_', strtolower($cleanHeader));
                $newHeader = trim($newHeader, '_');

                $originalHeader = $newHeader;
                $counter = 1;
                while (in_array($newHeader, $seen)) {
                    $counter++;
                    $newHeader = $originalHeader.'_'.$counter;
                }
            }

            $seen[] = $newHeader;
            $processed[] = $newHeader;
        }

        return $processed;
    }

    /**
     * Detect if first row contains headers
     */
    private function detectHeaders(array $firstRow): bool
    {
        if (empty($firstRow)) {
            return false;
        }

        $headerIndicators = ['identifier', 'name', 'phone', 'location', 'full_name', 'contact', 'primary_phone', 'region'];

        foreach ($firstRow as $cell) {
            $cell = strtolower(trim($cell));
            foreach ($headerIndicators as $indicator) {
                if (str_contains($cell, $indicator)) {
                    return true;
                }
            }
        }

        return $this->isTextRow($firstRow);
    }

    /**
     * Check if row contains mostly text (likely headers)
     */
    private function isTextRow(array $row): bool
    {
        $textCount = 0;
        $numericCount = 0;

        foreach ($row as $cell) {
            $cell = trim($cell);
            if (! empty($cell)) {
                if (is_numeric($cell) || preg_match('/^[\d\+\-\(\)\s]+$/', $cell)) {
                    $numericCount++;
                } else {
                    $textCount++;
                }
            }
        }

        return $textCount >= $numericCount;
    }

    /**
     * Create column mapping based on processed headers
     */
    private function createColumnMapping(array $headers): array
    {
        $mapping = [
            'identifier' => null,
            'full_name' => null,
            'location' => null,
        ];

        $columnMapping = [
            'identifier' => ['identifier', 'phone', 'primary_phone', 'contact', 'mobile', 'number'],
            'full_name' => ['full_name', 'name', 'fundi_name', 'full name', 'fname', 'first_name'],
            'location' => ['location', 'region', 'area', 'place', 'district', 'ward', 'address'],
        ];

        // Exact matches first
        foreach ($headers as $index => $header) {
            $header = strtolower(trim($header));

            if ($this->skipEmptyColumns && str_starts_with($header, 'empty_column_')) {
                continue;
            }

            foreach ($columnMapping as $expectedCol => $variations) {
                if ($mapping[$expectedCol] === null) {
                    foreach ($variations as $variation) {
                        if ($header === $variation) {
                            $mapping[$expectedCol] = $index;
                            break 2;
                        }
                    }
                }
            }
        }

        // Fallback mapping
        if ($mapping['identifier'] === null || $mapping['full_name'] === null) {
            $usefulColumns = [];
            foreach ($headers as $index => $header) {
                if (! str_starts_with($header, 'empty_column_')) {
                    $usefulColumns[] = $index;
                }
            }

            if ($mapping['identifier'] === null && ! empty($usefulColumns)) {
                $mapping['identifier'] = $usefulColumns[0];
            }
            if ($mapping['full_name'] === null && count($usefulColumns) > 1) {
                $mapping['full_name'] = $usefulColumns[1];
            }
            if ($mapping['location'] === null && count($usefulColumns) > 2) {
                $mapping['location'] = $usefulColumns[2];
            }
        }

        return $mapping;
    }

    /**
     * Build success message with comprehensive stats
     */
    private function buildSuccessMessage(array $stats, int $matchedCount): string
    {
        $message = "Successfully imported {$stats['imported']} records.";

        if ($stats['skipped'] > 0) {
            $message .= " Skipped {$stats['skipped']} records.";
        }

        if ($stats['duplicates'] > 0) {
            $message .= " Found {$stats['duplicates']} duplicates.";
        }

        if ($matchedCount > 0) {
            $message .= " Auto-matched {$matchedCount} records.";
        }

        if (! empty($stats['errors'])) {
            $message .= ' '.count($stats['errors']).' errors occurred.';
        }

        return $message;
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        $this->updateProgress(0, 'Import failed: '.$exception->getMessage(), 'failed');
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
            'status' => $status,
            'stats' => $stats,
            'updated_at' => now()->toISOString(),
        ];

        cache()->put("import_progress_{$this->jobId}", $progress, now()->addHour());
    }

    /**
     * Run bulk auto-matching with enhanced logic
     */
    private function runBulkAutoMatch(): int
    {
        $unmatchedLegacy = LegacyFundi::unmatched()->get();
        $matchedCount = 0;
        $total = $unmatchedLegacy->count();

        foreach ($unmatchedLegacy as $index => $legacy) {
            if ($index % 50 === 0) {
                $percentage = 60 + (($index / $total) * 30);
                $this->updateProgress($percentage, "Auto-matching: {$index}/{$total} processed...");
            }

            // Use confidence threshold for matching
            $matches = $legacy->findPotentialMatches();
            $highConfidenceMatch = $matches->where('confidence', '>=', $this->confidence)->first();

            if ($highConfidenceMatch) {
                $fundi = $highConfidenceMatch['fundi'];
                if (! $fundi->legacy_match && $legacy->autoLink($fundi)) {
                    $matchedCount++;
                }
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

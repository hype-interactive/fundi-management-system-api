<?php

namespace App\Console\Commands;

use League\Csv\Reader;
use App\Models\LegacyFundi;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ImportLegacyData extends Command
{
    protected $signature = 'import:legacy-fundis {file} {--dry-run : Run without actually importing} {--chunk-size=1000 : Number of records to process at once}';
    protected $description = 'Import legacy fundis data with minimal validation';

    private $stats = [
        'total_records' => 0,
        'successful_imports' => 0,
        'duplicates_found' => 0,
        'processing_errors' => 0
    ];

    public function handle()
    {
        $filePath = $this->argument('file');
        $isDryRun = $this->option('dry-run');
        $chunkSize = (int) $this->option('chunk-size');

        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return 1;
        }

        $this->info("Starting import process...");
        $this->info("File: {$filePath}");
        $this->info("Mode: " . ($isDryRun ? 'DRY RUN' : 'LIVE IMPORT'));

        try {
            $this->processFile($filePath, $isDryRun, $chunkSize);
            $this->displayResults();
            return 0;
        } catch (\Exception $e) {
            $this->error('Import failed: ' . $e->getMessage());
            Log::error('Legacy Fundis Import Error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return 1;
        }
    }

    private function processFile(string $filePath, bool $isDryRun, int $chunkSize)
    {
        // Initialize CSV reader
        $csv = Reader::createFromPath($filePath, 'r');
        $csv->setHeaderOffset(0);

        // Get headers
        $headers = $csv->getHeader();
        $this->info('File headers: ' . implode(', ', $headers));

        // Get all records as an array
        $records = iterator_to_array($csv->getRecords());
        $this->stats['total_records'] = count($records);

        $this->info("Found {$this->stats['total_records']} records to process");

        // Process in chunks
        $processedRecords = [];

        $progressBar = $this->output->createProgressBar($this->stats['total_records']);
        $progressBar->start();

        foreach ($records as $index => $record) {
            $processedRecord = $this->processRecord($record, $index + 1);

            if ($processedRecord) {
                $processedRecords[] = $processedRecord;
            }

            $progressBar->advance();

            // Process chunk when it reaches the limit
            if (count($processedRecords) >= $chunkSize) {
                $this->processChunk($processedRecords, $isDryRun);
                $processedRecords = [];
            }
        }

        // Process remaining records
        if (!empty($processedRecords)) {
            $this->processChunk($processedRecords, $isDryRun);
        }

        $progressBar->finish();
        $this->newLine();
    }

    private function processRecord(array $record, int $lineNumber): ?array
    {
        try {
            // Get values safely, handling cases where keys might not exist or be empty
            $identifier = '';
            $fullName = '';
            $location = '';

            // Try different ways to get the data
            if (isset($record['identifier'])) {
                $identifier = $record['identifier'];
            } elseif (isset($record[0])) {
                $identifier = $record[0];
            }

            if (isset($record['full_name'])) {
                $fullName = $record['full_name'];
            } elseif (isset($record[1])) {
                $fullName = $record[1];
            }

            if (isset($record['location'])) {
                $location = $record['location'];
            } elseif (isset($record[2])) {
                $location = $record[2];
            }

            // Basic cleaning - just trim and handle empty values
            $cleanedData = [
                'name' => trim($fullName ?? ''),
                'nida_number' => null, // Will be filled later if needed
                'primary_phone' => $this->basicPhoneClean($identifier ?? ''),
                'transaction_phone' => $this->basicPhoneClean($identifier ?? ''),
                'location' => trim($location ?? ''),
                'gender' => 'other', // Default value
                'ranking' => 'fundi_msaidizi', // Default value
                'status' => 'pending'
            ];

            // Skip records with no name or phone
            if (empty($cleanedData['name']) && empty($cleanedData['primary_phone'])) {
                return null;
            }

            // If no name but has phone, set a default name
            if (empty($cleanedData['name']) && !empty($cleanedData['primary_phone'])) {
                $cleanedData['name'] = 'User ' . $cleanedData['primary_phone'];
            }

            // If no phone but has name, skip this record
            if (empty($cleanedData['primary_phone'])) {
                return null;
            }

            return $cleanedData;

        } catch (\Exception $e) {
            $this->stats['processing_errors']++;
            $this->warn("Error processing line {$lineNumber}: " . $e->getMessage());
            return null;
        }
    }

    private function basicPhoneClean(string $identifier): string
    {
        // Remove all non-numeric characters
        $cleaned = preg_replace('/[^0-9]/', '', $identifier);

        // Handle empty or very short numbers
        if (strlen($cleaned) < 9) {
            return '';
        }

        // Handle different formats
        if (strlen($cleaned) == 12 && str_starts_with($cleaned, '255')) {
            return $cleaned; // Already correct format
        }

        if (strlen($cleaned) == 10 && str_starts_with($cleaned, '0')) {
            return '255' . substr($cleaned, 1); // Convert 07... to 2557...
        }

        if (strlen($cleaned) == 9) {
            return '255' . $cleaned; // Add country code
        }

        // For longer numbers, try to extract a valid phone number
        if (strlen($cleaned) > 12) {
            // Look for pattern that starts with 255
            if (preg_match('/255(\d{9})/', $cleaned, $matches)) {
                return '255' . $matches[1];
            }
            // Try to get last 12 digits if they start with 255
            $last12 = substr($cleaned, -12);
            if (str_starts_with($last12, '255')) {
                return $last12;
            }
        }

        return $cleaned; // Return as is if we can't parse it
    }

    private function processChunk(array $records, bool $isDryRun)
    {
        if ($isDryRun) {
            $this->stats['successful_imports'] += count($records);
            return;
        }

        DB::beginTransaction();

        try {
            foreach ($records as $record) {
                // Simple duplicate check - only by phone number
                if ($this->isDuplicate($record['primary_phone'])) {
                    $this->stats['duplicates_found']++;
                    continue;
                }

                // Create the record
                LegacyFundi::create($record);
                $this->stats['successful_imports']++;
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function isDuplicate(string $primaryPhone): bool
    {
        // Only check for duplicate phone numbers
        if (empty($primaryPhone)) {
            return false;
        }

        return LegacyFundi::where('primary_phone', $primaryPhone)->exists();
    }

    private function displayResults()
    {
        $this->newLine();
        $this->info('Import completed!');
        $this->newLine();

        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Records Processed', $this->stats['total_records']],
                ['Successful Imports', $this->stats['successful_imports']],
                ['Duplicates Found', $this->stats['duplicates_found']],
                ['Processing Errors', $this->stats['processing_errors']]
            ]
        );

        $successRate = $this->stats['total_records'] > 0
            ? round(($this->stats['successful_imports'] / $this->stats['total_records']) * 100, 2)
            : 0;

        $this->info("Success Rate: {$successRate}%");

        if ($this->stats['processing_errors'] > 0) {
            $this->warn('Some records had processing errors - check the logs for details.');
        }

        if ($this->stats['duplicates_found'] > 0) {
            $this->info("{$this->stats['duplicates_found']} duplicate phone numbers were skipped.");
        }
    }
}

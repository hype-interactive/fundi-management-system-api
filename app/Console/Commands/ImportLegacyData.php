<?php

namespace App\Console\Commands;

use League\Csv\Reader;
use League\Csv\Statement;
use App\Models\LegacyFundi;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ImportLegacyData extends Command
{
    protected $signature = 'import:legacy-fundis {file} {--dry-run : Run without actually importing} {--chunk-size=1000 : Number of records to process at once}';
    protected $description = 'Import legacy fundis data with comprehensive validation and duplicate checking';

    private $stats = [
        'total_records' => 0,
        'successful_imports' => 0,
        'duplicates_found' => 0,
        'validation_errors' => 0,
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

        // Get headers and validate structure
        $headers = $csv->getHeader();
        $this->validateFileStructure($headers);

        // Get all records as an array
        $records = iterator_to_array(Statement::create()->process($csv));
        $this->stats['total_records'] = count($records);

        $this->info("Found {$this->stats['total_records']} records to process");

        // Process in chunks
        $processedRecords = [];
        $chunkCount = 0;

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
                $chunkCount++;
            }
        }

        // Process remaining records
        if (!empty($processedRecords)) {
            $this->processChunk($processedRecords, $isDryRun);
        }

        $progressBar->finish();
        $this->newLine();
    }

    private function validateFileStructure(array $headers)
    {
        $requiredHeaders = ['identifier', 'full_name'];
        $missingHeaders = array_diff($requiredHeaders, $headers);

        if (!empty($missingHeaders)) {
            throw new \Exception('Missing required headers: ' . implode(', ', $missingHeaders));
        }

        $this->info('File structure validated successfully');
    }

    private function processRecord(array $record, int $lineNumber): ?array
    {
        try {
            // Clean and normalize data
            $cleanedData = $this->cleanRecord($record);

            // Validate the cleaned data
            $validator = $this->validateRecord($cleanedData, $lineNumber);

            if ($validator->fails()) {
                $this->stats['validation_errors']++;
                $this->logValidationErrors($validator->errors()->all(), $lineNumber, $cleanedData);
                return null;
            }

            return $cleanedData;
        } catch (\Exception $e) {
            $this->stats['processing_errors']++;
            $this->warn("Error processing line {$lineNumber}: " . $e->getMessage());
            Log::warning("Record processing error", [
                'line' => $lineNumber,
                'record' => $record,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    private function cleanRecord(array $record): array
    {
        return [
            'name' => $this->cleanName($record['full_name'] ?? ''),
            'nida_number' => $this->cleanNidaNumber($record['identifier'] ?? ''),
            'primary_phone' => $this->cleanPhoneNumber($record['identifier'] ?? ''),
            'transaction_phone' => $this->cleanPhoneNumber($record['identifier'] ?? ''),
            'location' => $this->cleanLocation($record['location'] ?? ''),
            'gender' => $this->inferGender($record['full_name'] ?? ''),
            'ranking' => $this->inferRanking($record),
            'status' => 'pending'
        ];
    }

    private function cleanName(string $name): string
    {
        // Remove extra spaces, fix capitalization
        $cleaned = trim($name);
        $cleaned = preg_replace('/\s+/', ' ', $cleaned);
        $cleaned = mb_convert_case($cleaned, MB_CASE_TITLE, 'UTF-8');

        // Handle common name variations
        $cleaned = str_replace(['  ', ' .', '. '], [' ', '.', '. '], $cleaned);

        return $cleaned;
    }

    private function cleanNidaNumber(string $identifier): ?string
    {
        // Clean identifier that might be NIDA number
        $cleaned = preg_replace('/[^0-9]/', '', $identifier);

        // Tanzania NIDA numbers are typically 20 digits
        if (strlen($cleaned) === 20 && ctype_digit($cleaned)) {
            return $cleaned;
        }

        return null;
    }

    private function cleanPhoneNumber(string $identifier): string
    {
        // Clean and format phone number
        $cleaned = preg_replace('/[^0-9+]/', '', $identifier);

        // Handle different phone number formats
        if (preg_match('/^255\d{9}$/', $cleaned)) {
            return $cleaned; // Already in correct format
        } elseif (preg_match('/^0\d{9}$/', $cleaned)) {
            return '255' . substr($cleaned, 1); // Convert from 0... to 255...
        } elseif (preg_match('/^\d{9}$/', $cleaned)) {
            return '255' . $cleaned; // Add country code
        }

        return $cleaned;
    }

    private function cleanLocation(string $location): string
    {
        $cleaned = trim($location);
        $cleaned = mb_convert_case($cleaned, MB_CASE_TITLE, 'UTF-8');

        // Fix common location name variations
        $locationMappings = [
            'dar es salaam' => 'Dar es Salaam',
            'dar-es-salaam' => 'Dar es Salaam',
            'dares salaam' => 'Dar es Salaam',
            'morogoro' => 'Morogoro',
            'tanga' => 'Tanga',
            'mbeya' => 'Mbeya',
            'mbezi' => 'Mbezi',
            'ilala' => 'Ilala',
            'tunduma' => 'Tunduma'
        ];

        $lowerLocation = strtolower($cleaned);
        return $locationMappings[$lowerLocation] ?? $cleaned;
    }

    private function inferGender(string $name): string
    {
        // Simple gender inference based on common Tanzanian names
        $maleNames = ['james', 'david', 'william', 'robert', 'johnson', 'steven', 'kenneth', 'abdala'];
        $femaleNames = ['lucy', 'yohana', 'obet'];

        $firstName = strtolower(explode(' ', trim($name))[0] ?? '');

        if (in_array($firstName, $maleNames)) {
            return 'male';
        } elseif (in_array($firstName, $femaleNames)) {
            return 'female';
        }

        // Default to male if uncertain (you might want to change this logic)
        return 'male';
    }

    private function inferRanking(array $record): string
    {
        // You can implement logic to infer ranking based on data patterns
        // For now, defaulting to 'fundi_msaidizi'
        return 'fundi_msaidizi';
    }

    private function validateRecord(array $data, int $lineNumber): \Illuminate\Validation\Validator
    {
        $rules = [
            'name' => 'required|string|min:2|max:255',
            'gender' => 'required|in:male,female,other',
            'nida_number' => 'nullable|string|size:20|regex:/^[0-9]{20}$/',
            'primary_phone' => 'required|string|min:10|max:20|regex:/^255[0-9]{9}$/',
            'transaction_phone' => 'nullable|string|min:10|max:20|regex:/^255[0-9]{9}$/',
            'ranking' => 'required|in:fundi_kuu,fundi_msaidizi',
            'status' => 'required|in:active,inactive,pending'
        ];

        $messages = [
            'name.required' => 'Name is required',
            'name.min' => 'Name must be at least 2 characters',
            'primary_phone.required' => 'Primary phone is required',
            'primary_phone.regex' => 'Phone number must be in format 255XXXXXXXXX',
            'nida_number.size' => 'NIDA number must be exactly 20 digits',
            'nida_number.regex' => 'NIDA number must contain only digits'
        ];

        return Validator::make($data, $rules, $messages);
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
                // Check for duplicates
                if ($this->isDuplicate($record)) {
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

    private function isDuplicate(array $record): bool
    {
        $query = LegacyFundi::query();

        // Check for duplicate by NIDA number if available
        if (!empty($record['nida_number'])) {
            if ($query->where('nida_number', $record['nida_number'])->exists()) {
                return true;
            }
        }

        // Check for duplicate by phone number
        if ($query->where('primary_phone', $record['primary_phone'])->exists()) {
            return true;
        }

        // Check for duplicate by name and similar phone (in case of slight variations)
        $similarPhonePattern = substr($record['primary_phone'], -9); // Last 9 digits
        if ($query->where('name', $record['name'])
            ->where('primary_phone', 'LIKE', '%' . $similarPhonePattern)
            ->exists()
        ) {
            return true;
        }

        return false;
    }

    private function logValidationErrors(array $errors, int $lineNumber, array $data)
    {
        $this->warn("Validation errors on line {$lineNumber}:");
        foreach ($errors as $error) {
            $this->warn("  - {$error}");
        }

        Log::warning("Validation errors during import", [
            'line' => $lineNumber,
            'errors' => $errors,
            'data' => $data
        ]);
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
                ['Validation Errors', $this->stats['validation_errors']],
                ['Processing Errors', $this->stats['processing_errors']]
            ]
        );

        $successRate = $this->stats['total_records'] > 0
            ? round(($this->stats['successful_imports'] / $this->stats['total_records']) * 100, 2)
            : 0;

        $this->info("Success Rate: {$successRate}%");

        if ($this->stats['validation_errors'] > 0 || $this->stats['processing_errors'] > 0) {
            $this->warn('Check the logs for detailed error information.');
        }
    }
}

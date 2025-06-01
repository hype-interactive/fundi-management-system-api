<?php

namespace App\Console\Commands;

use Exception;
use League\Csv\Reader;
use App\Models\LegacyFundi;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ImportCleanedLegacyData extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'import:cleaned-legacy-data
                          {file? : Path to the CSV file}
                          {--chunk=1000 : Number of records to process at once}
                          {--validate : Only validate data without importing}
                          {--force : Skip confirmation prompts}';

    /**
     * The console command description.
     */
    protected $description = 'Import fundi data from CSV file into the database';

    /**
     * Statistics tracking
     */
    private $stats = [
        'total_rows' => 0,
        'imported' => 0,
        'skipped' => 0,
        'errors' => 0,
        'duplicates' => 0,
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Starting LegacyFundi Data Import...');

        // Get file path
        $filePath = $this->argument('file') ?? $this->askForFilePath();

        if (!$this->validateFile($filePath)) {
            return 1;
        }

        // Show preview and confirm
        if (!$this->option('force') && !$this->previewAndConfirm($filePath)) {
            $this->info('Import cancelled.');
            return 0;
        }

        // Validate only mode
        if ($this->option('validate')) {
            return $this->validateData($filePath);
        }

        // Start import process
        return $this->importData($filePath);
    }

    /**
     * Ask user for file path
     */
    private function askForFilePath(): string
    {
        $defaultPath = storage_path('app/mysql_ready_data/fundi_mysql_import.csv');

        return $this->ask(
            'Enter the path to your CSV file',
            $defaultPath
        );
    }

    /**
     * Validate file exists and is readable
     */
    private function validateFile(string $filePath): bool
    {
        if (!file_exists($filePath)) {
            $this->error("❌ File not found: {$filePath}");
            return false;
        }

        if (!is_readable($filePath)) {
            $this->error("❌ File is not readable: {$filePath}");
            return false;
        }

        $this->info("✅ File found: {$filePath}");
        return true;
    }

    /**
     * Preview data and ask for confirmation
     */
    private function previewAndConfirm(string $filePath): bool
    {
        try {
            $csv = Reader::createFromPath($filePath, 'r');
            $csv->setHeaderOffset(0);

            $headers = $csv->getHeader();
            $records = iterator_to_array($csv->getRecords());

            $this->info("\n📊 File Preview:");
            $this->info("Headers: " . implode(', ', $headers));
            $this->info("Total rows: " . count($records));

            // Show first few records
            $this->info("\n🔍 First 3 records:");
            $this->table($headers, array_slice($records, 0, 3));

            // Check for existing records
            $existingCount = LegacyFundi::count();
            $this->info("\n📈 Current database status:");
            $this->info("Existing fundis in database: {$existingCount}");

            return $this->confirm('Do you want to proceed with the import?');
        } catch (Exception $e) {
            $this->error("❌ Error reading CSV file: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Validate data without importing
     */
    private function validateData(string $filePath): int
    {
        $this->info('🔍 Validating data...');

        try {
            $csv = Reader::createFromPath($filePath, 'r');
            $csv->setHeaderOffset(0);

            $validationErrors = [];
            $rowNumber = 1;

            foreach ($csv->getRecords() as $record) {
                $rowNumber++;
                $errors = $this->validateRecord($record, $rowNumber);

                if (!empty($errors)) {
                    $validationErrors = array_merge($validationErrors, $errors);
                }

                $this->stats['total_rows']++;
            }

            if (empty($validationErrors)) {
                $this->info("✅ All {$this->stats['total_rows']} records are valid!");
                return 0;
            } else {
                $this->error("❌ Found " . count($validationErrors) . " validation errors:");
                foreach ($validationErrors as $error) {
                    $this->error("  • {$error}");
                }
                return 1;
            }
        } catch (Exception $e) {
            $this->error("❌ Validation failed: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Import data into database
     */
    private function importData(string $filePath): int
    {
        try {
            $csv = Reader::createFromPath($filePath, 'r');
            $csv->setHeaderOffset(0);

            $chunkSize = (int) $this->option('chunk');
            $records = [];
            $progressBar = null;

            // Count total records for progress bar
            $totalRecords = iterator_count($csv->getRecords());
            $csv->setHeaderOffset(0); // Reset after counting

            $progressBar = $this->output->createProgressBar($totalRecords);
            $progressBar->start();

            DB::beginTransaction();

            foreach ($csv->getRecords() as $record) {
                $this->stats['total_rows']++;

                // Validate record
                $validationErrors = $this->validateRecord($record, $this->stats['total_rows']);
                if (!empty($validationErrors)) {
                    $this->stats['errors']++;
                    Log::warning("Row {$this->stats['total_rows']}: " . implode(', ', $validationErrors));
                    $progressBar->advance();
                    continue;
                }

                // Check for duplicates
                if ($this->isDuplicate($record)) {
                    $this->stats['duplicates']++;
                    $progressBar->advance();
                    continue;
                }

                $records[] = $this->prepareRecord($record);

                // Process in chunks
                if (count($records) >= $chunkSize) {
                    $this->insertChunk($records);
                    $records = [];
                }

                $progressBar->advance();
            }

            // Insert remaining records
            if (!empty($records)) {
                $this->insertChunk($records);
            }

            DB::commit();
            $progressBar->finish();

            $this->displayResults();
            return 0;
        } catch (Exception $e) {
            DB::rollBack();
            $this->error("\n❌ Import failed: " . $e->getMessage());
            Log::error('LegacyFundi import failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return 1;
        }
    }

    /**
     * Validate a single record
     */
    private function validateRecord(array $record, int $rowNumber): array
    {
        $errors = [];

        // Required fields
        if (empty(trim($record['name'] ?? ''))) {
            $errors[] = "Row {$rowNumber}: Name is required";
        }

        if (empty(trim($record['primary_phone'] ?? ''))) {
            $errors[] = "Row {$rowNumber}: Primary phone is required";
        }

        // Phone number format
        $phone = trim($record['primary_phone'] ?? '');
        if (!empty($phone) && !preg_match('/^255\d{9}$/', $phone)) {
            $errors[] = "Row {$rowNumber}: Invalid phone format: {$phone}";
        }

        // Gender validation
        $gender = trim($record['gender'] ?? '');
        if (!empty($gender) && !in_array($gender, ['male', 'female', 'other'])) {
            $errors[] = "Row {$rowNumber}: Invalid gender: {$gender}";
        }

        // Ranking validation
        $ranking = trim($record['ranking'] ?? '');
        if (!empty($ranking) && !in_array($ranking, ['fundi_kuu', 'fundi_msaidizi'])) {
            $errors[] = "Row {$rowNumber}: Invalid ranking: {$ranking}";
        }

        // Status validation
        $status = trim($record['status'] ?? '');
        if (!empty($status) && !in_array($status, ['active', 'inactive', 'pending', 'suspended'])) {
            $errors[] = "Row {$rowNumber}: Invalid status: {$status}";
        }

        return $errors;
    }

    /**
     * Check if record is duplicate
     */
    private function isDuplicate(array $record): bool
    {
        $phone = trim($record['primary_phone'] ?? '');

        if (empty($phone)) {
            return false;
        }

        return LegacyFundi::where('primary_phone', $phone)->exists();
    }

    /**
     * Prepare record for database insertion
     */
    private function prepareRecord(array $record): array
    {
        return [
            'name' => trim($record['name'] ?? ''),
            'gender' => trim($record['gender'] ?? 'other'),
            'nida_number' => !empty(trim($record['nida_number'] ?? '')) ? trim($record['nida_number']) : null,
            'primary_phone' => trim($record['primary_phone'] ?? ''),
            'transaction_phone' => !empty(trim($record['transaction_phone'] ?? '')) ? trim($record['transaction_phone']) : null,
            'ranking' => trim($record['ranking'] ?? 'fundi_msaidizi'),
            'profile_photo' => !empty(trim($record['profile_photo'] ?? '')) ? trim($record['profile_photo']) : null,
            'status' => trim($record['status'] ?? 'pending'),
            'region' => !empty(trim($record['region'] ?? '')) ? trim($record['region']) : null,
            'district' => !empty(trim($record['district'] ?? '')) ? trim($record['district']) : null,
            'ward' => !empty(trim($record['ward'] ?? '')) ? trim($record['ward']) : null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Insert chunk of records
     */
    private function insertChunk(array $records): void
    {
        try {
            LegacyFundi::insert($records);
            $this->stats['imported'] += count($records);
        } catch (Exception $e) {
            $this->stats['errors'] += count($records);
            Log::error('Failed to insert chunk', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Display import results
     */
    private function displayResults(): void
    {
        $this->newLine(2);
        $this->info('🎉 Import completed!');
        $this->newLine();

        $this->table(
            ['Metric', 'Count'],
            [
                ['Total rows processed', number_format($this->stats['total_rows'])],
                ['Successfully imported', number_format($this->stats['imported'])],
                ['Duplicates skipped', number_format($this->stats['duplicates'])],
                ['Errors encountered', number_format($this->stats['errors'])],
                ['Success rate', $this->calculateSuccessRate() . '%'],
            ]
        );

        if ($this->stats['errors'] > 0) {
            $this->warn("⚠️  Check the logs for detailed error information.");
        }

        if ($this->stats['duplicates'] > 0) {
            $this->info("ℹ️  Duplicates were skipped based on primary_phone field.");
        }
    }

    /**
     * Calculate success rate
     */
    private function calculateSuccessRate(): string
    {
        if ($this->stats['total_rows'] === 0) {
            return '0';
        }

        $rate = ($this->stats['imported'] / $this->stats['total_rows']) * 100;
        return number_format($rate, 1);
    }
}

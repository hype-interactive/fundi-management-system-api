<?php

namespace App\Console\Commands;

use Exception;
use App\Models\Fundi;
use League\Csv\Reader;
use League\Csv\Statement;
use App\Models\LegacyFundi;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ImportLegacyFundis extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'import:legacy-fundis
                        {file : Path to the CSV file}
                        {--chunk=1000 : Number of records to process at once}
                        {--auto-match : Run automatic matching after import}
                        {--confidence=90 : Confidence threshold for auto-matching (70-100)}
                        {--validate-only : Only validate data without importing}
                        {--dry-run : Show what would be done without making changes}
                        {--force : Skip confirmation prompts}
                        {--clean-duplicates : Remove duplicates after import}
                        {--skip-empty-columns : Skip columns with empty headers}
                        {--show-headers : Display all headers for debugging}
                        {--debug-skips : Show detailed reasons why records are skipped}
                        {--sample-skips=10 : Number of skipped record samples to show}';

    /**
     * The console command description.
     */
    protected $description = 'Import legacy fundi data from CSV with intelligent matching and detailed debugging';

    /**
     * Statistics tracking
     */
    private $stats = [
        'total_rows' => 0,
        'imported' => 0,
        'skipped' => 0,
        'errors' => 0,
        'duplicates' => 0,
        'matched' => 0,
        'potential_matches' => 0,
    ];

    /**
     * Detailed skip reasons tracking
     */
    private $skipReasons = [
        'empty_name_and_phone' => 0,
        'invalid_phone_format' => 0,
        'empty_name_no_phone' => 0,
        'processing_error' => 0,
        'duplicate_phone' => 0,
    ];

    /**
     * Sample skipped records for debugging
     */
    private $skippedSamples = [];

    private $columnMapping = [
        'identifier' => ['identifier', 'phone', 'primary_phone', 'contact', 'mobile', 'number'],
        'full_name' => ['full_name', 'name', 'fundi_name', 'full name', 'fname', 'first_name'],
        'location' => ['location', 'region', 'area', 'place', 'district', 'ward', 'address']
    ];

    public function handle()
    {
        $this->displayHeader();

        $filePath = $this->argument('file');

        if (!$this->validateFile($filePath)) {
            return 1;
        }

        try {
            // Analyze the CSV structure first
            $csvAnalysis = $this->analyzeCsvStructure($filePath);

            if (!$csvAnalysis['success']) {
                $this->error($csvAnalysis['error']);
                return 1;
            }

            // Show headers if requested
            if ($this->option('show-headers')) {
                $this->showDetailedHeaders($csvAnalysis);
            }

            // Show preview and confirm unless forced
            if (!$this->option('force') && !$this->previewAndConfirm($filePath, $csvAnalysis)) {
                $this->info('Import cancelled.');
                return 0;
            }

            // Validate only mode
            if ($this->option('validate-only')) {
                return $this->validateData($filePath, $csvAnalysis);
            }

            // Import data
            $this->importData($filePath, $csvAnalysis);

            // Clean duplicates if requested
            if ($this->option('clean-duplicates')) {
                $this->cleanDuplicates();
            }

            // Auto-match if requested
            if ($this->option('auto-match')) {
                $this->autoMatch();
            }

            $this->displayResults();
            return 0;
        } catch (Exception $e) {
            $this->error("\n❌ Import failed: " . $e->getMessage());
            Log::error('Legacy import failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $filePath
            ]);
            return 1;
        }
    }

    /**
     * Analyze CSV structure and handle empty/duplicate headers
     */
    private function analyzeCsvStructure(string $filePath): array
    {
        try {
            $csv = Reader::createFromPath($filePath, 'r');

            // Read first few rows to understand structure
            $rows = [];
            $stmt = (new Statement())->limit(10);
            foreach ($stmt->process($csv) as $row) {
                $rows[] = $row;
            }

            if (empty($rows)) {
                return ['success' => false, 'error' => 'CSV file is empty'];
            }

            $firstRow = $rows[0];
            $hasHeaders = $this->detectHeaders($firstRow);

            if ($hasHeaders) {
                // Process headers - handle empty ones
                $headers = $this->processHeaders($firstRow);

                // Map headers to our expected format
                $mapping = $this->createColumnMapping($headers);

                return [
                    'success' => true,
                    'has_headers' => true,
                    'raw_headers' => $firstRow,
                    'processed_headers' => $headers,
                    'mapping' => $mapping,
                    'data_start_row' => 1,
                    'total_columns' => count($firstRow),
                    'useful_columns' => count(array_filter($headers, fn($h) => !str_starts_with($h, 'empty_column_')))
                ];
            } else {
                // No headers detected, use positional mapping
                $this->info("📋 No headers detected. Using positional mapping (column 1=phone, 2=name, 3=location)");

                $syntheticHeaders = [];
                for ($i = 0; $i < count($firstRow); $i++) {
                    if ($i == 0) $syntheticHeaders[] = 'identifier';
                    elseif ($i == 1) $syntheticHeaders[] = 'full_name';
                    elseif ($i == 2) $syntheticHeaders[] = 'location';
                    else $syntheticHeaders[] = "column_" . ($i + 1);
                }

                return [
                    'success' => true,
                    'has_headers' => false,
                    'raw_headers' => $firstRow,
                    'processed_headers' => $syntheticHeaders,
                    'mapping' => [
                        'identifier' => 0,
                        'full_name' => 1,
                        'location' => 2
                    ],
                    'data_start_row' => 0,
                    'total_columns' => count($firstRow),
                    'useful_columns' => min(3, count($firstRow))
                ];
            }
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Failed to analyze CSV: ' . $e->getMessage()];
        }
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

            // Handle empty headers
            if (empty($cleanHeader)) {
                $newHeader = "empty_column_{$emptyCount}";
                $emptyCount++;
                $this->warn("⚠️  Found empty header at column " . ($index + 1) . ", renamed to '{$newHeader}'");
            } else {
                // Clean the header
                $newHeader = preg_replace('/[^a-zA-Z0-9_]/', '_', strtolower($cleanHeader));
                $newHeader = trim($newHeader, '_');

                // Handle duplicates
                $originalHeader = $newHeader;
                $counter = 1;
                while (in_array($newHeader, $seen)) {
                    $counter++;
                    $newHeader = $originalHeader . '_' . $counter;
                }

                if ($counter > 1) {
                    $this->warn("⚠️  Found duplicate header '{$cleanHeader}', renamed to '{$newHeader}'");
                }
            }

            $seen[] = $newHeader;
            $processed[] = $newHeader;
        }

        return $processed;
    }

    /**
     * Show detailed header information for debugging
     */
    private function showDetailedHeaders(array $csvAnalysis): void
    {
        $this->info("\n🔍 Detailed Header Analysis");
        $this->info("============================");

        $headers = [];
        foreach ($csvAnalysis['raw_headers'] as $index => $rawHeader) {
            $processedHeader = $csvAnalysis['processed_headers'][$index];
            $isEmpty = empty(trim($rawHeader));
            $isUseful = !str_starts_with($processedHeader, 'empty_column_');

            $headers[] = [
                'Index' => $index + 1,
                'Raw Header' => $rawHeader ?: '(empty)',
                'Processed' => $processedHeader,
                'Status' => $isEmpty ? 'Empty' : ($isUseful ? 'Useful' : 'Processed'),
                'Will Use' => $isUseful && !$this->option('skip-empty-columns') ? 'Yes' : 'No'
            ];
        }

        $this->table(['Index', 'Raw Header', 'Processed', 'Status', 'Will Use'], $headers);

        $this->info("\nSummary:");
        $this->info("Total columns: " . $csvAnalysis['total_columns']);
        $this->info("Useful columns: " . $csvAnalysis['useful_columns']);
        $this->info("Empty columns: " . ($csvAnalysis['total_columns'] - $csvAnalysis['useful_columns']));
        $this->newLine();
    }

    /**
     * Detect if first row contains headers
     */
    private function detectHeaders(array $firstRow): bool
    {
        if (empty($firstRow)) {
            return false;
        }

        // Check if first row looks like headers
        $headerIndicators = ['identifier', 'name', 'phone', 'location', 'full_name', 'contact'];

        $nonEmptyHeaders = array_filter($firstRow, fn($cell) => !empty(trim($cell)));

        foreach ($nonEmptyHeaders as $cell) {
            $cell = strtolower(trim($cell));
            foreach ($headerIndicators as $indicator) {
                if (str_contains($cell, $indicator)) {
                    return true;
                }
            }
        }

        // If first row has mostly text and appears to be headers
        return $this->isTextRow($firstRow);
    }

    /**
     * Check if row contains mostly text (likely headers)
     */
    private function isTextRow(array $row): bool
    {
        $textCount = 0;
        $numericCount = 0;
        $emptyCount = 0;

        foreach ($row as $cell) {
            $cell = trim($cell);
            if (empty($cell)) {
                $emptyCount++;
            } elseif (is_numeric($cell) || preg_match('/^[\d\+\-\(\)\s]+$/', $cell)) {
                $numericCount++;
            } else {
                $textCount++;
            }
        }

        // Consider it headers if we have more text than numbers (ignoring empty cells)
        $nonEmptyCount = $textCount + $numericCount;
        return $nonEmptyCount > 0 && $textCount >= $numericCount;
    }

    /**
     * Create column mapping based on processed headers
     */
    private function createColumnMapping(array $headers): array
    {
        $mapping = [
            'identifier' => null,
            'full_name' => null,
            'location' => null
        ];

        foreach ($headers as $index => $header) {
            $header = strtolower(trim($header));

            // Skip empty columns if requested
            if ($this->option('skip-empty-columns') && str_starts_with($header, 'empty_column_')) {
                continue;
            }

            // EXACT MATCHES FIRST (highest priority)
            if ($header === 'primary_phone' && $mapping['identifier'] === null) {
                $mapping['identifier'] = $index;
                continue;
            }
            if ($header === 'name' && $mapping['full_name'] === null) {
                $mapping['full_name'] = $index;
                continue;
            }
            if ($header === 'region' && $mapping['location'] === null) {
                $mapping['location'] = $index;
                continue;
            }

            // PARTIAL MATCHES (lower priority)
            foreach ($this->columnMapping as $expectedCol => $variations) {
                if ($mapping[$expectedCol] === null) {
                    foreach ($variations as $variation) {
                        // Use exact match instead of contains for better precision
                        if ($header === $variation) {
                            $mapping[$expectedCol] = $index;
                            break 2;
                        }
                    }
                }
            }
        }

        // FALLBACK: If still no exact matches, try contains but with priority order
        if ($mapping['identifier'] === null) {
            foreach ($headers as $index => $header) {
                $header = strtolower($header);
                if (str_contains($header, 'primary_phone') || str_contains($header, 'phone')) {
                    $mapping['identifier'] = $index;
                    break;
                }
            }
        }

        // Standard fallback for other fields
        if ($mapping['identifier'] === null) {
            $usefulColumns = [];
            foreach ($headers as $index => $header) {
                if (!str_starts_with($header, 'empty_column_')) {
                    $usefulColumns[] = $index;
                }
            }

            if (count($usefulColumns) >= 1) $mapping['identifier'] = $usefulColumns[0];
            if (count($usefulColumns) >= 2) $mapping['full_name'] = $usefulColumns[1];
            if (count($usefulColumns) >= 3) $mapping['location'] = $usefulColumns[2];
        }

        return $mapping;
    }

    /**
     * Display command header
     */
    private function displayHeader(): void
    {
        $this->info('🚀 Legacy Fundis Import Tool (Enhanced)');
        $this->info('=======================================');
        $this->newLine();
    }

    /**
     * Validate file exists and is readable
     */
    private function validateFile(string $filePath): bool
    {
        if (!file_exists($filePath)) {
            $this->error("❌ File not found: {$filePath}");
            $this->info("💡 Make sure to provide the full path to your CSV file");
            return false;
        }

        if (!is_readable($filePath)) {
            $this->error("❌ File is not readable: {$filePath}");
            return false;
        }

        // Check file size
        $sizeBytes = filesize($filePath);
        $sizeMB = round($sizeBytes / (1024 * 1024), 2);

        $this->info("✅ File found: {$filePath}");
        $this->info("📊 File size: {$sizeMB}MB");

        return true;
    }

    /**
     * Preview data and ask for confirmation
     */
    private function previewAndConfirm(string $filePath, array $csvAnalysis): bool
    {
        try {
            $csv = Reader::createFromPath($filePath, 'r');

            // Skip headers if they exist
            $stmt = Statement::create()->offset($csvAnalysis['data_start_row'])->limit(5);
            $records = [];

            foreach ($stmt->process($csv) as $row) {
                $records[] = $row;
            }

            $this->info("📊 File Analysis:");
            $this->info("Has headers: " . ($csvAnalysis['has_headers'] ? 'Yes' : 'No'));
            $this->info("Total columns: " . $csvAnalysis['total_columns']);
            $this->info("Useful columns: " . $csvAnalysis['useful_columns']);
            $this->info("Empty columns: " . ($csvAnalysis['total_columns'] - $csvAnalysis['useful_columns']));

            // Show column mapping
            $this->info("\n🗂️  Column Mapping:");
            foreach ($csvAnalysis['mapping'] as $field => $index) {
                if ($index !== null) {
                    $headerName = $csvAnalysis['processed_headers'][$index] ?? "Column {$index}";
                    $rawHeader = $csvAnalysis['raw_headers'][$index] ?? '';
                    $displayHeader = !empty($rawHeader) ? $rawHeader : "(empty column {$index})";
                    $this->info("  {$field} -> {$displayHeader} (processed as: {$headerName})");
                } else {
                    $this->warn("  {$field} -> NOT FOUND");
                }
            }

            // Count total records
            $csv = Reader::createFromPath($filePath, 'r');
            $stmt = Statement::create()->offset($csvAnalysis['data_start_row']);
            $totalRecords = iterator_count($stmt->process($csv));

            $this->info("\nTotal data rows: " . number_format($totalRecords));

            // Show sample data
            if (!empty($records)) {
                $this->info("\n🔍 Sample Data (showing only mapped columns):");
                $sampleData = [];

                foreach (array_slice($records, 0, 3) as $record) {
                    $row = [];
                    foreach ($csvAnalysis['mapping'] as $field => $index) {
                        $row[$field] = $index !== null ? ($record[$index] ?? '') : 'N/A';
                    }
                    $sampleData[] = $row;
                }

                $this->table(['Identifier', 'Full Name', 'Location'], array_map(function ($row) {
                    return [$row['identifier'], $row['full_name'], $row['location']];
                }, $sampleData));
            }

            // Show current database status
            $existingCount = LegacyFundi::count();
            $currentFundis = Fundi::count();

            $this->info("\n📈 Current Database Status:");
            $this->info("Existing legacy fundis: " . number_format($existingCount));
            $this->info("Current fundis: " . number_format($currentFundis));

            // Show import configuration
            $this->info("\n⚙️  Import Configuration:");
            $this->info("Chunk size: " . $this->option('chunk'));
            $this->info("Skip empty columns: " . ($this->option('skip-empty-columns') ? 'Yes' : 'No'));
            $this->info("Auto-match: " . ($this->option('auto-match') ? 'Yes' : 'No'));
            if ($this->option('auto-match')) {
                $this->info("Match confidence: " . $this->option('confidence') . '%');
            }
            $this->info("Dry run: " . ($this->option('dry-run') ? 'Yes' : 'No'));

            return $this->confirm('Do you want to proceed with the import?', true);
        } catch (Exception $e) {
            $this->error("❌ Error previewing CSV file: " . $e->getMessage());
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
            $warnings = [];
            $rowNumber = 1;

            $progressBar = $this->output->createProgressBar(iterator_count($csv->getRecords()));
            $csv->setHeaderOffset(0); // Reset after counting
            $progressBar->start();

            foreach ($csv->getRecords() as $record) {
                $rowNumber++;
                $result = $this->validateRecord($record, $rowNumber);

                $validationErrors = array_merge($validationErrors, $result['errors']);
                $warnings = array_merge($warnings, $result['warnings']);

                $this->stats['total_rows']++;
                $progressBar->advance();
            }

            $progressBar->finish();
            $this->newLine(2);

            // Display results
            if (empty($validationErrors)) {
                $this->info("✅ All {$this->stats['total_rows']} records are valid!");
            } else {
                $this->error("❌ Found " . count($validationErrors) . " validation errors:");
                foreach (array_slice($validationErrors, 0, 10) as $error) {
                    $this->error("  • {$error}");
                }
                if (count($validationErrors) > 10) {
                    $this->info("  ... and " . (count($validationErrors) - 10) . " more errors");
                }
            }

            if (!empty($warnings)) {
                $this->warn("\n⚠️  Found " . count($warnings) . " warnings:");
                foreach (array_slice($warnings, 0, 5) as $warning) {
                    $this->warn("  • {$warning}");
                }
                if (count($warnings) > 5) {
                    $this->info("  ... and " . (count($warnings) - 5) . " more warnings");
                }
            }

            return empty($validationErrors) ? 0 : 1;
        } catch (Exception $e) {
            $this->error("❌ Validation failed: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Import data into database
     */
    private function importData(string $filePath, array $csvAnalysis): void
    {
        $this->info('📥 Starting import process...');

        $csv = Reader::createFromPath($filePath, 'r');
        $csv->setHeaderOffset(0);

        $chunkSize = (int) $this->option('chunk');
        $isDryRun = $this->option('dry-run');
        $records = [];

        // Count total records for progress bar
        $totalRecords = iterator_count($csv->getRecords());
        $csv->setHeaderOffset(0); // Reset after counting

        $progressBar = $this->output->createProgressBar($totalRecords);
        $progressBar->start();

        if (!$isDryRun) {
            DB::beginTransaction();
        }

        try {
            foreach ($csv->getRecords() as $record) {
                $this->stats['total_rows']++;

                $processedRecord = $this->processRecord($record, $this->stats['total_rows'], $csvAnalysis);

                if ($processedRecord) {
                    if ($this->isDuplicate($processedRecord)) {
                        $this->stats['duplicates']++;
                        $this->skipReasons['duplicate_phone']++;

                        if ($this->option('debug-skips') && count($this->skippedSamples) < $this->option('sample-skips')) {
                            $this->addSkippedSample(
                                $record,
                                $this->stats['total_rows'],
                                'duplicate_phone',
                                "Phone {$processedRecord['primary_phone']} already exists in database"
                            );
                        }
                    } else {
                        $records[] = $processedRecord;
                    }
                } else {
                    $this->stats['skipped']++;
                }

                // Process in chunks
                if (count($records) >= $chunkSize) {
                    $this->insertChunk($records, $isDryRun);
                    $records = [];
                }

                $progressBar->advance();
            }

            // Insert remaining records
            if (!empty($records)) {
                $this->insertChunk($records, $isDryRun);
            }

            if (!$isDryRun) {
                DB::commit();
            }

            $progressBar->finish();
            $this->newLine();

            if ($this->option('debug-skips')) {
                $this->displaySkipAnalysis();
            }
        } catch (Exception $e) {
            if (!$isDryRun) {
                DB::rollBack();
            }
            throw $e;
        }
    }

    /**
     * Process a single CSV record with detailed skip tracking
     */
    private function processRecord(array $record, int $rowNumber, array $csvAnalysis): ?array
    {
        $debugSkips = $this->option('debug-skips');
        $sampleLimit = (int) $this->option('sample-skips');

        try {
            // Extract data from CSV (supporting both associative and indexed arrays)
            $mapping = $csvAnalysis['mapping'];

            // $identifier = '';
            // $fullName = '';
            // $location = '';

            // // Use the detected column mapping
            // if (isset($mapping['identifier']) && $mapping['identifier'] !== null) {
            //     $identifier = $record[$mapping['identifier']] ?? '';
            // }
            // if (isset($mapping['full_name']) && $mapping['full_name'] !== null) {
            //     $fullName = $record[$mapping['full_name']] ?? '';
            // }
            // if (isset($mapping['location']) && $mapping['location'] !== null) {
            //     $location = $record[$mapping['location']] ?? '';
            // }

            // FIXED: Access data directly by column names since CSV reader creates associative array
            $identifier = $record['primary_phone'] ?? '';
            $fullName = $record['name'] ?? '';
            $location = $record['region'] ?? '';

            // Clean phone number
            $phone = $this->cleanPhone($identifier);
            $name = $this->cleanName($fullName);
            $locationData = $this->parseLocation($location);

            // Debug logging for empty data
            if (empty($name) && empty($phone)) {
                $this->skipReasons['empty_name_and_phone']++;
                if ($debugSkips && count($this->skippedSamples) < $sampleLimit) {
                    $this->addSkippedSample(
                        $record,
                        $rowNumber,
                        'empty_name_and_phone',
                        "Both name and phone are empty after cleaning. Raw data: identifier='{$identifier}', name='{$fullName}'"
                    );
                }
                return null;
            }

            // Generate name if missing but has phone
            if (empty($name) && !empty($phone)) {
                $name = 'User ' . substr($phone, -4);
            }

            // Skip if no valid phone
            if (empty($phone)) {
                if (empty($name)) {
                    $this->skipReasons['empty_name_no_phone']++;
                    if ($debugSkips && count($this->skippedSamples) < $sampleLimit) {
                        $this->addSkippedSample(
                            $record,
                            $rowNumber,
                            'empty_name_no_phone',
                            "Name is empty and no valid phone found. Raw identifier: '{$identifier}', cleaned phone: '{$phone}'"
                        );
                    }
                } else {
                    $this->skipReasons['invalid_phone_format']++;
                    if ($debugSkips && count($this->skippedSamples) < $sampleLimit) {
                        $this->addSkippedSample(
                            $record,
                            $rowNumber,
                            'invalid_phone_format',
                            "Invalid phone format. Raw: '{$identifier}', after cleaning: '{$phone}'"
                        );
                    }
                }
                return null;
            }

            return [
                'name' => $name,
                'gender' => $this->inferGender($name),
                'nida_number' => null,
                'primary_phone' => $phone,
                'transaction_phone' => $phone, // Same as primary for legacy data
                'ranking' => 'fundi_msaidizi',
                'profile_photo' => null,
                'status' => 'pending',
                'region' => $locationData['region'],
                'district' => $locationData['district'],
                'ward' => $locationData['ward'],
                'created_at' => now(),
                'updated_at' => now(),
            ];
        } catch (Exception $e) {
            $this->stats['errors']++;
            $this->skipReasons['processing_error']++;

            if ($debugSkips && count($this->skippedSamples) < $sampleLimit) {
                $this->addSkippedSample(
                    $record,
                    $rowNumber,
                    'processing_error',
                    "Exception during processing: " . $e->getMessage()
                );
            }

            Log::warning("Error processing row {$rowNumber}: " . $e->getMessage(), [
                'record' => $record,
                'row_number' => $rowNumber
            ]);
            return null;
        }
    }

    /**
     * Add a sample of skipped record for debugging
     */
    private function addSkippedSample(array $record, int $rowNumber, string $reason, string $details)
    {
        $this->skippedSamples[] = [
            'row' => $rowNumber,
            'reason' => $reason,
            'details' => $details,
            'raw_data' => array_slice($record, 0, 5), // First 5 columns only
        ];
    }

    /**
     * Display detailed skip analysis
     */
    private function displaySkipAnalysis()
    {
        $this->newLine();
        $this->info('🔍 Skip Analysis');
        $this->info('================');

        // Show skip reasons summary
        $this->info("\n📊 Skip Reasons Summary:");
        foreach ($this->skipReasons as $reason => $count) {
            if ($count > 0) {
                $percentage = ($count / $this->stats['total_rows']) * 100;
                $this->info("  • " . str_replace('_', ' ', ucfirst($reason)) . ": {$count} (" . number_format($percentage, 1) . "%)");
            }
        }

        // Show sample skipped records
        if (!empty($this->skippedSamples)) {
            $this->info("\n📋 Sample Skipped Records:");

            foreach ($this->skippedSamples as $sample) {
                $this->warn("\nRow {$sample['row']} - {$sample['reason']}:");
                $this->line("  Reason: {$sample['details']}");
                $this->line("  Raw data: " . json_encode($sample['raw_data']));
            }

            if (count($this->skippedSamples) >= $this->option('sample-skips')) {
                $this->info("\n💡 Showing first " . $this->option('sample-skips') . " samples. Increase --sample-skips for more.");
            }
        }

        // Recommendations
        $this->info("\n💡 Recommendations:");

        if ($this->skipReasons['empty_name_and_phone'] > 0) {
            $this->warn("  • {$this->skipReasons['empty_name_and_phone']} records have both empty name and phone");
            $this->line("    - Check if data is in different columns");
            $this->line("    - Verify column mapping is correct");
        }

        if ($this->skipReasons['invalid_phone_format'] > 0) {
            $this->warn("  • {$this->skipReasons['invalid_phone_format']} records have invalid phone formats");
            $this->line("    - Check phone number formats in source data");
            $this->line("    - Consider updating phone cleaning logic");
        }

        if ($this->skipReasons['duplicate_phone'] > 0) {
            $this->warn("  • {$this->skipReasons['duplicate_phone']} records are duplicates");
            $this->line("    - Use --clean-duplicates to remove existing duplicates first");
        }
    }

    /**
     * Clean phone number to standard format
     */
    private function cleanPhone(string $phone): string
    {
        // Remove all non-numeric characters
        $cleaned = preg_replace('/[^0-9]/', '', $phone);

        if (strlen($cleaned) < 9) {
            return '';
        }

        // Handle different formats
        if (str_starts_with($cleaned, '255') && strlen($cleaned) == 12) {
            return $cleaned;
        }

        if (str_starts_with($cleaned, '0') && strlen($cleaned) == 10) {
            return '255' . substr($cleaned, 1);
        }

        if (strlen($cleaned) == 9) {
            return '255' . $cleaned;
        }

        // For longer numbers, try to extract valid phone
        if (strlen($cleaned) > 12) {
            if (preg_match('/255(\d{9})/', $cleaned, $matches)) {
                return '255' . $matches[1];
            }
        }

        return '';
    }

    /**
     * Clean and format name
     */
    private function cleanName(string $name): string
    {
        $name = trim($name);
        $name = preg_replace('/\s+/', ' ', $name);
        return ucwords(strtolower($name));
    }

    /**
     * Parse location string into components
     */
    private function parseLocation(string $location): array
    {
        $location = trim(strtoupper($location));

        $regionMap = [
            'DAR ES SALAAM' => ['region' => 'Dar es Salaam', 'district' => null, 'ward' => null],
            'TANGA' => ['region' => 'Tanga', 'district' => null, 'ward' => null],
            'MOROGORO' => ['region' => 'Morogoro', 'district' => null, 'ward' => null],
            'MBEYA' => ['region' => 'Mbeya', 'district' => null, 'ward' => null],
            'ILALA' => ['region' => 'Dar es Salaam', 'district' => 'Ilala', 'ward' => null],
            'MBEZI' => ['region' => 'Dar es Salaam', 'district' => 'Kinondoni', 'ward' => 'Mbezi'],
            'TUNDUMA' => ['region' => 'Mbeya', 'district' => 'Mbozi', 'ward' => null],
        ];

        return $regionMap[$location] ?? [
            'region' => $location ?: null,
            'district' => null,
            'ward' => null
        ];
    }

    /**
     * Infer gender from name
     */
    private function inferGender(string $name): string
    {
        $name = strtolower($name);

        $maleNames = ['james', 'john', 'joseph', 'david', 'william', 'robert', 'kenneth', 'steven'];
        $femaleNames = ['lucy', 'mary', 'elizabeth', 'grace', 'joyce', 'anna'];

        foreach ($maleNames as $maleName) {
            if (str_contains($name, $maleName)) {
                return 'male';
            }
        }

        foreach ($femaleNames as $femaleName) {
            if (str_contains($name, $femaleName)) {
                return 'female';
            }
        }

        return 'other';
    }

    /**
     * Validate a single record
     */
    private function validateRecord(array $record, int $rowNumber): array
    {
        $errors = [];
        $warnings = [];

        $identifier = $record['identifier'] ?? $record[0] ?? '';
        $fullName = $record['full_name'] ?? $record[1] ?? '';

        // Check required fields
        if (empty(trim($fullName)) && empty(trim($identifier))) {
            $errors[] = "Row {$rowNumber}: Both name and phone are empty";
        }

        // Validate phone format
        if (!empty($identifier)) {
            $cleanedPhone = $this->cleanPhone($identifier);
            if (empty($cleanedPhone)) {
                $warnings[] = "Row {$rowNumber}: Invalid phone format: {$identifier}";
            }
        }

        return ['errors' => $errors, 'warnings' => $warnings];
    }

    /**
     * Check if record is duplicate
     */
    private function isDuplicate(array $record): bool
    {
        return LegacyFundi::where('primary_phone', $record['primary_phone'])->exists();
    }

    /**
     * Insert chunk of records
     */
    private function insertChunk(array $records, bool $isDryRun): void
    {
        if ($isDryRun) {
            $this->stats['imported'] += count($records);
            return;
        }

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
     * Run automatic matching
     */
    private function autoMatch(): void
    {
        $confidence = (int) $this->option('confidence');
        $isDryRun = $this->option('dry-run');

        $this->info("\n🔗 Running automatic matching (confidence: {$confidence}%)...");

        $unmatched = LegacyFundi::whereNotIn('id', function ($query) {
            $query->select('legacy_id')
                ->from('fundis')
                ->whereNotNull('legacy_id');
        })->get();

        if ($unmatched->isEmpty()) {
            $this->info("No unmatched legacy records found.");
            return;
        }

        $progressBar = $this->output->createProgressBar($unmatched->count());
        $progressBar->start();

        foreach ($unmatched as $legacy) {
            $matches = $legacy->findPotentialMatches();
            $highConfidenceMatch = $matches->where('confidence', '>=', $confidence)->first();

            if ($highConfidenceMatch) {
                $fundi = $highConfidenceMatch['fundi'];

                if (!$fundi->legacy_match) {
                    if (!$isDryRun) {
                        $fundi->update([
                            'legacy_match' => true,
                            'legacy_id' => $legacy->id,
                        ]);
                    }
                    $this->stats['matched']++;
                }
            } elseif ($matches->count() > 0) {
                $this->stats['potential_matches']++;
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();

        if ($this->stats['matched'] > 0) {
            $this->info("✅ Auto-matched {$this->stats['matched']} legacy records!");
        } else {
            $this->warn("No matches found with {$confidence}% confidence threshold");
        }
    }

    /**
     * Clean duplicate records
     */
    private function cleanDuplicates(): void
    {
        $this->info("\n🧹 Cleaning duplicate records...");
        $isDryRun = $this->option('dry-run');

        $duplicatesRemoved = 0;

        // Remove phone duplicates
        $phoneGroups = LegacyFundi::select('primary_phone', DB::raw('COUNT(*) as count'))
            ->groupBy('primary_phone')
            ->having('count', '>', 1)
            ->get();

        foreach ($phoneGroups as $group) {
            $duplicates = LegacyFundi::where('primary_phone', $group->primary_phone)
                ->orderBy('created_at')
                ->get();

            $toRemove = $duplicates->skip(1);
            foreach ($toRemove as $duplicate) {
                if (!$isDryRun) {
                    $duplicate->delete();
                }
                $duplicatesRemoved++;
            }
        }

        $this->info("Removed {$duplicatesRemoved} duplicate records");
    }

    /**
     * Display final results
     */
    private function displayResults(): void
    {
        $this->newLine();
        $this->info('🎉 Import Process Completed!');
        $this->newLine();

        $data = [
            ['Total rows processed', number_format($this->stats['total_rows'])],
            ['Successfully imported', number_format($this->stats['imported'])],
            ['Duplicates skipped', number_format($this->stats['duplicates'])],
            ['Records skipped', number_format($this->stats['skipped'])],
            ['Errors encountered', number_format($this->stats['errors'])],
        ];

        if ($this->option('auto-match')) {
            $data[] = ['Auto-matched records', number_format($this->stats['matched'])];
            $data[] = ['Potential matches found', number_format($this->stats['potential_matches'])];
        }

        $data[] = ['Success rate', $this->calculateSuccessRate() . '%'];

        $this->table(['Metric', 'Count'], $data);

        // Additional information
        if ($this->stats['errors'] > 0) {
            $this->warn("⚠️  Check the logs for detailed error information.");
        }

        if ($this->stats['potential_matches'] > 0) {
            $this->info("💡 Run 'php artisan legacy:manage auto-match' to review potential matches manually.");
        }

        if ($this->option('dry-run')) {
            $this->warn("🔍 This was a dry run - no actual changes were made to the database.");
        }

        // Show skip breakdown
        if ($this->stats['skipped'] > 0) {
            $this->warn("\n⚠️  Skip Breakdown:");
            foreach ($this->skipReasons as $reason => $count) {
                if ($count > 0) {
                    $this->line("  • " . str_replace('_', ' ', ucfirst($reason)) . ": " . number_format($count));
                }
            }

            $this->info("\n💡 To see detailed skip analysis, run with --debug-skips option");
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

<?php

namespace App\Imports;

use App\Models\LegacyFundi;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithStartRow;

class LegacyFundiImport implements ToCollection, WithBatchInserts, WithChunkReading, WithStartRow
{
    private $importedCount = 0;

    private $skippedCount = 0;

    private $duplicatesCount = 0;

    private $errors = [];

    private $fileAnalysis;

    private $skipEmptyColumns;

    private $jobId;

    private $progressCallback;

    private $processedRows = 0;

    private $totalRows = 0;

    // Skip reasons tracking (same as command)
    private $skipReasons = [
        'empty_name_and_phone' => 0,
        'invalid_phone_format' => 0,
        'empty_name_no_phone' => 0,
        'processing_error' => 0,
        'duplicate_phone' => 0,
    ];

    private $skippedSamples = [];

    public function __construct(array $fileAnalysis, bool $skipEmptyColumns = true, ?string $jobId = null)
    {
        $this->fileAnalysis = $fileAnalysis;
        $this->skipEmptyColumns = $skipEmptyColumns;
        $this->jobId = $jobId;
        $this->totalRows = $fileAnalysis['total_rows'] ?? 0;
    }

    /**
     * Set progress callback for job updates
     */
    public function setProgressCallback(callable $callback): void
    {
        $this->progressCallback = $callback;
    }

    /**
     * Get starting row based on file analysis
     */
    public function startRow(): int
    {
        return $this->fileAnalysis['data_start_row'] + 1; // +1 because Laravel Excel is 1-indexed
    }

    /**
     * Process the collection in chunks with enhanced logic
     */
    public function collection(Collection $rows)
    {
        $validRows = [];
        $mapping = $this->fileAnalysis['mapping'];

        foreach ($rows as $index => $row) {
            $this->processedRows++;

            try {
                // Convert row to array and apply mapping
                $rowArray = $this->applyMapping($row->toArray(), $mapping);
                $cleanedData = $this->processRow($rowArray, $this->processedRows);

                if ($cleanedData) {
                    if (! $this->isDuplicate($cleanedData)) {
                        $validRows[] = $cleanedData;
                        $this->importedCount++;
                    } else {
                        $this->duplicatesCount++;
                        $this->skippedCount++;
                        $this->skipReasons['duplicate_phone']++;

                        if (count($this->skippedSamples) < 10) {
                            $this->addSkippedSample(
                                $rowArray,
                                $this->processedRows,
                                'duplicate_phone',
                                "Phone {$cleanedData['primary_phone']} already exists"
                            );
                        }
                    }
                } else {
                    $this->skippedCount++;
                }

                // Update progress periodically
                if ($this->processedRows % 100 === 0 && $this->progressCallback) {
                    $percentage = $this->totalRows > 0 ? ($this->processedRows / $this->totalRows) * 100 : 0;
                    ($this->progressCallback)($percentage, "Processing row {$this->processedRows}...");
                }

            } catch (\Exception $e) {
                $this->errors[] = 'Row '.($this->processedRows).': '.$e->getMessage();
                $this->skippedCount++;
                $this->skipReasons['processing_error']++;

                Log::warning('Import error on row '.$this->processedRows, [
                    'error' => $e->getMessage(),
                    'row' => $row->toArray(),
                    'job_id' => $this->jobId,
                ]);

                if (count($this->skippedSamples) < 10) {
                    $this->addSkippedSample(
                        $row->toArray(),
                        $this->processedRows,
                        'processing_error',
                        $e->getMessage()
                    );
                }
            }
        }

        // Batch insert valid rows
        if (! empty($validRows)) {
            LegacyFundi::insert($validRows);
        }
    }

    /**
     * Apply column mapping to row data
     */
    private function applyMapping(array $row, array $mapping): array
    {
        $mappedRow = [
            'identifier' => '',
            'full_name' => '',
            'location' => '',
        ];

        foreach ($mapping as $field => $columnIndex) {
            if ($columnIndex !== null && isset($row[$columnIndex])) {
                $mappedRow[$field] = $row[$columnIndex];
            }
        }

        return $mappedRow;
    }

    /**
     * Process a single row with enhanced logic from command
     */
    private function processRow(array $row, int $rowNumber): ?array
    {
        try {
            $identifier = $row['identifier'] ?? '';
            $fullName = $row['full_name'] ?? '';
            $location = $row['location'] ?? '';

            // Clean data
            $phone = $this->cleanPhone($identifier);
            $name = $this->cleanName($fullName);
            $locationData = $this->parseLocation($location);

            // Enhanced validation logic from command
            if (empty($name) && empty($phone)) {
                $this->skipReasons['empty_name_and_phone']++;
                $this->addSkippedSample(
                    $row,
                    $rowNumber,
                    'empty_name_and_phone',
                    'Both name and phone are empty after cleaning'
                );

                return null;
            }

            // Generate name if missing but has phone
            if (empty($name) && ! empty($phone)) {
                $name = 'User '.substr($phone, -4);
            }

            // Skip if no valid phone
            if (empty($phone)) {
                if (empty($name)) {
                    $this->skipReasons['empty_name_no_phone']++;
                    $this->addSkippedSample(
                        $row,
                        $rowNumber,
                        'empty_name_no_phone',
                        'Name is empty and no valid phone found'
                    );
                } else {
                    $this->skipReasons['invalid_phone_format']++;
                    $this->addSkippedSample(
                        $row,
                        $rowNumber,
                        'invalid_phone_format',
                        "Invalid phone format: '{$identifier}'"
                    );
                }

                return null;
            }

            return [
                'name' => $name,
                'gender' => $this->inferGender($name),
                'nida_number' => null,
                'primary_phone' => $phone,
                'transaction_phone' => $phone,
                'ranking' => 'fundi_msaidizi',
                'profile_photo' => null,
                'status' => 'pending',
                'region' => $locationData['region'],
                'district' => $locationData['district'],
                'ward' => $locationData['ward'],
                'created_at' => now(),
                'updated_at' => now(),
            ];

        } catch (\Exception $e) {
            $this->skipReasons['processing_error']++;
            throw $e;
        }
    }

    /**
     * Add skipped sample for debugging
     */
    private function addSkippedSample(array $record, int $rowNumber, string $reason, string $details)
    {
        if (count($this->skippedSamples) < 10) {
            $this->skippedSamples[] = [
                'row' => $rowNumber,
                'reason' => $reason,
                'details' => $details,
                'raw_data' => array_slice($record, 0, 5),
            ];
        }
    }

    /**
     * Enhanced phone cleaning from command
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
            return '255'.substr($cleaned, 1);
        }

        if (strlen($cleaned) == 9) {
            return '255'.$cleaned;
        }

        // For longer numbers, try to extract valid phone
        if (strlen($cleaned) > 12) {
            if (preg_match('/255(\d{9})/', $cleaned, $matches)) {
                return '255'.$matches[1];
            }
        }

        return '';
    }

    /**
     * Clean and standardize name
     */
    private function cleanName(string $name): string
    {
        $name = trim($name);
        $name = preg_replace('/\s+/', ' ', $name);

        return ucwords(strtolower($name));
    }

    /**
     * Enhanced location parsing from command
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
            'ARUSHA' => ['region' => 'Arusha', 'district' => null, 'ward' => null],
            'MWANZA' => ['region' => 'Mwanza', 'district' => null, 'ward' => null],
            'DODOMA' => ['region' => 'Dodoma', 'district' => null, 'ward' => null],
        ];

        return $regionMap[$location] ?? [
            'region' => $location ?: null,
            'district' => null,
            'ward' => null,
        ];
    }

    /**
     * Enhanced gender inference
     */
    private function inferGender(string $name): string
    {
        $name = strtolower($name);

        // Common Tanzanian male names
        $maleNames = [
            'james', 'john', 'joseph', 'david', 'william', 'robert', 'kenneth', 'steven',
            'ally', 'hassan', 'ibrahim', 'abdallah', 'salim', 'mohammed', 'hamisi',
            'frank', 'charles', 'peter', 'michael', 'daniel', 'emmanuel',
        ];

        // Common Tanzanian female names
        $femaleNames = [
            'lucy', 'mary', 'elizabeth', 'grace', 'joyce', 'anna', 'fatuma', 'amina',
            'aisha', 'zainab', 'rehema', 'happy', 'gloria', 'agnes', 'christina',
            'rose', 'jane', 'esther', 'sarah', 'ruth',
        ];

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
     * Enhanced duplicate checking
     */
    private function isDuplicate(array $data): bool
    {
        return LegacyFundi::where('primary_phone', $data['primary_phone'])
            ->orWhere(function ($query) use ($data) {
                $query->where('name', $data['name'])
                    ->where('region', $data['region']);
            })
            ->exists();
    }

    /**
     * Required methods for Excel package
     */
    public function batchSize(): int
    {
        return 1000;
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    /**
     * Get comprehensive import statistics
     */
    public function getStats(): array
    {
        return [
            'imported' => $this->importedCount,
            'skipped' => $this->skippedCount,
            'duplicates' => $this->duplicatesCount,
            'errors' => $this->errors,
            'skip_reasons' => $this->skipReasons,
            'skipped_samples' => $this->skippedSamples,
            'processed_rows' => $this->processedRows,
            'success_rate' => $this->processedRows > 0 ?
                round(($this->importedCount / $this->processedRows) * 100, 2) : 0,
        ];
    }
}

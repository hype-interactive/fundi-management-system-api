<?php

namespace App\Imports;

use App\Models\LegacyFundi;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class LegacyFundiImport implements ToCollection, WithHeadingRow, WithBatchInserts, WithChunkReading
{
    private $importedCount = 0;
    private $skippedCount = 0;
    private $errors = [];

    /**
     * Process the collection in chunks
     */
    public function collection(Collection $rows)
    {
        $validRows = [];

        foreach ($rows as $index => $row) {
            try {
                $cleanedData = $this->processRow($row->toArray());

                if ($cleanedData) {
                    // Check for duplicates
                    if (!$this->isDuplicate($cleanedData)) {
                        $validRows[] = $cleanedData;
                        $this->importedCount++;
                    } else {
                        $this->skippedCount++;
                    }
                }
            } catch (\Exception $e) {
                $this->errors[] = "Row " . ($index + 2) . ": " . $e->getMessage();
                $this->skippedCount++;
                Log::warning("Import error on row " . ($index + 2), [
                    'error' => $e->getMessage(),
                    'row' => $row->toArray()
                ]);
            }
        }

        // Batch insert valid rows
        if (!empty($validRows)) {
            LegacyFundi::insert($validRows);
        }
    }

    /**
     * Process a single row from CSV
     */
    private function processRow(array $row): ?array
    {
        // Map CSV columns based on your structure
        // identifier = phone, full_name = name, location = region/district/ward

        $phone = $this->cleanPhone($row['identifier'] ?? '');
        $name = $this->cleanName($row['full_name'] ?? '');
        $location = $this->parseLocation($row['location'] ?? '');

        // Skip if essential data is missing
        if (empty($phone) || empty($name)) {
            return null;
        }

        return [
            'name' => $name,
            'gender' => $this->inferGender($name), // You can improve this logic
            'nida_number' => null, // Not in your CSV
            'primary_phone' => $phone,
            'transaction_phone' => null,
            'ranking' => 'fundi_msaidizi', // Default
            'profile_photo' => null,
            'status' => 'pending',
            'region' => $location['region'],
            'district' => $location['district'],
            'ward' => $location['ward'],
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Clean phone number to standardized format
     */
    private function cleanPhone(string $phone): ?string
    {
        // Remove any non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Handle Tanzanian format
        if (str_starts_with($phone, '255')) {
            $phone = substr($phone, 3);
        } elseif (str_starts_with($phone, '0')) {
            $phone = substr($phone, 1);
        }

        // Validate format (9 digits starting with 6, 7, or 8)
        if (preg_match('/^[678]\d{8}$/', $phone)) {
            return '+255' . $phone;
        }

        return null;
    }

    /**
     * Clean and standardize name
     */
    private function cleanName(string $name): string
    {
        $name = trim($name);
        $name = preg_replace('/\s+/', ' ', $name); // Remove extra spaces
        return ucwords(strtolower($name));
    }

    /**
     * Parse location string into components
     */
    private function parseLocation(string $location): array
    {
        $location = trim(strtoupper($location));

        // Common Tanzanian regions for basic mapping
        $regionMap = [
            'DAR ES SALAAM' => ['region' => 'Dar es Salaam', 'district' => null, 'ward' => null],
            'TANGA' => ['region' => 'Tanga', 'district' => null, 'ward' => null],
            'MOROGORO' => ['region' => 'Morogoro', 'district' => null, 'ward' => null],
            'MBEYA' => ['region' => 'Mbeya', 'district' => null, 'ward' => null],
            'ILALA' => ['region' => 'Dar es Salaam', 'district' => 'Ilala', 'ward' => null],
            'MBEZI' => ['region' => 'Dar es Salaam', 'district' => 'Kinondoni', 'ward' => 'Mbezi'],
            'TUNDUMA' => ['region' => 'Mbeya', 'district' => 'Mbozi', 'ward' => null],
        ];

        if (isset($regionMap[$location])) {
            return $regionMap[$location];
        }

        // Default fallback
        return [
            'region' => $location ?: null,
            'district' => null,
            'ward' => null
        ];
    }

    /**
     * Basic gender inference from name (improve as needed)
     */
    private function inferGender(string $name): string
    {
        $name = strtolower($name);

        // Common Tanzanian male names
        $maleNames = ['james', 'john', 'joseph', 'david', 'william', 'robert', 'kenneth', 'steven'];
        // Common Tanzanian female names
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

        return 'other'; // Default when unclear
    }

    /**
     * Check if record already exists
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
     * Get import statistics
     */
    public function getStats(): array
    {
        return [
            'imported' => $this->importedCount,
            'skipped' => $this->skippedCount,
            'errors' => $this->errors,
        ];
    }
}

<?php

namespace App\Imports;

use App\Models\LegacyFundi;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class LegacyFundiImport implements ToModel, WithHeadingRow, WithValidation, WithBatchInserts, WithChunkReading
{
    private $importedCount = 0;
    private $skippedCount = 0;

    /**
     * Transform each row into a model
     */
    public function model(array $row)
    {
        try {
            // Clean and validate the data
            $cleanedData = $this->cleanRowData($row);

            // Check if this record already exists (by phone or NIDA)
            $exists = LegacyFundi::where(function ($query) use ($cleanedData) {
                if (!empty($cleanedData['primary_phone'])) {
                    $query->where('primary_phone', $cleanedData['primary_phone']);
                }
                if (!empty($cleanedData['nida_number'])) {
                    $query->orWhere('nida_number', $cleanedData['nida_number']);
                }
            })->exists();

            if ($exists) {
                $this->skippedCount++;
                return null; // Skip duplicate
            }

            $this->importedCount++;

            return new LegacyFundi($cleanedData);
        } catch (\Exception $e) {
            Log::warning('Failed to import legacy fundi row: ' . $e->getMessage(), $row);
            $this->skippedCount++;
            return null;
        }
    }

    /**
     * Clean and normalize row data
     */
    private function cleanRowData(array $row): array
    {
        return [
            'name' => $this->cleanName($row['name'] ?? ''),
            'gender' => $this->cleanGender($row['gender'] ?? ''),
            'nida_number' => $this->cleanNida($row['nida_number'] ?? null),
            'primary_phone' => $this->cleanPhone($row['primary_phone'] ?? ''),
            'transaction_phone' => $this->cleanPhone($row['transaction_phone'] ?? null),
            'ranking' => $this->cleanRanking($row['ranking'] ?? ''),
            'profile_photo' => $row['profile_photo'] ?? null,
            'status' => $this->cleanStatus($row['status'] ?? 'pending'),
            'region' => $this->cleanLocation($row['region'] ?? null),
            'district' => $this->cleanLocation($row['district'] ?? null),
            'ward' => $this->cleanLocation($row['ward'] ?? null),
        ];
    }

    /**
     * Clean name field
     */
    private function cleanName(string $name): string
    {
        // Remove extra spaces, capitalize properly
        $name = trim(preg_replace('/\s+/', ' ', $name));
        return ucwords(strtolower($name));
    }

    /**
     * Clean gender field
     */
    private function cleanGender(string $gender): string
    {
        $gender = strtolower(trim($gender));

        // Map common variations
        $genderMap = [
            'm' => 'male',
            'male' => 'male',
            'mume' => 'male',
            'kiume' => 'male',
            'f' => 'female',
            'female' => 'female',
            'kike' => 'female',
            'jike' => 'female',
        ];

        return $genderMap[$gender] ?? 'other';
    }

    /**
     * Clean NIDA number
     */
    private function cleanNida(?string $nida): ?string
    {
        if (empty($nida)) {
            return null;
        }

        // Remove any non-numeric characters
        $nida = preg_replace('/[^0-9]/', '', $nida);

        // Validate NIDA format (should be 20 digits)
        if (strlen($nida) === 20) {
            return $nida;
        }

        return null; // Invalid NIDA
    }

    /**
     * Clean phone number
     */
    private function cleanPhone(?string $phone): ?string
    {
        if (empty($phone)) {
            return null;
        }

        // Remove any non-numeric characters except +
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        // Handle different formats
        if (str_starts_with($phone, '+255')) {
            $phone = substr($phone, 4); // Remove +255
        } elseif (str_starts_with($phone, '255')) {
            $phone = substr($phone, 3); // Remove 255
        } elseif (str_starts_with($phone, '0')) {
            $phone = substr($phone, 1); // Remove leading 0
        }

        // Validate Tanzanian mobile format (9 digits starting with 6, 7, or 8)
        if (preg_match('/^[678]\d{8}$/', $phone)) {
            return '+255' . $phone;
        }

        return null; // Invalid phone format
    }

    /**
     * Clean ranking field
     */
    private function cleanRanking(string $ranking): string
    {
        $ranking = strtolower(trim($ranking));

        $rankingMap = [
            'fundi_kuu' => 'fundi_kuu',
            'fundi kuu' => 'fundi_kuu',
            'mkuu' => 'fundi_kuu',
            'fundi_msaidizi' => 'fundi_msaidizi',
            'fundi msaidizi' => 'fundi_msaidizi',
            'msaidizi' => 'fundi_msaidizi',
            'fundi' => 'fundi_msaidizi',
        ];

        return $rankingMap[$ranking] ?? 'fundi_msaidizi';
    }

    /**
     * Clean status field
     */
    private function cleanStatus(string $status): string
    {
        $status = strtolower(trim($status));

        $statusMap = [
            'active' => 'active',
            'inactive' => 'inactive',
            'pending' => 'pending',
            'approved' => 'active',
            'rejected' => 'inactive',
        ];

        return $statusMap[$status] ?? 'pending';
    }

    /**
     * Clean location fields
     */
    private function cleanLocation(?string $location): ?string
    {
        if (empty($location)) {
            return null;
        }

        // Clean and capitalize properly
        $location = trim(preg_replace('/\s+/', ' ', $location));
        return ucwords(strtolower($location));
    }

    /**
     * Validation rules
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'gender' => 'nullable|in:male,female,other',
            'primary_phone' => 'required|string|max:20',
            'ranking' => 'nullable|in:fundi_kuu,fundi_msaidizi',
            'status' => 'nullable|in:active,inactive,pending',
        ];
    }

    /**
     * Custom validation messages
     */
    public function customValidationMessages(): array
    {
        return [
            'name.required' => 'Name is required for all legacy fundis.',
            'primary_phone.required' => 'Primary phone is required for all legacy fundis.',
        ];
    }

    /**
     * Batch insert size
     */
    public function batchSize(): int
    {
        return 500;
    }

    /**
     * Chunk reading size
     */
    public function chunkSize(): int
    {
        return 500;
    }

    /**
     * Get import statistics
     */
    public function getImportedCount(): int
    {
        return $this->importedCount;
    }

    public function getSkippedCount(): int
    {
        return $this->skippedCount;
    }

    /**
     * Headers that are expected in the import file
     */
    public function getExpectedHeaders(): array
    {
        return [
            'name',
            'gender',
            'nida_number',
            'primary_phone',
            'transaction_phone',
            'ranking',
            'profile_photo',
            'status',
            'region',
            'district',
            'ward'
        ];
    }
}

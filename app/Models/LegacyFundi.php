<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class LegacyFundi extends Model
{
    use HasFactory;

    protected $table = 'legacy_fundis';

    protected $fillable = [
        'name',
        'gender',
        'nida_number',
        'primary_phone',
        'transaction_phone',
        'ranking',
        'profile_photo',
        'status'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Scopes for common queries
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('status', 'inactive');
    }

    public function scopeByRanking(Builder $query, string $ranking): Builder
    {
        return $query->where('ranking', $ranking);
    }

    public function scopeByGender(Builder $query, string $gender): Builder
    {
        return $query->where('gender', $gender);
    }

    // Accessors
    public function getFormattedPhoneAttribute(): string
    {
        return $this->formatPhoneNumber($this->primary_phone);
    }

    public function getFormattedTransactionPhoneAttribute(): ?string
    {
        return $this->transaction_phone ? $this->formatPhoneNumber($this->transaction_phone) : null;
    }

    // Mutators
    public function setPrimaryPhoneAttribute($value): void
    {
        $this->attributes['primary_phone'] = $this->cleanPhoneNumber($value);
    }

    public function setTransactionPhoneAttribute($value): void
    {
        $this->attributes['transaction_phone'] = $value ? $this->cleanPhoneNumber($value) : null;
    }

    public function setNameAttribute($value): void
    {
        $this->attributes['name'] = $this->cleanName($value);
    }

    // Helper methods
    private function formatPhoneNumber(string $phone): string
    {
        // Format phone number for display (e.g., +255 XXX XXX XXX)
        if (preg_match('/^255(\d{3})(\d{3})(\d{3})$/', $phone, $matches)) {
            return "+255 {$matches[1]} {$matches[2]} {$matches[3]}";
        }

        return $phone;
    }

    private function cleanPhoneNumber(string $phone): string
    {
        // Remove non-numeric characters except +
        $cleaned = preg_replace('/[^0-9+]/', '', $phone);

        // Convert to standard format (255XXXXXXXXX)
        if (preg_match('/^\+?255(\d{9})$/', $cleaned, $matches)) {
            return '255' . $matches[1];
        } elseif (preg_match('/^0(\d{9})$/', $cleaned, $matches)) {
            return '255' . $matches[1];
        } elseif (preg_match('/^(\d{9})$/', $cleaned, $matches)) {
            return '255' . $matches[1];
        }

        return $cleaned;
    }

    private function cleanName(string $name): string
    {
        // Clean and format name
        $cleaned = trim($name);
        $cleaned = preg_replace('/\s+/', ' ', $cleaned);
        return mb_convert_case($cleaned, MB_CASE_TITLE, 'UTF-8');
    }

    // Static methods for data analysis
    public static function getDuplicatesByPhone(): array
    {
        return self::select('primary_phone')
            ->groupBy('primary_phone')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('primary_phone')
            ->toArray();
    }

    public static function getDuplicatesByNida(): array
    {
        return self::select('nida_number')
            ->whereNotNull('nida_number')
            ->groupBy('nida_number')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('nida_number')
            ->toArray();
    }

    public static function getStatsByLocation(): array
    {
        // This would require adding a location field to your migration
        // or extracting location from another field
        return [];
    }

    public static function getImportStats(): array
    {
        return [
            'total_records' => self::count(),
            'active_records' => self::active()->count(),
            'pending_records' => self::pending()->count(),
            'inactive_records' => self::inactive()->count(),
            'male_count' => self::byGender('male')->count(),
            'female_count' => self::byGender('female')->count(),
            'fundi_kuu_count' => self::byRanking('fundi_kuu')->count(),
            'fundi_msaidizi_count' => self::byRanking('fundi_msaidizi')->count(),
            'with_nida' => self::whereNotNull('nida_number')->count(),
            'without_nida' => self::whereNull('nida_number')->count()
        ];
    }
}

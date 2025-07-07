<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LegacyFundi extends Model
{
    use HasFactory;

    protected $fillable = [
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
        'ward',
    ];

    /**
     * Get the fundi that this legacy record is matched to
     */
    public function matchedFundi()
    {
        return $this->hasOne(Fundi::class, 'legacy_id', 'id');
    }

    /**
     * Check if this legacy fundi is already matched
     */
    public function getIsMatchedAttribute(): bool
    {
        return $this->matchedFundi()->exists();
    }

    /**
     * Get match status for UI display
     */
    public function getMatchStatusAttribute(): string
    {
        if ($this->is_matched) {
            return 'matched';
        }

        $potentialCount = $this->getPotentialMatchesCount();
        return $potentialCount > 0 ? 'potential' : 'unmatched';
    }

    /**
     * Get count of potential matches (cached for performance)
     */
    public function getPotentialMatchesCount(): int
    {
        return cache()->remember(
            "legacy_fundi_{$this->id}_potential_matches_count",
            now()->addMinutes(30),
            fn() => $this->findPotentialMatches()->count()
        );
    }

    /**
     * Find potential matches with current fundis
     * Simplified matching logic with clear priorities
     */
    public function findPotentialMatches()
    {
        $matches = collect();

        // 1. NIDA Match (Highest Priority - 95% confidence)
        if ($this->nida_number) {
            $nidaMatches = Fundi::where('nida_number', $this->nida_number)
                ->whereNull('legacy_id') // Not already matched
                ->get();

            foreach ($nidaMatches as $fundi) {
                $matches->push([
                    'fundi' => $fundi,
                    'match_type' => 'nida',
                    'confidence' => 95,
                    'details' => 'Exact NIDA number match'
                ]);
            }
        }

        // 2. Phone Match (High Priority - 85% confidence)
        $phoneMatches = Fundi::where(function ($query) {
            if ($this->primary_phone) {
                $query->where('primary_phone', $this->primary_phone)
                    ->orWhere('transaction_phone', $this->primary_phone);
            }
            if ($this->transaction_phone && $this->transaction_phone !== $this->primary_phone) {
                $query->orWhere('primary_phone', $this->transaction_phone)
                    ->orWhere('transaction_phone', $this->transaction_phone);
            }
        })
            ->whereNull('legacy_id')
            ->get();

        foreach ($phoneMatches as $fundi) {
            // Skip if already matched by NIDA
            if ($matches->where('fundi.id', $fundi->id)->isNotEmpty()) {
                continue;
            }

            $matches->push([
                'fundi' => $fundi,
                'match_type' => 'phone',
                'confidence' => 85,
                'details' => 'Phone number match'
            ]);
        }

        // 3. Name Similarity (Medium Priority - 60-80% confidence)
        if (!$matches->isEmpty()) {
            // Skip name matching if we have high-confidence matches
            return $matches->sortByDesc('confidence');
        }

        $nameMatches = $this->findNameMatches();
        $matches = $matches->merge($nameMatches);

        return $matches->sortByDesc('confidence')->take(10); // Limit to top 10 matches
    }

    /**
     * Find matches based on name similarity
     */
    private function findNameMatches()
    {
        $matches = collect();
        $cleanLegacyName = $this->cleanNameForMatching($this->name);

        if (empty($cleanLegacyName)) {
            return $matches;
        }

        // Get fundis with similar names in same region (if available)
        $query = Fundi::whereNull('legacy_id');

        if ($this->region) {
            $query->whereHas('ward.district.region', function ($q) {
                $q->where('name', 'like', '%' . $this->region . '%');
            });
        }

        $fundis = $query->get();

        foreach ($fundis as $fundi) {
            $fundiName = $this->cleanNameForMatching($fundi->full_name);
            $similarity = $this->calculateNameSimilarity($cleanLegacyName, $fundiName);

            if ($similarity >= 0.6) { // 60% similarity threshold
                $confidence = min(80, round($similarity * 100)); // Cap at 80%

                $matches->push([
                    'fundi' => $fundi,
                    'match_type' => 'name',
                    'confidence' => $confidence,
                    'details' => "Name similarity: {$confidence}%"
                ]);
            }
        }

        return $matches;
    }

    /**
     * Clean name for matching
     */
    private function cleanNameForMatching(string $name): string
    {
        // Remove common prefixes/suffixes, normalize spaces
        $name = strtolower(trim($name));
        $name = preg_replace('/\s+/', ' ', $name);

        // Remove common prefixes
        $prefixes = ['mr', 'mrs', 'ms', 'dr', 'prof', 'eng', 'dkt'];
        foreach ($prefixes as $prefix) {
            $name = preg_replace('/^' . $prefix . '\s+/', '', $name);
        }

        return $name;
    }

    /**
     * Calculate similarity between two names
     */
    private function calculateNameSimilarity(string $name1, string $name2): float
    {
        // Use Levenshtein distance for similarity
        $maxLen = max(strlen($name1), strlen($name2));
        if ($maxLen === 0) return 0;

        $distance = levenshtein($name1, $name2);
        return 1 - ($distance / $maxLen);
    }

    /**
     * Auto-link with high confidence match
     */
    public function autoLink(): bool
    {
        $matches = $this->findPotentialMatches();
        $highConfidenceMatch = $matches->where('confidence', '>=', 90)->first();

        if ($highConfidenceMatch && $highConfidenceMatch['match_type'] === 'nida') {
            $fundi = $highConfidenceMatch['fundi'];

            $fundi->update([
                'legacy_match' => true,
                'legacy_id' => $this->id,
            ]);

            // Clear cache
            cache()->forget("legacy_fundi_{$this->id}_potential_matches_count");

            return true;
        }

        return false;
    }

    /**
     * Manually link with a fundi
     */
    public function linkWith(Fundi $fundi): bool
    {
        if ($fundi->legacy_match) {
            return false; // Already linked
        }

        $fundi->update([
            'legacy_match' => true,
            'legacy_id' => $this->id,
        ]);

        // Clear cache
        cache()->forget("legacy_fundi_{$this->id}_potential_matches_count");

        return true;
    }

    /**
     * Unlink from current fundi
     */
    public function unlink(): bool
    {
        $fundi = $this->matchedFundi;

        if ($fundi) {
            $fundi->update([
                'legacy_match' => false,
                'legacy_id' => null,
            ]);

            // Clear cache
            cache()->forget("legacy_fundi_{$this->id}_potential_matches_count");

            return true;
        }

        return false;
    }

    /**
     * Scopes for filtering
     */
    public function scopeMatched($query)
    {
        return $query->whereHas('matchedFundi');
    }

    public function scopeUnmatched($query)
    {
        return $query->whereDoesntHave('matchedFundi');
    }

    public function scopeWithPotentialMatches($query)
    {
        return $query->unmatched()->whereRaw('
            EXISTS (
                SELECT 1 FROM fundis f
                WHERE f.legacy_id IS NULL
                AND (
                    f.nida_number = legacy_fundis.nida_number
                    OR f.primary_phone = legacy_fundis.primary_phone
                    OR f.transaction_phone = legacy_fundis.primary_phone
                )
            )
        ');
    }
}

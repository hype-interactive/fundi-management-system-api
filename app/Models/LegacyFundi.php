<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
        'status',
        'region',
        'district',
        'ward',
    ];

    public function getFullNameAttribute()
    {
        return $this->name;
    }

    /**
     * Find potential matches in the main fundis table
     */
    public function findPotentialMatches()
    {
        $query = Fundi::query();

        $matches = collect();

        // Exact NIDA match (highest priority)
        if ($this->nida_number) {
            $nidaMatch = $query->where('nida_number', $this->nida_number)->first();
            if ($nidaMatch) {
                $matches->push([
                    'fundi' => $nidaMatch,
                    'match_type' => 'nida',
                    'confidence' => 95,
                    'details' => 'NIDA number match'
                ]);
            }
        }

        // Phone number matches
        if ($this->primary_phone) {
            $phoneMatches = Fundi::where(function ($q) {
                $q->where('primary_phone', $this->primary_phone)
                    ->orWhere('transaction_phone', $this->primary_phone);
            })->get();

            foreach ($phoneMatches as $match) {
                if (!$matches->where('fundi.id', $match->id)->count()) {
                    $matches->push([
                        'fundi' => $match,
                        'match_type' => 'phone',
                        'confidence' => 85,
                        'details' => 'Phone number match'
                    ]);
                }
            }
        }

        // Name similarity match (fuzzy matching)
        $nameMatches = Fundi::whereRaw("MATCH(fname, mname, lname) AGAINST(? IN BOOLEAN MODE)", [$this->name])
            ->orWhereRaw("CONCAT(fname, ' ', IFNULL(mname, ''), ' ', lname) LIKE ?", ['%' . $this->name . '%'])
            ->get();

        foreach ($nameMatches as $match) {
            if (!$matches->where('fundi.id', $match->id)->count()) {
                $similarity = $this->calculateNameSimilarity($this->name, $match->full_name);
                if ($similarity > 70) {
                    $matches->push([
                        'fundi' => $match,
                        'match_type' => 'name',
                        'confidence' => $similarity,
                        'details' => "Name similarity: {$similarity}%"
                    ]);
                }
            }
        }

        return $matches->sortByDesc('confidence');
    }

    /**
     * Calculate name similarity percentage
     */
    private function calculateNameSimilarity($name1, $name2)
    {
        $name1 = strtolower(trim($name1));
        $name2 = strtolower(trim($name2));

        similar_text($name1, $name2, $percent);
        return round($percent, 2);
    }

    /**
     * Check if this legacy fundi has been matched
     */
    public function getMatchedFundiAttribute()
    {
        return Fundi::where('legacy_id', $this->id)->first();
    }

    /**
     * Check if this legacy fundi is matched
     */
    public function getIsMatchedAttribute()
    {
        return $this->matchedFundi !== null;
    }

    /**
     * Get match status for display
     */
    public function getMatchStatusAttribute()
    {
        if ($this->is_matched) {
            return 'matched';
        }

        $potentialMatches = $this->findPotentialMatches();
        if ($potentialMatches->count() > 0) {
            return 'potential';
        }

        return 'unmatched';
    }
}

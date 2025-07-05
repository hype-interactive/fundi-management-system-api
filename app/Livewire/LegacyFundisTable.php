<?php

namespace App\Livewire;

use App\Models\Fundi;
use Livewire\Component;
use App\Models\LegacyFundi;
use Livewire\Attributes\Url;
use Livewire\WithPagination;

class LegacyFundisTable extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    #[Url]
    public $search_key = '';

    public $perPage = 15;

    #[Url]
    public $region = null;

    #[Url]
    public $district = null;

    #[Url]
    public $ward = null;

    #[Url]
    public $gender = null;

    #[Url]
    public $ranking = null;

    #[Url]
    public $status = null;

    #[Url]
    public $match_status = null; // matched, potential, unmatched

    public $regions = [];
    public $districts = [];
    public $wards = [];
    public $gender_options = [];
    public $ranking_options = [];
    public $status_options = [];
    public $match_status_options = [];

    // Cross-checking properties
    public $showMatches = false;
    public $selectedLegacyId = null;
    public $potentialMatches = [];
    public $compactView = false;

    public function updatedSearchKey()
    {
        $this->resetPage();
    }

    public function updatedRegion()
    {
        $this->resetPage();
    }

    public function updatedDistrict()
    {
        $this->resetPage();
    }

    public function updatedWard()
    {
        $this->resetPage();
    }

    public function updatedGender()
    {
        $this->resetPage();
    }

    public function updatedRanking()
    {
        $this->resetPage();
    }

    public function updatedStatus()
    {
        $this->resetPage();
    }

    public function updatedMatchStatus()
    {
        $this->resetPage();
    }

    public function fetch()
    {
        return $this->getLegacyFundiQuery()->paginate($this->perPage);
    }

    public function getLegacyFundiQuery()
    {
        $this->loadData();

        $query = LegacyFundi::query();

        return $query
            ->when($this->search_key, function ($query, $search_key) {
                $query->where('name', 'like', '%' . $search_key . '%')
                    ->orWhere('primary_phone', 'like', '%' . $search_key . '%')
                    ->orWhere('transaction_phone', 'like', '%' . $search_key . '%')
                    ->orWhere('nida_number', 'like', '%' . $search_key . '%');
            })
            ->when($this->region, function ($query, $region) {
                $query->where('region', 'like', '%' . $region . '%');
            })
            ->when($this->district, function ($query, $district) {
                $query->where('district', 'like', '%' . $district . '%');
            })
            ->when($this->ward, function ($query, $ward) {
                $query->where('ward', 'like', '%' . $ward . '%');
            })
            ->when($this->gender, function ($query, $gender) {
                $query->where('gender', $gender);
            })
            ->when($this->ranking, function ($query, $ranking) {
                $query->where('ranking', $ranking);
            })
            ->when($this->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($this->match_status, function ($query, $match_status) {
                if ($match_status === 'matched') {
                    $query->whereIn('id', function($subQuery) {
                        $subQuery->select('legacy_id')
                            ->from('fundis')
                            ->whereNotNull('legacy_id');
                    });
                } elseif ($match_status === 'unmatched') {
                    $query->whereNotIn('id', function($subQuery) {
                        $subQuery->select('legacy_id')
                            ->from('fundis')
                            ->whereNotNull('legacy_id');
                    });
                }
                // For 'potential', we'll handle this in the view
            })
            ->latest('created_at');
    }

    public function loadData()
    {
        // Get unique values from legacy data for filters
        $this->regions = LegacyFundi::whereNotNull('region')
            ->distinct()
            ->pluck('region')
            ->filter()
            ->sort()
            ->values()
            ->toArray();

        $this->districts = LegacyFundi::when($this->region, function($query) {
                return $query->where('region', 'like', '%' . $this->region . '%');
            })
            ->whereNotNull('district')
            ->distinct()
            ->pluck('district')
            ->filter()
            ->sort()
            ->values()
            ->toArray();

        $this->wards = LegacyFundi::when($this->district, function($query) {
                return $query->where('district', 'like', '%' . $this->district . '%');
            })
            ->whereNotNull('ward')
            ->distinct()
            ->pluck('ward')
            ->filter()
            ->sort()
            ->values()
            ->toArray();

        $this->gender_options = ['male' => 'Male', 'female' => 'Female', 'other' => 'Other'];
        $this->ranking_options = ['fundi_kuu' => 'Fundi Mkuu', 'fundi_msaidizi' => 'Fundi'];
        $this->status_options = ['active' => 'Active', 'inactive' => 'Inactive', 'pending' => 'Pending'];
        $this->match_status_options = [
            'matched' => 'Matched',
            'potential' => 'Has Potential Matches',
            'unmatched' => 'Unmatched'
        ];
    }

    /**
     * Show potential matches for a legacy fundi
     */
    public function showMatches($legacyFundiId)
    {
        $this->selectedLegacyId = $legacyFundiId;
        $legacyFundi = LegacyFundi::find($legacyFundiId);

        if ($legacyFundi) {
            $this->potentialMatches = $legacyFundi->findPotentialMatches()->toArray();
            $this->showMatches = true;
        }
    }

    /**
     * Close matches modal
     */
    public function closeMatches()
    {
        $this->showMatches = false;
        $this->selectedLegacyId = null;
        $this->potentialMatches = [];
    }

    /**
     * Link a legacy fundi with an existing fundi
     */
    public function linkFundis($legacyFundiId, $fundiId)
    {
        $legacyFundi = LegacyFundi::find($legacyFundiId);
        $fundi = Fundi::find($fundiId);

        if ($legacyFundi && $fundi) {
            $fundi->update([
                'legacy_match' => true,
                'legacy_id' => $legacyFundiId,
            ]);

            $this->closeMatches();
            session()->flash('success', 'Legacy fundi linked successfully!');
        }
    }

    /**
     * Unlink a legacy fundi
     */
    public function unlinkFundi($legacyFundiId)
    {
        $fundi = Fundi::where('legacy_id', $legacyFundiId)->first();

        if ($fundi) {
            $fundi->update([
                'legacy_match' => false,
                'legacy_id' => null,
            ]);

            session()->flash('success', 'Legacy fundi unlinked successfully!');
        }
    }

    /**
     * Export legacy data analysis
     */
    public function exportAnalysis()
    {
        return response()->streamDownload(function () {
            $legacyFundis = $this->getLegacyFundiQuery()->get();

            $headers = [
                'Legacy ID',
                'Name',
                'Gender',
                'Primary Phone',
                'Transaction Phone',
                'NIDA Number',
                'Region',
                'District',
                'Ward',
                'Ranking',
                'Status',
                'Match Status',
                'Matched Fundi ID',
                'Potential Matches Count',
                'Created At'
            ];

            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);

            foreach ($legacyFundis as $legacy) {
                $potentialMatches = $legacy->findPotentialMatches();

                fputcsv($handle, [
                    $legacy->id,
                    $legacy->name,
                    ucfirst($legacy->gender),
                    $legacy->primary_phone,
                    $legacy->transaction_phone,
                    $legacy->nida_number,
                    $legacy->region,
                    $legacy->district,
                    $legacy->ward,
                    $this->ranking_options[$legacy->ranking] ?? 'N/A',
                    ucfirst($legacy->status),
                    ucfirst($legacy->match_status),
                    $legacy->matchedFundi?->id ?? 'N/A',
                    $potentialMatches->count(),
                    $legacy->created_at->format('Y-m-d H:i:s')
                ]);
            }

            fclose($handle);
        }, 'legacy_fundis_analysis_' . date('Y-m-d_H-i-s') . '.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Run auto-matching for high confidence matches
     */
    public function runAutoMatch()
    {
        $legacyFundis = LegacyFundi::whereNotIn('id', function($query) {
            $query->select('legacy_id')
                ->from('fundis')
                ->whereNotNull('legacy_id');
        })->get();

        $matchedCount = 0;

        foreach ($legacyFundis as $legacy) {
            $matches = $legacy->findPotentialMatches();
            $highConfidenceMatch = $matches->where('confidence', '>=', 90)->first();

            if ($highConfidenceMatch) {
                $fundi = $highConfidenceMatch['fundi'];
                $fundi->update([
                    'legacy_match' => true,
                    'legacy_id' => $legacy->id,
                ]);
                $matchedCount++;
            }
        }

        session()->flash('success', "Auto-matched {$matchedCount} legacy fundis with high confidence!");
    }

    public function refresh()
    {
        $this->reset([
            'search_key',
            'region',
            'district',
            'ward',
            'gender',
            'ranking',
            'status',
            'match_status'
        ]);
    }

    public function mount()
    {
        $this->compactView = false;
        $this->showMatches = false;
    }

    public function render()
    {
        return view('livewire.legacy-fundis-table', [
            'legacyFundis' => $this->fetch(),
        ]);
    }
}

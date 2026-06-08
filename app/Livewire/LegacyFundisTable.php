<?php

namespace App\Livewire;

use App\Models\Fundi;
use App\Models\LegacyFundi;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class LegacyFundisTable extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    #[Url]
    public $search = '';

    #[Url]
    public $region = '';

    #[Url]
    public $district = '';

    #[Url]
    public $status = '';

    #[Url]
    public $matchStatus = '';

    public $perPage = 25;

    public $compactView = false;

    // Modal states
    public $showMatchesModal = false;

    public $selectedLegacyId = null;

    public $potentialMatches = [];

    public $loadingMatches = false;

    // Filter options
    public $regions = [];

    public $districts = [];

    public $statusOptions = [
        'active' => 'Active',
        'inactive' => 'Inactive',
        'pending' => 'Pending',
    ];

    public $matchStatusOptions = [
        'matched' => 'Matched',
        'potential' => 'Has Potential Matches',
        'unmatched' => 'No Matches',
    ];

    // Bulk operations
    public $selectedItems = [];

    public $selectAll = false;

    protected $listeners = [
        'refreshTable' => '$refresh',
        'closeMatchesModal' => 'closeMatches',
    ];

    public function mount()
    {
        $this->loadFilterOptions();
    }

    public function render()
    {
        return view('livewire.legacy-fundis-table', [
            'legacyFundis' => $this->getLegacyFundis(),
            'stats' => $this->getStats(),
        ]);
    }

    /**
     * Get paginated legacy fundis with filters applied
     */
    public function getLegacyFundis()
    {
        $query = LegacyFundi::query()
            ->with('matchedFundi')
            ->when($this->search, function ($q) {
                $q->where(function ($subQuery) {
                    $subQuery->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('primary_phone', 'like', '%'.$this->search.'%')
                        ->orWhere('nida_number', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->region, fn ($q) => $q->where('region', $this->region))
            ->when($this->district, fn ($q) => $q->where('district', $this->district))
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->when($this->matchStatus, function ($q) {
                match ($this->matchStatus) {
                    'matched' => $q->matched(),
                    'unmatched' => $q->unmatched(),
                    'potential' => $q->withPotentialMatches(),
                    default => $q
                };
            })
            ->latest('created_at');

        return $query->paginate($this->perPage);
    }

    /**
     * Get dashboard statistics
     */
    public function getStats()
    {
        return Cache::remember('legacy_fundis_stats', now()->addMinutes(5), function () {
            return [
                'total' => LegacyFundi::count(),
                'matched' => LegacyFundi::matched()->count(),
                'unmatched' => LegacyFundi::unmatched()->count(),
                'potential' => LegacyFundi::withPotentialMatches()->count(),
            ];
        });
    }

    /**
     * Load filter options
     */
    public function loadFilterOptions()
    {
        $this->regions = LegacyFundi::whereNotNull('region')
            ->distinct()
            ->pluck('region')
            ->filter()
            ->sort()
            ->values()
            ->toArray();

        $this->districts = LegacyFundi::when($this->region, fn ($q) => $q->where('region', $this->region))
            ->whereNotNull('district')
            ->distinct()
            ->pluck('district')
            ->filter()
            ->sort()
            ->values()
            ->toArray();
    }

    /**
     * Updated methods to reset pagination and reload data
     */
    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedRegion()
    {
        $this->district = '';
        $this->loadFilterOptions();
        $this->resetPage();
    }

    public function updatedDistrict()
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

    /**
     * Show potential matches modal
     */
    public function showMatches($legacyFundiId)
    {
        $this->selectedLegacyId = $legacyFundiId;
        $this->loadingMatches = true;
        $this->showMatchesModal = true;
        $this->potentialMatches = [];

        // Load matches asynchronously
        $this->loadPotentialMatches();
    }

    /**
     * Load potential matches for selected legacy fundi
     */
    public function loadPotentialMatches()
    {
        if (! $this->selectedLegacyId) {
            return;
        }

        $legacyFundi = LegacyFundi::find($this->selectedLegacyId);

        if ($legacyFundi) {
            $matches = $legacyFundi->findPotentialMatches();

            $this->potentialMatches = $matches->map(function ($match) {
                return [
                    'fundi' => $match['fundi']->load('ward.district.region'),
                    'match_type' => $match['match_type'],
                    'confidence' => $match['confidence'],
                    'details' => $match['details'],
                ];
            })->toArray();
        }

        $this->loadingMatches = false;
    }

    /**
     * Close matches modal
     */
    public function closeMatches()
    {
        $this->showMatchesModal = false;
        $this->selectedLegacyId = null;
        $this->potentialMatches = [];
        $this->loadingMatches = false;
    }

    /**
     * Link legacy fundi with current fundi
     */
    public function linkFundis($legacyFundiId, $fundiId)
    {
        try {
            $legacyFundi = LegacyFundi::findOrFail($legacyFundiId);
            $fundi = Fundi::findOrFail($fundiId);

            if ($legacyFundi->linkWith($fundi)) {
                $this->closeMatches();
                $this->dispatch('showToast', [
                    'type' => 'success',
                    'message' => 'Legacy fundi linked successfully!',
                ]);

                // Clear stats cache
                Cache::forget('legacy_fundis_stats');
            } else {
                $this->dispatch('showToast', [
                    'type' => 'error',
                    'message' => 'This fundi is already linked to another legacy record.',
                ]);
            }
        } catch (\Exception $e) {
            $this->dispatch('showToast', [
                'type' => 'error',
                'message' => 'Failed to link: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * Unlink legacy fundi
     */
    public function unlinkFundi($legacyFundiId)
    {
        try {
            $legacyFundi = LegacyFundi::findOrFail($legacyFundiId);

            if ($legacyFundi->unlink()) {
                $this->dispatch('showToast', [
                    'type' => 'success',
                    'message' => 'Legacy fundi unlinked successfully!',
                ]);

                // Clear stats cache
                Cache::forget('legacy_fundis_stats');
            } else {
                $this->dispatch('showToast', [
                    'type' => 'error',
                    'message' => 'No linked fundi found for this legacy record.',
                ]);
            }
        } catch (\Exception $e) {
            $this->dispatch('showToast', [
                'type' => 'error',
                'message' => 'Failed to unlink: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * Run bulk auto-matching
     */
    public function runBulkAutoMatch()
    {
        try {
            $unmatchedCount = LegacyFundi::unmatched()->count();

            if ($unmatchedCount === 0) {
                $this->dispatch('showToast', [
                    'type' => 'info',
                    'message' => 'No unmatched legacy fundis found.',
                ]);

                return;
            }

            $matchedCount = 0;

            LegacyFundi::unmatched()->chunk(100, function ($legacyFundis) use (&$matchedCount) {
                foreach ($legacyFundis as $legacy) {
                    if ($legacy->autoLink()) {
                        $matchedCount++;
                    }
                }
            });

            $this->dispatch('showToast', [
                'type' => 'success',
                'message' => "Auto-matched {$matchedCount} legacy fundis with high confidence!",
            ]);

            // Clear stats cache
            Cache::forget('legacy_fundis_stats');

        } catch (\Exception $e) {
            $this->dispatch('showToast', [
                'type' => 'error',
                'message' => 'Auto-match failed: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * Bulk operations
     */
    public function updatedSelectAll()
    {
        if ($this->selectAll) {
            $this->selectedItems = $this->getLegacyFundis()->pluck('id')->toArray();
        } else {
            $this->selectedItems = [];
        }
    }

    public function bulkLink()
    {
        if (empty($this->selectedItems)) {
            $this->dispatch('showToast', [
                'type' => 'warning',
                'message' => 'Please select items to perform bulk operation.',
            ]);

            return;
        }

        $matchedCount = 0;

        foreach ($this->selectedItems as $legacyId) {
            $legacy = LegacyFundi::find($legacyId);
            if ($legacy && $legacy->autoLink()) {
                $matchedCount++;
            }
        }

        $this->selectedItems = [];
        $this->selectAll = false;

        $this->dispatch('showToast', [
            'type' => 'success',
            'message' => "Bulk linked {$matchedCount} legacy fundis.",
        ]);

        Cache::forget('legacy_fundis_stats');
    }

    /**
     * Clear all filters
     */
    public function clearFilters()
    {
        $this->reset([
            'search',
            'region',
            'district',
            'status',
            'matchStatus',
        ]);

        $this->loadFilterOptions();
    }

    /**
     * Export filtered data
     */
    public function export()
    {
        // add execution time to 5 minutes
        set_time_limit(2500);
        $filename = 'legacy_fundis_'.date('Y-m-d_H-i-s').'.csv';

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');

            // Headers
            fputcsv($handle, [
                'ID', 'Name', 'Phone', 'NIDA', 'Region', 'District',
                'Ward', 'Status', 'Match Status', 'Matched Fundi ID',
            ]);

            // Use cursor for memory-efficient iteration over large datasets
            // Make sure getLegacyFundis() returns a Query Builder, not a Collection
            $this->getLegacyFundisQuery()
                ->with('matchedFundi:id') // Eager load only the id
                ->cursor()
                ->each(function ($legacy) use ($handle) {
                    fputcsv($handle, [
                        $legacy->id,
                        $legacy->name,
                        $legacy->primary_phone,
                        $legacy->nida_number ?? 'N/A',
                        $legacy->region ?? 'N/A',
                        $legacy->district ?? 'N/A',
                        $legacy->ward ?? 'N/A',
                        ucfirst($legacy->status),
                        ucfirst($legacy->match_status),
                        $legacy->matchedFundi?->id ?? 'N/A',
                    ]);
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Get the query builder (not the result) for legacy fundis
     */
    private function getLegacyFundisQuery()
    {
        // Return the query builder WITHOUT calling ->get()
        return LegacyFundi::query()
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', "%{$this->search}%")
                        ->orWhere('primary_phone', 'like', "%{$this->search}%")
                        ->orWhere('nida_number', 'like', "%{$this->search}%");
                });
            });
        // ->when($this->selectedRegion, fn($q) => $q->where('region', $this->selectedRegion))
        // ->when($this->selectedDistrict, fn($q) => $q->where('district', $this->selectedDistrict))
        // ->when($this->selectedStatus, fn($q) => $q->where('status', $this->selectedStatus))
        // ->when($this->selectedMatchStatus, fn($q) => $q->where('match_status', $this->selectedMatchStatus));
    }
}

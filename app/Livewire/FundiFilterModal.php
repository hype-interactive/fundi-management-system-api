<?php

namespace App\Livewire;

use App\Models\User;
use App\Traits\LocationTrait;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\On;
use Livewire\Component;

class FundiFilterModal extends Component
{
    use LocationTrait;

    public $region = null;

    public $district = null;

    public $ward = null;

    public $gender = null;

    public $ranking = null;

    public $site_visit_min = null;

    public $site_visit_max = null;

    public $bags_min = null;

    public $bags_max = null;

    public $created_by = null;

    public $date_from = null;

    public $date_to = null;

    public $regions = [];

    public $districts = [];

    public $wards = [];

    public $creators = [];

    public $gender_options = ['male' => 'Male', 'female' => 'Female'];

    public function mount(array $filters = [])
    {
        $this->hydrateFromFilters($filters);

        $this->regions = $this->getAccessibleRegions();
        $this->creators = $this->getCachedCreators();
    }

    /**
     * Re-sync pending values from the parent's committed state whenever the
     * modal is (re)opened - covers Apply, chip removal, and Clear Filters.
     */
    #[On('sync-filter-modal')]
    public function syncFromCommitted(array $filters)
    {
        $this->hydrateFromFilters($filters);
    }

    private function hydrateFromFilters(array $filters): void
    {
        $this->region = $filters['region'] ?? null;
        $this->district = $filters['district'] ?? null;
        $this->ward = $filters['ward'] ?? null;
        $this->gender = $filters['gender'] ?? null;
        $this->ranking = $filters['ranking'] ?? null;
        $this->site_visit_min = $filters['site_visit_min'] ?? null;
        $this->site_visit_max = $filters['site_visit_max'] ?? null;
        $this->bags_min = $filters['bags_min'] ?? null;
        $this->bags_max = $filters['bags_max'] ?? null;
        $this->created_by = $filters['created_by'] ?? null;
        $this->date_from = $filters['date_from'] ?? null;
        $this->date_to = $filters['date_to'] ?? null;

        $this->districts = $this->getDistricts((int) $this->region);
        $this->wards = $this->getWards($this->district);
    }

    private function getCachedCreators(): \Illuminate\Support\Collection
    {
        return Cache::remember('fundis_users_created_by', now()->addMinutes(30), function () {
            return User::select('id', 'fname', 'lname', 'email')
                ->whereIn('id', function ($query) {
                    $query->select('created_by')
                        ->from('fundis')
                        ->whereNotNull('created_by')
                        ->distinct();
                })
                ->orderBy('fname')
                ->get();
        });
    }

    /**
     * Cascade is local to this component - it never touches FundisTable.
     */
    public function updatedRegion($value)
    {
        $this->district = null;
        $this->ward = null;
        $this->districts = $this->getDistricts((int) $value);
        $this->wards = collect();
    }

    public function updatedDistrict($value)
    {
        $this->ward = null;
        $this->wards = $this->getWards($value);
    }

    public function apply()
    {
        $this->dispatch('filtersApplied', filters: [
            'region' => $this->region,
            'district' => $this->district,
            'ward' => $this->ward,
            'gender' => $this->gender,
            'ranking' => $this->ranking,
            'site_visit_min' => $this->site_visit_min,
            'site_visit_max' => $this->site_visit_max,
            'bags_min' => $this->bags_min,
            'bags_max' => $this->bags_max,
            'created_by' => $this->created_by,
            'date_from' => $this->date_from,
            'date_to' => $this->date_to,
        ])->to(FundisTable::class);
    }

    public function clear()
    {
        $this->reset([
            'region', 'district', 'ward', 'gender', 'ranking',
            'site_visit_min', 'site_visit_max', 'bags_min', 'bags_max',
            'created_by', 'date_from', 'date_to',
        ]);
        $this->districts = collect();
        $this->wards = collect();

        $this->dispatch('filtersCleared')->to(FundisTable::class);
    }

    public function render()
    {
        return view('livewire.fundi-filter-modal');
    }
}

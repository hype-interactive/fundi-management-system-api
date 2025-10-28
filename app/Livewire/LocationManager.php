<?php

namespace App\Livewire;

use App\Models\District;
use App\Models\Region;
use App\Models\Ward;
use Livewire\Component;
use Livewire\WithPagination;

class LocationManager extends Component
{
    use WithPagination;

    public $view = 'districts'; // districts or wards
    public $search = '';
    
    // District properties
    public $districtId = null;
    public $districtName = '';
    public $districtRegionId = '';
    public $showDistrictModal = false;
    public $isEditingDistrict = false;
    
    // Ward properties
    public $wardId = null;
    public $wardName = '';
    public $wardDistrictId = '';
    public $showWardModal = false;
    public $isEditingWard = false;
    
    // Filter
    public $filterRegionId = '';
    public $filterDistrictId = '';

    protected $queryString = ['search', 'view'];
    protected $paginationTheme = 'bootstrap';
    

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function switchView($view)
    {
        $this->view = $view;
        $this->search = '';
        $this->resetPage();
    }

    // District Methods
    public function createDistrict()
    {
        $this->resetDistrictForm();
        $this->showDistrictModal = true;
        $this->isEditingDistrict = false;
    }

    public function editDistrict($id)
    {
        $district = District::findOrFail($id);
        $this->districtId = $district->id;
        $this->districtName = $district->name;
        $this->districtRegionId = $district->region_id;
        $this->showDistrictModal = true;
        $this->isEditingDistrict = true;
    }

    public function saveDistrict()
    {
        $this->validate([
            'districtName' => 'required|string|max:255',
            'districtRegionId' => 'required|exists:regions,id',
        ]);

        if ($this->isEditingDistrict) {
            $district = District::findOrFail($this->districtId);
            $district->update([
                'name' => $this->districtName,
                'region_id' => $this->districtRegionId,
            ]);
            session()->flash('message', 'District updated successfully.');
        } else {
            District::create([
                'name' => $this->districtName,
                'region_id' => $this->districtRegionId,
            ]);
            session()->flash('message', 'District created successfully.');
        }

        $this->closeDistrictModal();
    }

    public function deleteDistrict($id)
    {
        $district = District::findOrFail($id);
        
        if ($district->wards()->count() > 0) {
            session()->flash('error', 'Cannot delete district with existing wards.');
            return;
        }
        
        $district->delete();
        session()->flash('message', 'District deleted successfully.');
    }

    public function closeDistrictModal()
    {
        $this->showDistrictModal = false;
        $this->resetDistrictForm();
    }

    private function resetDistrictForm()
    {
        $this->districtId = null;
        $this->districtName = '';
        $this->districtRegionId = '';
    }

    // Ward Methods
    public function createWard()
    {
        $this->resetWardForm();
        $this->showWardModal = true;
        $this->isEditingWard = false;
    }

    public function editWard($id)
    {
        $ward = Ward::findOrFail($id);
        $this->wardId = $ward->id;
        $this->wardName = $ward->name;
        $this->wardDistrictId = $ward->district_id;
        $this->showWardModal = true;
        $this->isEditingWard = true;
    }

    public function saveWard()
    {
        $this->validate([
            'wardName' => 'required|string|max:255',
            'wardDistrictId' => 'required|exists:districts,id',
        ]);

        if ($this->isEditingWard) {
            $ward = Ward::findOrFail($this->wardId);
            $ward->update([
                'name' => $this->wardName,
                'district_id' => $this->wardDistrictId,
            ]);
            session()->flash('message', 'Ward updated successfully.');
        } else {
            Ward::create([
                'name' => $this->wardName,
                'district_id' => $this->wardDistrictId,
            ]);
            session()->flash('message', 'Ward created successfully.');
        }

        $this->closeWardModal();
    }

    public function deleteWard($id)
    {
        $ward = Ward::findOrFail($id);
        
        if ($ward->fundis()->count() > 0 || $ward->siteVisits()->count() > 0) {
            session()->flash('error', 'Cannot delete ward with existing data.');
            return;
        }
        
        $ward->delete();
        session()->flash('message', 'Ward deleted successfully.');
    }

    public function closeWardModal()
    {
        $this->showWardModal = false;
        $this->resetWardForm();
    }

    private function resetWardForm()
    {
        $this->wardId = null;
        $this->wardName = '';
        $this->wardDistrictId = '';
    }

    public function render()
    {
        $regions = Region::orderBy('name')->get();
        
        if ($this->view === 'districts') {
            $query = District::with('region')
                ->when($this->search, function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                      ->orWhereHas('region', function ($q) {
                          $q->where('name', 'like', '%' . $this->search . '%');
                      });
                })
                ->when($this->filterRegionId, function ($q) {
                    $q->where('region_id', $this->filterRegionId);
                })
                ->orderBy('name');
            
            $items = $query->paginate(15);
        } else {
            $districts = District::orderBy('name')->get();
            
            $query = Ward::with('district.region')
                ->when($this->search, function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                      ->orWhereHas('district', function ($q) {
                          $q->where('name', 'like', '%' . $this->search . '%');
                      });
                })
                ->when($this->filterDistrictId, function ($q) {
                    $q->where('district_id', $this->filterDistrictId);
                })
                ->orderBy('name');
            
            $items = $query->paginate(15);
        }

        return view('livewire.location-manager', [
            'items' => $items,
            'regions' => $regions,
            'districts' => $this->view === 'wards' ? District::orderBy('name')->get() : collect(),
        ]);
    }
}
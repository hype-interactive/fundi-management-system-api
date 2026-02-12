<?php

namespace App\Livewire\Dashboard;

use App\Models\User;
use App\Models\Fundi;
use Livewire\Component;

class CompanySummary extends Component
{
    public $admin_count;
    public $operator_count;
    public $fundi_count;

    public function render()
    {
        $this->admin_count = User::where('role_id', '!=', 3)->orWhereNull('role_id')->count(); // Assuming 3 is the role_id for 'operator'

        if (isRoot()) {
            $this->operator_count = User::where('role_id', 3)->count(); // Assuming 3 is the role_id for 'operator'
            $this->fundi_count = Fundi::count();
        } else {
            $districtIds = currentDistrictIds();

            $this->operator_count = User::whereHas('districts', function ($q) use ($districtIds) {
                $q->whereIn('districts.id', $districtIds);
            })->count();

            $this->fundi_count = Fundi::get()->count();
        }
        return view('livewire.dashboard.company-summary');
    }
}

<?php

namespace App\Livewire;

use App\Models\District;
use App\Traits\PlatformSummaryTrait;
use Livewire\Component;

class PlatformSummary extends Component
{
    use PlatformSummaryTrait;

    // Summary Metrics
    public $total_fundis;

    public $fundis_month_diff;

    public $fundis_month_status;

    public $total_visits;

    public $visits_month_diff;

    public $visits_month_status;

    public $total_disbursements;

    public $disbursements_month_diff;

    public $disbursements_month_status;

    // Performance Metrics
    public $fundi_approval_rate;

    public $performance_status;

    // Filters
    public $districts;

    public $selected_district = null;

    protected $listeners = ['districtChanged' => 'updateMetrics'];

    public function mount()
    {
        $this->districts = District::orderBy('name')->get();
        $this->loadMetrics();
    }

    public function updatedSelectedDistrict()
    {
        $this->loadMetrics();
    }

    public function loadMetrics()
    {
        // Total Fundis with Month Comparison
        $this->total_fundis = $this->getFundiTotal('MONTH', $this->selected_district);
        $this->fundis_month_diff = abs($this->getFundiDiff('MONTH'));
        $this->fundis_month_status = $this->getFundiDiff('MONTH') >= 0;

        // Total Site Visits with Month Comparison
        $this->total_visits = $this->getSiteVisitTotal('MONTH', $this->selected_district);
        $this->visits_month_diff = abs($this->getSiteVisitDiff('MONTH'));
        $this->visits_month_status = $this->getSiteVisitDiff('MONTH') >= 0;

        // Total Disbursements with Month Comparison
        $this->total_disbursements = $this->getDisbursementTotal('MONTH', $this->selected_district);
        $this->disbursements_month_diff = abs($this->getDisbursementDiff('MONTH'));
        $this->disbursements_month_status = $this->getDisbursementDiff('MONTH') >= 0;   

        // Performance Metrics
        $this->fundi_approval_rate = $this->getFundiApprovalRate('MONTH');
        $this->performance_status = $this->fundi_approval_rate >= 80;
    }

    public function render()
    {
        return view('livewire.platform-summary');
    }
}

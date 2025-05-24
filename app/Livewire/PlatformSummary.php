<?php

namespace App\Livewire;

use Livewire\Component;
use App\Traits\PlatformSummaryTrait;
use App\Models\District;

class PlatformSummary extends Component
{
    use PlatformSummaryTrait;

    // Fundi Registration Metrics
    public $fundis_today;
    public $fundis_today_diff;
    public $fundis_today_status;

    public $fundis_week;
    public $fundis_week_diff;
    public $fundis_week_status;

    public $fundis_month;
    public $fundis_month_diff;
    public $fundis_month_status;

    public $fundis_year;
    public $fundis_year_diff;
    public $fundis_year_status;

    // Site Visit Metrics
    public $visits_today;
    public $visits_today_diff;
    public $visits_today_status;

    public $visits_week;
    public $visits_week_diff;
    public $visits_week_status;

    public $visits_month;
    public $visits_month_diff;
    public $visits_month_status;

    public $visits_year;
    public $visits_year_diff;
    public $visits_year_status;

    // Financial Metrics
    public $disbursements_today;
    public $disbursements_today_diff;
    public $disbursements_today_status;

    public $disbursements_week;
    public $disbursements_week_diff;
    public $disbursements_week_status;

    public $disbursements_month;
    public $disbursements_month_diff;
    public $disbursements_month_status;

    public $disbursements_year;
    public $disbursements_year_diff;
    public $disbursements_year_status;

    // Overall Platform Stats
    public $total_fundis;
    public $active_fundis;
    public $pending_fundis;
    public $pending_site_visits;
    public $total_operators;
    public $fundi_approval_rate;
    public $visit_approval_rate;

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
        // Fundi Registration Metrics
        $this->fundis_today = $this->getFundiTotal("TODAY", $this->selected_district);
        $this->fundis_today_diff = abs($this->getFundiDiff("TODAY"));
        $this->fundis_today_status = $this->getFundiDiff("TODAY") >= 0;

        $this->fundis_week = $this->getFundiTotal("WEEK", $this->selected_district);
        $this->fundis_week_diff = abs($this->getFundiDiff("WEEK"));
        $this->fundis_week_status = $this->getFundiDiff("WEEK") >= 0;

        $this->fundis_month = $this->getFundiTotal("MONTH", $this->selected_district);
        $this->fundis_month_diff = abs($this->getFundiDiff("MONTH"));
        $this->fundis_month_status = $this->getFundiDiff("MONTH") >= 0;

        $this->fundis_year = $this->getFundiTotal("YEAR", $this->selected_district);
        $this->fundis_year_diff = abs($this->getFundiDiff("YEAR"));
        $this->fundis_year_status = $this->getFundiDiff("YEAR") >= 0;

        // Site Visit Metrics
        $this->visits_today = $this->getSiteVisitTotal("TODAY", $this->selected_district);
        $this->visits_today_diff = abs($this->getSiteVisitDiff("TODAY"));
        $this->visits_today_status = $this->getSiteVisitDiff("TODAY") >= 0;

        $this->visits_week = $this->getSiteVisitTotal("WEEK", $this->selected_district);
        $this->visits_week_diff = abs($this->getSiteVisitDiff("WEEK"));
        $this->visits_week_status = $this->getSiteVisitDiff("WEEK") >= 0;

        $this->visits_month = $this->getSiteVisitTotal("MONTH", $this->selected_district);
        $this->visits_month_diff = abs($this->getSiteVisitDiff("MONTH"));
        $this->visits_month_status = $this->getSiteVisitDiff("MONTH") >= 0;

        $this->visits_year = $this->getSiteVisitTotal("YEAR", $this->selected_district);
        $this->visits_year_diff = abs($this->getSiteVisitDiff("YEAR"));
        $this->visits_year_status = $this->getSiteVisitDiff("YEAR") >= 0;

        // Financial Metrics
        $this->disbursements_today = $this->getDisbursementTotal("TODAY", $this->selected_district);
        $this->disbursements_today_diff = abs($this->getDisbursementDiff("TODAY"));
        $this->disbursements_today_status = $this->getDisbursementDiff("TODAY") >= 0;

        $this->disbursements_week = $this->getDisbursementTotal("WEEK", $this->selected_district);
        $this->disbursements_week_diff = abs($this->getDisbursementDiff("WEEK"));
        $this->disbursements_week_status = $this->getDisbursementDiff("WEEK") >= 0;

        $this->disbursements_month = $this->getDisbursementTotal("MONTH", $this->selected_district);
        $this->disbursements_month_diff = abs($this->getDisbursementDiff("MONTH"));
        $this->disbursements_month_status = $this->getDisbursementDiff("MONTH") >= 0;

        $this->disbursements_year = $this->getDisbursementTotal("YEAR", $this->selected_district);
        $this->disbursements_year_diff = abs($this->getDisbursementDiff("YEAR"));
        $this->disbursements_year_status = $this->getDisbursementDiff("YEAR") >= 0;

        // Overall Platform Stats
        $this->total_fundis = $this->getFundiTotal(null, $this->selected_district);
        $this->active_fundis = $this->getFundiTotal(null, $this->selected_district, 'active');
        $this->pending_fundis = $this->getPendingFundisCount($this->selected_district);
        $this->pending_site_visits = $this->getPendingSiteVisitsCount($this->selected_district);
        $this->total_operators = $this->getActiveOperatorsCount($this->selected_district);
        $this->fundi_approval_rate = $this->getFundiApprovalRate("MONTH");
        $this->visit_approval_rate = $this->getSiteVisitApprovalRate("MONTH");
    }

    public function render()
    {
        return view('livewire.platform-summary');
    }
}

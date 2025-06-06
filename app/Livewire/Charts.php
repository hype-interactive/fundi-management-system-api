<?php

namespace App\Livewire;

use Carbon\Carbon;
use App\Models\Fundi;
use Livewire\Component;
use App\Models\SiteVisit;

class Charts extends Component
{
    public $fundiData = [];
    public $siteVisitData = [];

    public function mount()
    {
        $this->loadFundiData();
        $this->loadSiteVisitData();
    }

    private function loadFundiData()
    {
        // Get last 12 months of fundi registrations
        $fundiRegistrations = Fundi::selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, COUNT(*) as count')
            ->where('created_at', '>=', Carbon::now()->subMonths(12))
            ->groupBy('year', 'month')
            ->orderBy('year', 'asc')
            ->orderBy('month', 'asc')
            ->get();

        // Create array with all 12 months (fill missing months with 0)
        $months = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $months[] = [
                'label' => $date->format('M Y'),
                'year' => $date->year,
                'month' => $date->month,
                'count' => 0
            ];
        }

        // Fill in actual data
        foreach ($fundiRegistrations as $registration) {
            $key = array_search(true, array_map(function($m) use ($registration) {
                return $m['year'] == $registration->year && $m['month'] == $registration->month;
            }, $months));

            if ($key !== false) {
                $months[$key]['count'] = $registration->count;
            }
        }

        $this->fundiData = [
            'labels' => array_column($months, 'label'),
            'data' => array_column($months, 'count')
        ];
    }

    private function loadSiteVisitData()
    {
        // Get last 12 months of site visits
        $siteVisits = SiteVisit::selectRaw('YEAR(visit_date) as year, MONTH(visit_date) as month, COUNT(*) as count')
            ->where('visit_date', '>=', Carbon::now()->subMonths(12))
            ->groupBy('year', 'month')
            ->orderBy('year', 'asc')
            ->orderBy('month', 'asc')
            ->get();

        // Create array with all 12 months (fill missing months with 0)
        $months = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $months[] = [
                'label' => $date->format('M Y'),
                'year' => $date->year,
                'month' => $date->month,
                'count' => 0
            ];
        }

        // Fill in actual data
        foreach ($siteVisits as $visit) {
            $key = array_search(true, array_map(function($m) use ($visit) {
                return $m['year'] == $visit->year && $m['month'] == $visit->month;
            }, $months));

            if ($key !== false) {
                $months[$key]['count'] = $visit->count;
            }
        }

        $this->siteVisitData = [
            'labels' => array_column($months, 'label'),
            'data' => array_column($months, 'count')
        ];
    }

    public function render()
    {
        return view('livewire.charts');
    }
}

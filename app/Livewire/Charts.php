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

    // Filter properties
    public $selectedTimeRange = '12_months';
    public $selectedChartType = 'both';
    public $selectedMetric = 'count';
    public $dateFrom;
    public $dateTo;

    public function mount()
    {
        $this->dateFrom = Carbon::now()->subMonths(12)->format('Y-m-d');
        $this->dateTo = Carbon::now()->format('Y-m-d');
        $this->loadChartData();
    }

    public function updatedSelectedTimeRange()
    {
        $this->setDateRange();
        $this->loadChartData();
    }

    public function updatedDateFrom()
    {
        $this->loadChartData();
    }

    public function updatedDateTo()
    {
        $this->loadChartData();
    }

    public function updatedSelectedMetric()
    {
        $this->loadChartData();
    }

    private function setDateRange()
    {
        switch ($this->selectedTimeRange) {
            case '3_months':
                $this->dateFrom = Carbon::now()->subMonths(3)->format('Y-m-d');
                $this->dateTo = Carbon::now()->format('Y-m-d');
                break;
            case '6_months':
                $this->dateFrom = Carbon::now()->subMonths(6)->format('Y-m-d');
                $this->dateTo = Carbon::now()->format('Y-m-d');
                break;
            case '12_months':
                $this->dateFrom = Carbon::now()->subMonths(12)->format('Y-m-d');
                $this->dateTo = Carbon::now()->format('Y-m-d');
                break;
            case 'current_year':
                $this->dateFrom = Carbon::now()->startOfYear()->format('Y-m-d');
                $this->dateTo = Carbon::now()->format('Y-m-d');
                break;
            case 'last_year':
                $this->dateFrom = Carbon::now()->subYear()->startOfYear()->format('Y-m-d');
                $this->dateTo = Carbon::now()->subYear()->endOfYear()->format('Y-m-d');
                break;
        }
    }

    private function loadChartData()
    {
        $this->loadFundiData();
        $this->loadSiteVisitData();
        $this->dispatch('updateCharts', $this->fundiData, $this->siteVisitData);
    }

    private function loadFundiData()
    {
        $dateFrom = Carbon::parse($this->dateFrom);
        $dateTo = Carbon::parse($this->dateTo);

        $query = Fundi::selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, COUNT(*) as count')
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->groupBy('year', 'month')
            ->orderBy('year', 'asc')
            ->orderBy('month', 'asc');

        if ($this->selectedMetric === 'cumulative') {
            $fundiRegistrations = $query->get();
            $cumulative = 0;
            foreach ($fundiRegistrations as $registration) {
                $cumulative += $registration->count;
                $registration->count = $cumulative;
            }
        } else {
            $fundiRegistrations = $query->get();
        }

        // Generate month range
        $months = $this->generateMonthRange($dateFrom, $dateTo);

        // Fill in actual data
        foreach ($fundiRegistrations as $registration) {
            $key = array_search(true, array_map(function ($m) use ($registration) {
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
        $dateFrom = Carbon::parse($this->dateFrom);
        $dateTo = Carbon::parse($this->dateTo);

        $query = SiteVisit::selectRaw('YEAR(visit_date) as year, MONTH(visit_date) as month, COUNT(*) as count')
            ->whereBetween('visit_date', [$dateFrom, $dateTo])
            ->groupBy('year', 'month')
            ->orderBy('year', 'asc')
            ->orderBy('month', 'asc');

        if ($this->selectedMetric === 'cumulative') {
            $siteVisits = $query->get();
            $cumulative = 0;
            foreach ($siteVisits as $visit) {
                $cumulative += $visit->count;
                $visit->count = $cumulative;
            }
        } else {
            $siteVisits = $query->get();
        }

        // Generate month range
        $months = $this->generateMonthRange($dateFrom, $dateTo);

        // Fill in actual data
        foreach ($siteVisits as $visit) {
            $key = array_search(true, array_map(function ($m) use ($visit) {
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

    private function generateMonthRange($dateFrom, $dateTo)
    {
        $months = [];
        $current = $dateFrom->copy();

        while ($current <= $dateTo) {
            $months[] = [
                'label' => $current->format('M Y'),
                'year' => $current->year,
                'month' => $current->month,
                'count' => 0
            ];
            $current->addMonth();
        }

        return $months;
    }

    public function refreshData()
    {
        $this->loadChartData();
        $this->dispatch('showNotification', 'Charts refreshed successfully');
    }

    public function resetFilters()
    {
        $this->selectedTimeRange = '12_months';
        $this->selectedChartType = 'both';
        $this->selectedMetric = 'count';
        $this->dateFrom = Carbon::now()->subMonths(12)->format('Y-m-d');
        $this->dateTo = Carbon::now()->format('Y-m-d');
        $this->loadChartData();
        $this->dispatch('showNotification', 'Filters reset to default');
    }

    public function render()
    {
        return view('livewire.charts');
    }
}

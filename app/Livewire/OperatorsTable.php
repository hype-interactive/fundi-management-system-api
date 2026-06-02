<?php

namespace App\Livewire;

use App\Models\Role;
use App\Models\User;
use Livewire\Component;
use Livewire\Attributes\Url;
use Livewire\WithPagination;
use App\Traits\LocationTrait;
use Carbon\Carbon;

class OperatorsTable extends Component
{
    use WithPagination, LocationTrait;

    protected $paginationTheme = 'bootstrap';

    #[Url]
    public $search_key = '';

    public $perPage = 12;

    #[Url]
    public $region = null;

    #[Url]
    public $district = null;

    #[Url]
    public $role_id = null;

    #[Url]
    public $status = null;

    // Date range filters
    #[Url]
    public $date_from = null;

    #[Url]
    public $date_to = null;

    public $regions = [];
    public $districts = [];
    public $roles = [];
    public $status_options = [];

    // New properties for mobile responsiveness
    public $showMobileFilters = false;
    public $compactView = false;

    public function updatedRegion($value)
    {
        $this->district = null;
        $this->districts = $this->getDistricts((int)$value);
        $this->resetPage();
    }

    public function updatedDistrict($value)
    {
        $this->resetPage();
    }

    public function updatedSearchKey()
    {
        $this->resetPage();
    }

    public function updatedRoleId()
    {
        $this->resetPage();
    }

    public function updatedStatus()
    {
        $this->resetPage();
    }

    public function updatedDateFrom()
    {
        $this->resetPage();
    }

    public function updatedDateTo()
    {
        $this->resetPage();
    }

    public function fetch()
    {
        return $this->getOperatorQuery()->paginate($this->perPage);
    }

    /**
     * Get the date range for filtering
     */
    public function getDateRange(): array
    {
        $from = $this->date_from ? Carbon::parse($this->date_from)->startOfDay() : null;
        $to = $this->date_to ? Carbon::parse($this->date_to)->endOfDay() : null;

        return compact('from', 'to');
    }

    /**
     * Check if date filters are active
     */
    public function hasDateFilter(): bool
    {
        return $this->date_from || $this->date_to;
    }

    /**
     * Get stats for an operator within the date range
     */
    public function getOperatorStats($operator): array
    {
        $dateRange = $this->getDateRange();

        if ($dateRange['from'] && $dateRange['to']) {
            // Filtered stats
            $siteVisits = $operator->siteVisits()
                ->whereBetween('created_at', [$dateRange['from'], $dateRange['to']])
                ->count();
            $fundis = $operator->createdFundis()
                ->whereBetween('created_at', [$dateRange['from'], $dateRange['to']])
                ->count();
        } else {
            // Default: Today, Week, Month, Total
            $siteVisits = null;
            $fundis = null;
        }

        return [
            'filtered_site_visits' => $siteVisits,
            'filtered_fundis' => $fundis,
            'today_visits' => $operator->siteVisits()->whereDate('created_at', today())->count(),
            'today_fundis' => $operator->createdFundis()->whereDate('created_at', today())->count(),
            'week_visits' => $operator->siteVisits()->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'week_fundis' => $operator->createdFundis()->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'month_visits' => $operator->siteVisits()->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
            'month_fundis' => $operator->createdFundis()->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
            'total_visits' => $operator->siteVisits()->count(),
            'total_fundis' => $operator->createdFundis()->count(),
        ];
    }

    public function getOperatorQuery()
    {
        $this->loadData();

        $query = User::with(['role', 'districts.region'])
            ->where("role_id", '!=', 1)
            ->where("role_id", '!=', 2);

        if ($this->region) {
            $query->whereHas('districts.region', function ($q) {
                $q->where('regions.id', $this->region);
            });
        }

        if ($this->district) {
            $query->whereHas('districts', function ($q) {
                $q->where('districts.id', $this->district);
            });
        }

        return $query
            ->when($this->search_key, function ($query, $search_key) {
                $query->where(function ($q) use ($search_key) {
                    $q->whereRaw("concat(fname, ' ', mname, ' ', lname) like ?", ['%' . $search_key . '%'])
                        ->orWhere('email', 'like', '%' . $search_key . '%')
                        ->orWhere('phone', 'like', '%' . $search_key . '%');
                });
            })
            ->when($this->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->latest('created_at');
    }

    public function loadData()
    {
        $this->regions = $this->getAccessibleRegions();
        $this->districts = $this->getDistricts((int)$this->region);
        $this->roles = Role::orderBy('name')->get();
        $this->status_options = [
            'active' => 'Active',
            'inactive' => 'Inactive'
        ];
    }

    public function toggleCompactView()
    {
        $this->compactView = !$this->compactView;
    }

    public function downloadOperatorPDF()
    {
        set_time_limit(300);
        ini_set("memory_limit", "1000M");

        return response()->streamDownload(function () {
            $operators = $this->getOperatorQuery()->get();

            $rows = [];
            foreach ($operators as $operator) {
                $stats = $this->getOperatorStats($operator);

                $rows[] = [
                    'name' => $operator->full_name,
                    'phone' => $operator->phone,
                    'site' => [
                        'today' => $stats['today_visits'],
                        'month' => $stats['month_visits'],
                        'all_time' => $stats['total_visits'],
                    ],
                    'fundis' => [
                        'today' => $stats['today_fundis'],
                        'month' => $stats['month_fundis'],
                        'all_time' => $stats['total_fundis'],
                    ],
                ];
            }

            $title = 'Operators | Magic Builders';
            $date = date('D, M/d/Y');
            $html_view = view('documents.operators', compact('title', 'date', 'rows'))->render();
            $pdf = app('dompdf.wrapper');
            $pdf->loadHTML($html_view);
            $pdf->setPaper('A4', 'landscape');

            echo $pdf->stream();
        }, 'magic_builders_operators.pdf');
    }

    public function exportToExcel()
    {
        return response()->streamDownload(function () {
            $operators = $this->getOperatorQuery()->get();

            $headers = [
                'Name',
                'Email',
                'Phone',
                'Role',
                'Status',
                'Districts',
                'Site Visits (Today)',
                'Site Visits (Month)',
                'Site Visits (All Time)',
                'Fundis Registered (Today)',
                'Fundis Registered (Month)',
                'Fundis Registered (All Time)',
                'Total Incentives',
                'Created At',
            ];

            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);

            foreach ($operators as $operator) {
                $districts = $operator->districts->pluck('name')->join(', ');
                $totalIncentives = $operator->incentives()->sum('amount');
                $stats = $this->getOperatorStats($operator);

                fputcsv($handle, [
                    $operator->full_name,
                    $operator->email,
                    $operator->phone,
                    $operator->role->display_name ?? 'N/A',
                    ucfirst($operator->status),
                    $districts ?: 'N/A',
                    $stats['today_visits'],
                    $stats['month_visits'],
                    $stats['total_visits'],
                    $stats['today_fundis'],
                    $stats['month_fundis'],
                    $stats['total_fundis'],
                    number_format($totalIncentives, 2),
                    $operator->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($handle);
        }, 'operators_export_' . date('Y-m-d_H-i-s') . '.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function mount()
    {
        if (!$this->isAdminOrRoot()) {
            $primaryDistrict = $this->getUserPrimaryDistrict();
            $primaryRegion = $this->getUserPrimaryRegion();

            if ($primaryDistrict) {
                $this->district = $primaryDistrict->id;
            }

            if ($primaryRegion) {
                $this->region = $primaryRegion->id;
            }
        }

        $this->showMobileFilters = false;
        $this->compactView = false;
    }

    public function refresh()
    {
        $this->reset([
            'search_key',
            'role_id',
            'status',
            'date_from',
            'date_to'
        ]);

        if ($this->isAdminOrRoot()) {
            $this->reset(['region', 'district']);
        }

        $this->showMobileFilters = false;
    }

    public function render()
    {
        return view('livewire.operators-table', [
            'operators' => $this->fetch(),
        ]);
    }
}
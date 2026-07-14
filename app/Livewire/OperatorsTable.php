<?php

namespace App\Livewire;

use App\Models\Role;
use App\Models\User;
use App\Traits\LocationTrait;
use Carbon\Carbon;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class OperatorsTable extends Component
{
    use LocationTrait, WithPagination;

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
        $this->districts = $this->getDistricts((int) $value);
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
     * Get the date range for filtering, boundaries in UTC for DB comparison.
     * User-selected dates are interpreted as EAT (Africa/Dar_es_Salaam) calendar days.
     */
    public function getDateRange(): array
    {
        $from = $this->date_from
            ? Carbon::parse($this->date_from, 'Africa/Dar_es_Salaam')->startOfDay()->utc()
            : null;
        $to = $this->date_to
            ? Carbon::parse($this->date_to, 'Africa/Dar_es_Salaam')->endOfDay()->utc()
            : null;

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
     * Get stats for an operator. All period boundaries use EAT (UTC+3) and are
     * converted to UTC for comparison against stored timestamps.
     */
    public function getOperatorStats($operator): array
    {
        $eatNow = Carbon::now('Africa/Dar_es_Salaam');

        $todayStart  = $eatNow->copy()->startOfDay()->utc();
        $todayEnd    = $eatNow->copy()->endOfDay()->utc();
        $weekStart   = $eatNow->copy()->startOfWeek()->utc();
        $weekEnd     = $eatNow->copy()->endOfWeek()->utc();
        $monthStart  = $eatNow->copy()->startOfMonth()->utc();
        $monthEnd    = $eatNow->copy()->endOfMonth()->utc();

        $dateRange = $this->getDateRange();
        $hasFilter = $dateRange['from'] && $dateRange['to'];

        return [
            'filtered_site_visits' => $hasFilter
                ? $operator->siteVisits()->whereBetween('created_at', [$dateRange['from'], $dateRange['to']])->count()
                : null,
            'filtered_fundis' => $hasFilter
                ? $operator->createdFundis()->whereBetween('created_at', [$dateRange['from'], $dateRange['to']])->count()
                : null,
            'today_visits'  => $operator->siteVisits()->whereBetween('created_at', [$todayStart, $todayEnd])->count(),
            'today_fundis'  => $operator->createdFundis()->whereBetween('created_at', [$todayStart, $todayEnd])->count(),
            'week_visits'   => $operator->siteVisits()->whereBetween('created_at', [$weekStart, $weekEnd])->count(),
            'week_fundis'   => $operator->createdFundis()->whereBetween('created_at', [$weekStart, $weekEnd])->count(),
            'month_visits'  => $operator->siteVisits()->whereBetween('created_at', [$monthStart, $monthEnd])->count(),
            'month_fundis'  => $operator->createdFundis()->whereBetween('created_at', [$monthStart, $monthEnd])->count(),
            'total_visits'  => $operator->siteVisits()->count(),
            'total_fundis'  => $operator->createdFundis()->count(),
        ];
    }

    public function getOperatorQuery()
    {
        $this->loadData();

        $query = User::with(['role', 'districts.region'])
            ->where('role_id', '!=', 1)
            ->where('role_id', '!=', 2);

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
                    $q->whereRaw("concat(fname, ' ', mname, ' ', lname) like ?", ['%'.$search_key.'%'])
                        ->orWhere('email', 'like', '%'.$search_key.'%')
                        ->orWhere('phone', 'like', '%'.$search_key.'%');
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
        $this->districts = $this->getDistricts((int) $this->region);
        $this->roles = Role::orderBy('name')->get();
        $this->status_options = [
            'active' => 'Active',
            'inactive' => 'Inactive',
        ];
    }

    public function toggleCompactView()
    {
        $this->compactView = ! $this->compactView;
    }

    public function downloadOperatorPDF()
    {
        set_time_limit(300);
        ini_set('memory_limit', '1000M');

        return response()->streamDownload(function () {
            $operators = $this->getOperatorQuery()->get();
            $hasFilter = $this->hasDateFilter() && $this->date_from && $this->date_to;

            $rows = [];
            foreach ($operators as $operator) {
                $stats = $this->getOperatorStats($operator);

                $rows[] = [
                    'name' => $operator->full_name,
                    'phone' => $operator->phone,
                    'site' => [
                        'today'    => $stats['today_visits'],
                        'week'     => $stats['week_visits'],
                        'month'    => $stats['month_visits'],
                        'all_time' => $stats['total_visits'],
                        'range'    => $stats['filtered_site_visits'],
                    ],
                    'fundis' => [
                        'today'    => $stats['today_fundis'],
                        'week'     => $stats['week_fundis'],
                        'month'    => $stats['month_fundis'],
                        'all_time' => $stats['total_fundis'],
                        'range'    => $stats['filtered_fundis'],
                    ],
                ];
            }

            $filterParts = [];
            if ($this->region) {
                $region = \App\Models\Region::find($this->region);
                $filterParts[] = 'Region: ' . ($region?->name ?? $this->region);
            }
            if ($this->district) {
                $district = \App\Models\District::find($this->district);
                $filterParts[] = 'District: ' . ($district?->name ?? $this->district);
            }
            if ($this->date_from || $this->date_to) {
                $filterParts[] = 'Date: ' . ($this->date_from ?? 'Beginning') . ' — ' . ($this->date_to ?? 'Today');
            }
            $filters = implode('  |  ', $filterParts);

            $title = 'Operators | Magic Builders';
            $date = date('D, M/d/Y');
            $html_view = view('documents.operators', compact('title', 'date', 'rows', 'filters', 'hasFilter'))->render();
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
            $hasFilter = $this->hasDateFilter() && $this->date_from && $this->date_to;

            $headers = [
                'Name',
                'Email',
                'Phone',
                'Role',
                'Status',
                'Districts',
                'Site Visits (Today)',
                'Site Visits (Week)',
                'Site Visits (Month)',
                'Site Visits (All Time)',
                'Fundis Registered (Today)',
                'Fundis Registered (Week)',
                'Fundis Registered (Month)',
                'Fundis Registered (All Time)',
                'Total Incentives',
                'Created At',
            ];

            if ($hasFilter) {
                $headers[] = 'Site Visits (' . $this->date_from . ' to ' . $this->date_to . ')';
                $headers[] = 'Fundis Registered (' . $this->date_from . ' to ' . $this->date_to . ')';
            }

            $handle = fopen('php://output', 'w');

            $filterParts = [];
            if ($this->region) {
                $region = \App\Models\Region::find($this->region);
                $filterParts[] = 'Region: ' . ($region?->name ?? $this->region);
            }
            if ($this->district) {
                $district = \App\Models\District::find($this->district);
                $filterParts[] = 'District: ' . ($district?->name ?? $this->district);
            }
            if ($this->date_from || $this->date_to) {
                $filterParts[] = 'Date: ' . ($this->date_from ?? 'Beginning') . ' to ' . ($this->date_to ?? 'Today');
            }
            if (! empty($filterParts)) {
                fputcsv($handle, ['Filters: ' . implode(' | ', $filterParts)]);
            }

            fputcsv($handle, $headers);

            foreach ($operators as $operator) {
                $districts = $operator->districts->pluck('name')->join(', ');
                $totalIncentives = $operator->incentives()->sum('amount');
                $stats = $this->getOperatorStats($operator);

                $row = [
                    $operator->full_name,
                    $operator->email,
                    $operator->phone,
                    $operator->role->display_name ?? 'N/A',
                    ucfirst($operator->status),
                    $districts ?: 'N/A',
                    $stats['today_visits'],
                    $stats['week_visits'],
                    $stats['month_visits'],
                    $stats['total_visits'],
                    $stats['today_fundis'],
                    $stats['week_fundis'],
                    $stats['month_fundis'],
                    $stats['total_fundis'],
                    number_format($totalIncentives, 2),
                    $operator->created_at->format('Y-m-d H:i:s'),
                ];

                if ($hasFilter) {
                    $row[] = $stats['filtered_site_visits'] ?? 0;
                    $row[] = $stats['filtered_fundis'] ?? 0;
                }

                fputcsv($handle, $row);
            }

            fclose($handle);
        }, 'operators_export_'.date('Y-m-d_H-i-s').'.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Active filter summary for operator+mafundi reports.
     */
    protected function getActiveFiltersLabel(): string
    {
        $filterParts = [];

        if ($this->region) {
            $region = \App\Models\Region::find($this->region);
            $filterParts[] = 'Region: '.($region?->name ?? $this->region);
        }
        if ($this->district) {
            $district = \App\Models\District::find($this->district);
            $filterParts[] = 'District: '.($district?->name ?? $this->district);
        }
        if ($this->date_from || $this->date_to) {
            $filterParts[] = 'Date: '.($this->date_from ?? 'Beginning').' — '.($this->date_to ?? 'Today');
        }
        if ($this->search_key) {
            $filterParts[] = 'Search: '.$this->search_key;
        }
        if ($this->status) {
            $filterParts[] = 'Status: '.ucfirst($this->status);
        }

        return implode('  |  ', $filterParts);
    }

    /**
     * Build operator + mafundi rows for the filtered operators list.
     */
    protected function buildOperatorsWithMafundiData(): array
    {
        $dateRange = $this->getDateRange();
        $hasDateFilter = $dateRange['from'] && $dateRange['to'];

        $operators = $this->getOperatorQuery()
            ->with([
                'role',
                'districts',
                'createdFundis' => function ($query) use ($hasDateFilter, $dateRange) {
                    $query->with(['ward.district.region'])
                        ->withMafundiReportStats()
                        ->latest('created_at');
                    if ($hasDateFilter) {
                        $query->whereBetween('created_at', [$dateRange['from'], $dateRange['to']]);
                    }
                },
            ])
            ->get();

        return $operators->map(function ($operator) {
            return [
                'name' => $operator->full_name,
                'phone' => $operator->phone,
                'email' => $operator->email,
                'status' => ucfirst($operator->status),
                'role' => $operator->role->display_name ?? $operator->role->name ?? 'N/A',
                'districts' => $operator->districts->pluck('name')->join(', '),
                'fundis' => $operator->createdFundis->map->toMafundiReportRow()->all(),
            ];
        })->all();
    }

    /**
     * Download PDF of filtered operators with their mafundi lists.
     */
    public function downloadOperatorsWithMafundiPDF()
    {
        set_time_limit(300);
        ini_set('memory_limit', '1000M');

        return response()->streamDownload(function () {
            $operators = $this->buildOperatorsWithMafundiData();
            $filters = $this->getActiveFiltersLabel();
            $title = 'Operators with Mafundi | Magic Builders';
            $date = date('D, M/d/Y');

            $html_view = view('documents.operator-mafundi', compact('title', 'date', 'operators', 'filters'))->render();
            $pdf = app('dompdf.wrapper');
            $pdf->loadHTML($html_view);
            $pdf->setPaper('A4', 'landscape');

            echo $pdf->stream();
        }, 'operators_with_mafundi_'.date('Y-m-d_H-i-s').'.pdf');
    }

    /**
     * Export filtered operators with their mafundi lists to CSV.
     */
    public function exportOperatorsWithMafundi()
    {
        return response()->streamDownload(function () {
            $operators = $this->buildOperatorsWithMafundiData();
            $handle = fopen('php://output', 'w');

            $filters = $this->getActiveFiltersLabel();
            if ($filters !== '') {
                fputcsv($handle, ['Filters: '.$filters]);
            }

            fputcsv($handle, [
                'Operator Name',
                'Operator Phone',
                'Operator Email',
                'Operator Status',
                'Operator Role',
                'Operator Districts',
                'Fundi Name',
                'Fundi Gender',
                'Fundi Primary Phone',
                'Fundi Transaction Phone',
                'Fundi Address',
                'Fundi Site Visits',
                'Fundi Bags Total',
            ]);

            foreach ($operators as $operator) {
                if (empty($operator['fundis'])) {
                    fputcsv($handle, [
                        $operator['name'],
                        $operator['phone'] ?: 'N/A',
                        $operator['email'] ?: 'N/A',
                        $operator['status'],
                        $operator['role'],
                        $operator['districts'] ?: 'N/A',
                        'No mafundi',
                        '',
                        '',
                        '',
                        '',
                        '',
                        '',
                    ]);

                    continue;
                }

                foreach ($operator['fundis'] as $fundi) {
                    fputcsv($handle, [
                        $operator['name'],
                        $operator['phone'] ?: 'N/A',
                        $operator['email'] ?: 'N/A',
                        $operator['status'],
                        $operator['role'],
                        $operator['districts'] ?: 'N/A',
                        $fundi['name'],
                        $fundi['gender'],
                        $fundi['primary_phone'],
                        $fundi['transaction_phone'],
                        $fundi['address'],
                        $fundi['site_visits'],
                        $fundi['bags_total'],
                    ]);
                }
            }

            fclose($handle);
        }, 'operators_with_mafundi_'.date('Y-m-d_H-i-s').'.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function mount()
    {
        if (! $this->isAdminOrRoot()) {
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
            'date_to',
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

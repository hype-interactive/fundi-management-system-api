<?php

namespace App\Livewire;

use App\Models\Role;
use App\Models\User;
use Livewire\Component;
use Livewire\Attributes\Url;
use Livewire\WithPagination;
use App\Traits\LocationTrait;

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

        // Load districts based on region selection
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

    public function fetch()
    {
        return $this->getOperatorQuery()->paginate($this->perPage);
    }

    public function getOperatorQuery()
    {
        $this->loadData();

        $query = User::with(['role', 'districts.region'])->where("role_id", '!=', 1) // Exclude root users
            ->where("role_id", '!=', 2); // Exclude admin users

        // Apply location-based filters
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
        // Load regions based on user permissions
        $this->regions = $this->getAccessibleRegions();

        // Load districts based on current region selection and user permissions
        $this->districts = $this->getDistricts((int)$this->region);

        // Load roles (excluding sensitive roles if needed)
        $this->roles = Role::orderBy('name')->get();

        $this->status_options = [
            'active' => 'Active',
            'inactive' => 'Inactive'
        ];
    }

    /**
     * Toggle compact view for better mobile experience
     */
    public function toggleCompactView()
    {
        $this->compactView = !$this->compactView;
    }

    /**
     * Download the PDF of operators
     */
public function downloadOperatorPDF()
{
    set_time_limit(300);
    ini_set("memory_limit", "1000M");

    return response()->streamDownload(function () {
        $operators = $this->getOperatorQuery()->get();

        $rows = [];
        foreach ($operators as $operator) {
            $todayVisits = $operator->siteVisits()->whereDate('created_at', today())->count();
            $todayFundis = $operator->createdFundis()->whereDate('created_at', today())->count();
            $monthVisits = $operator->siteVisits()
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count();
            $monthFundis = $operator->createdFundis()
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count();
            $totalVisits = $operator->siteVisits()->count();
            $totalFundis = $operator->createdFundis()->count();

            $rows[] = [
                'name' => $operator->full_name,
                'phone' => $operator->phone,
                'site' => [
                    'today' => $todayVisits,
                    'month' => $monthVisits,
                    'all_time' => $totalVisits,
                ],
                'fundis' => [
                    'today' => $todayFundis,
                    'month' => $monthFundis,
                    'all_time' => $totalFundis,
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

    /**
     * Export current filtered data to Excel
     */
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

            fputcsv($handle, [
                $operator->full_name,
                $operator->email,
                $operator->phone,
                $operator->role->display_name ?? 'N/A',
                ucfirst($operator->status),
                $districts ?: 'N/A',
                $operator->siteVisits()->whereDate('created_at', today())->count(),
                $operator->siteVisits()->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
                $operator->siteVisits()->count(),
                $operator->createdFundis()->whereDate('created_at', today())->count(),
                $operator->createdFundis()->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
                $operator->createdFundis()->count(),
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
        // For non-admin/root users, set their primary location
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

        // Auto-hide mobile filters initially on mobile
        $this->showMobileFilters = false;

        // Set compact view based on screen size
        $this->compactView = false;
    }

    public function refresh()
    {
        $this->reset([
            'search_key',
            'role_id',
            'status'
        ]);

        // Don't reset region/district for non-admin/root users
        if ($this->isAdminOrRoot()) {
            $this->reset(['region', 'district']);
        }

        // Hide mobile filters after refresh
        $this->showMobileFilters = false;
    }

    public function render()
    {
        return view('livewire.operators-table', [
            'operators' => $this->fetch(),
        ]);
    }
}

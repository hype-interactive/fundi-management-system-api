<?php

namespace App\Livewire;

use App\Models\SiteVisit;
use Livewire\Component;
use Livewire\Attributes\Url;
use Livewire\WithPagination;
use App\Traits\LocationTrait;

class SiteVisitsTable extends Component
{
    use WithPagination, LocationTrait;

    protected $paginationTheme = 'bootstrap';

    #[Url]
    public $search_key = '';

    public $perPage = 10;

    #[Url]
    public $region = null;

    #[Url]
    public $district = null;

    #[Url]
    public $ward = null;

    #[Url]
    public $status = null;

    #[Url]
    public $operator_id = null;

    #[Url]
    public $approved_by = null;

    #[Url]
    public $visit_date_from = null;

    #[Url]
    public $visit_date_to = null;

    public $regions = [];
    public $districts = [];
    public $wards = [];
    public $status_options = [];
    public $operators = [];
    public $approvers = [];

    // New properties for mobile responsiveness
    public $showMobileFilters = false;
    public $compactView = false;

    public function updatedRegion($value)
    {
        $this->district = null;
        $this->ward = null;

        // Load districts based on region selection
        $this->districts = $this->getDistricts((int)$value);
        $this->wards = collect();

        $this->resetPage();
    }

    public function updatedDistrict($value)
    {
        $this->ward = null;
        $this->wards = $this->getWards($value);
        $this->resetPage();
    }

    public function updatedSearchKey()
    {
        $this->resetPage();
    }

    public function updatedWard()
    {
        $this->resetPage();
    }

    public function updatedStatus()
    {
        $this->resetPage();
    }

    public function updatedOperatorId()
    {
        $this->resetPage();
    }

    public function updatedApprovedBy()
    {
        $this->resetPage();
    }

    public function updatedVisitDateFrom()
    {
        $this->resetPage();
    }

    public function updatedVisitDateTo()
    {
        $this->resetPage();
    }

    public function fetch()
    {
        return $this->getSiteVisitQuery()->paginate($this->perPage);
    }

    public function getSiteVisitQuery()
    {
        $this->loadData();

        $query = SiteVisit::with(['ward.district.region', 'operator', 'approver', 'photos']);

        // Apply location-based filters using the trait method
        $this->applyLocationFiltersToQuery($query, $this->region, $this->district, $this->ward);

        return $query
            ->when($this->search_key, function ($query, $search_key) {
                $query->where(function ($subQuery) use ($search_key) {
                    $subQuery->where('site_name', 'like', '%' . $search_key . '%')
                        ->orWhere('site_description', 'like', '%' . $search_key . '%')
                        ->orWhereHas('operator', function ($operatorQuery) use ($search_key) {
                            $operatorQuery->whereRaw("concat(fname, ' ', lname) like ?", ['%' . $search_key . '%']);
                        });
                });
            })
            ->when($this->status, function ($query, $status) {
                if ($status == 'approved') {
                    $query->whereNotNull('approved_at');
                } elseif ($status == 'pending') {
                    $query->whereNull('approved_at');
                } elseif ($status == 'rejected') {
                    $query->whereNotNull('rejection_reason');
                }
            })
            ->when($this->operator_id, function ($query, $operator_id) {
                $query->where('operator_id', $operator_id);
            })
            ->when($this->approved_by, function ($query, $approved_by) {
                $query->where('approved_by', $approved_by);
            })
            ->when($this->visit_date_from, function ($query, $visit_date_from) {
                $query->where('visit_date', '>=', $visit_date_from);
            })
            ->when($this->visit_date_to, function ($query, $visit_date_to) {
                $query->where('visit_date', '<=', $visit_date_to);
            })
            ->orderBy('visit_date', 'desc')
            ->latest('created_at');
    }

    public function loadData()
    {
        // Load regions based on user permissions
        $this->regions = $this->getAccessibleRegions();

        // Load districts based on current region selection and user permissions
        $this->districts = $this->getDistricts((int)$this->region);

        // Load wards based on current district selection
        $this->wards = $this->getWards($this->district);

        // Load other dropdown options
        $this->status_options = [
            'approved' => 'Approved',
            'pending' => 'Pending Approval',
            'rejected' => 'Rejected'
        ];

        $this->operators = $this->getOperators();
        $this->approvers = $this->getUsers('approved_by');
    }

    /**
     * Get users who have created or approved site visits
     */
    private function getUsers(string $type)
    {
        return \App\Models\User::whereIn('id', function ($query) use ($type) {
            $query->select($type)
                ->from('site_visits')
                ->whereNotNull($type)
                ->distinct();
        })->get();
    }

    /**
     * Get operators who have site visits
     */
    private function getOperators()
    {
        return \App\Models\User::whereIn('id', function ($query) {
            $query->select('operator_id')
                ->from('site_visits')
                ->whereNotNull('operator_id')
                ->distinct();
        })->get();
    }

    /**
     * Toggle compact view for better mobile experience
     */
    public function toggleCompactView()
    {
        $this->compactView = !$this->compactView;
    }

    /**
     * Toggle mobile filters visibility
     */
    public function toggleMobileFilters()
    {
        $this->showMobileFilters = !$this->showMobileFilters;
    }

    /**
     * Download the PDF of site visits
     */
    public function downloadSiteVisitPDF()
    {
        set_time_limit(300); // execution time to 5min
        ini_set("memory_limit", "1000M");

        return response()->streamDownload(function () {
            // Get the site visits query
            $siteVisits = $this->getSiteVisitQuery()->get();

            // Create an array of rows
            $rows = [];
            foreach ($siteVisits as $visit) {
                $location = $this->getLocationBreadcrumb(
                    $visit->ward_id,
                    $visit->ward->district_id,
                    $visit->ward->district->region_id
                );

                $rows[] = [
                    'site_name' => $visit->site_name,
                    'operator' => $visit->operator->full_name ?? 'N/A',
                    'location' => $location,
                    'visit_date' => $visit->visit_date->format('Y-m-d'),
                    'status' => $this->getVisitStatus($visit),
                    'gps_coordinates' => $visit->gps_latitude && $visit->gps_longitude
                        ? $visit->gps_latitude . ', ' . $visit->gps_longitude
                        : 'N/A',
                ];
            }

            $title = 'Site Visits | Magic Builders';
            $date = date('D, M/d/Y');
            $html_view = view('documents.site-visits', compact('title', 'date', 'rows'))->render();
            $pdf = app('dompdf.wrapper');
            $pdf->loadHTML($html_view);

            $pdf->setPaper('A4', 'landscape');

            echo $pdf->stream();
        }, 'magic_builders_site_visits.pdf');
    }

    /**
     * Export current filtered data to Excel
     */
    public function exportToExcel()
    {
        return response()->streamDownload(function () {
            $siteVisits = $this->getSiteVisitQuery()->get();

            $headers = [
                'Site Name',
                'Site Description',
                'Operator',
                'Ward',
                'District',
                'Region',
                'Visit Date',
                'GPS Latitude',
                'GPS Longitude',
                'Status',
                'Approved By',
                'Approved At',
                'Rejection Reason',
                'Photos Count',
                'Mafundi Count',
                'Products Count',
                'Created At'
            ];

            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);

            foreach ($siteVisits as $visit) {
                fputcsv($handle, [
                    $visit->site_name,
                    $visit->site_description,
                    $visit->operator->full_name ?? 'N/A',
                    $visit->ward->name ?? 'N/A',
                    $visit->ward->district->name ?? 'N/A',
                    $visit->ward->district->region->name ?? 'N/A',
                    $visit->visit_date->format('Y-m-d'),
                    $visit->gps_latitude,
                    $visit->gps_longitude,
                    $this->getVisitStatus($visit),
                    $visit->approver->full_name ?? 'N/A',
                    $visit->approved_at ? $visit->approved_at->format('Y-m-d H:i:s') : 'N/A',
                    $visit->rejection_reason ?? 'N/A',
                    $visit->photos->count(),
                    $visit->fundis->count(),
                    $visit->products->count(),
                    $visit->created_at->format('Y-m-d H:i:s')
                ]);
            }

            fclose($handle);
        }, 'site_visits_export_' . date('Y-m-d_H-i-s') . '.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Get the status of a site visit
     */
    private function getVisitStatus($visit)
    {
        // First check database status field
        if ($visit->status === 'rejected') {
            return 'Rejected';
        } elseif ($visit->status === 'approved') {
            return 'Approved';
        } elseif ($visit->status === 'pending') {
            return 'Pending';
        }

        // Fallback to the old logic for backward compatibility
        if ($visit->rejection_reason) {
            return 'Rejected';
        } elseif ($visit->approved_at) {
            return 'Approved';
        } else {
            return 'Pending';
        }
    }

    public function mount()
    {
        $user = currentUser();

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

        // Auto-hide mobile filters initially
        $this->showMobileFilters = false;
        $this->compactView = false;
    }

    public function refresh()
    {
        $this->reset([
            'search_key',
            'ward',
            'status',
            'operator_id',
            'approved_by',
            'visit_date_from',
            'visit_date_to'
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
        return view('livewire.site-visits-table', [
            'siteVisits' => $this->fetch(),
        ]);
    }
}

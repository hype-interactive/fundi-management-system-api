<?php

namespace App\Livewire;

use App\Models\SiteVisit;
use App\Models\User;
use Livewire\Component;
use Livewire\Attributes\Url;
use Livewire\WithPagination;
use App\Traits\LocationTrait;

class OperatorSiteVisits extends Component
{
    use WithPagination, LocationTrait;

    protected $paginationTheme = 'bootstrap';

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
    public $approved_by = null;

    public $regions = [];
    public $districts = [];
    public $wards = [];
    public $status_options = [];
    public $approvers = [];

    // New properties for mobile responsiveness
    public $showMobileFilters = false;
    public $compactView = false;

    // Operator model - this will be passed when component is invoked
    public $operator = null;

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

    public function updatedWard()
    {
        $this->resetPage();
    }

    public function updatedStatus()
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

    public function updatedVisiteDateTo()
    {
        $this->resetPage();
    }

    public function fetch()
    {
        return $this->getSiteVisitQuery()->paginate($this->perPage);
    }

    /**
     * Get the operator's full name
     */
    public function getOperatorName()
    {
        return $this->operator ? $this->operator->full_name : 'Unknown Operator';
    }

    /**
     * Get the operator's ID
     */
    public function getOperatorId()
    {
        return $this->operator ? $this->operator->id : null;
    }

    /**
     * Get the query for fetching site visits for the current operator
     */
    public function getSiteVisitQuery()
    {
        $this->loadData();

        // Ensure we have an operator
        if (!$this->operator) {
            return SiteVisit::whereRaw('1 = 0'); // Return empty query
        }

        $query = SiteVisit::with(['ward.district.region', 'operator', 'approver', 'photos'])
            ->where('operator_id', $this->getOperatorId()); // Use the operator's ID

        // Apply location-based filters using the trait method
        $this->applyLocationFiltersToQuery($query, $this->region, $this->district, $this->ward);

        return $query
            ->when($this->status, function ($query, $status) {
                if ($status == 'approved') {
                    $query->whereNotNull('approved_at');
                } elseif ($status == 'pending') {
                    $query->whereNull('approved_at');
                } elseif ($status == 'rejected') {
                    $query->whereNotNull('rejection_reason');
                }
            })
            ->when($this->approved_by, function ($query, $approved_by) {
                $query->where('approved_by', $approved_by);
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

        // Only load approvers who have approved this operator's site visits
        $this->approvers = $this->getApproversForOperator();
    }

    /**
     * Get users who have approved site visits for the current operator
     */
    private function getApproversForOperator()
    {
        if (!$this->operator) {
            return collect();
        }

        return User::whereIn('id', function ($query) {
            $query->select('approved_by')
                ->from('site_visits')
                ->where('operator_id', $this->getOperatorId())
                ->whereNotNull('approved_by')
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
     * Download the PDF of operator's site visits
     */
    public function downloadSiteVisitPDF()
    {
        if (!$this->operator) {
            session()->flash('error', 'No operator specified for PDF download.');
            return;
        }

        set_time_limit(300); // execution time to 5min
        ini_set("memory_limit", "1000M");

        return response()->streamDownload(function () {
            // Get the site visits query for current operator
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

            // Generate the PDF
            $title = "Site Visits - {$this->getOperatorName()} | Magic Builders";
            $date = date('D, M/d/Y');
            $html_view = view('documents.site-visits', compact('title', 'date', 'rows'))->render();
            $pdf = app('dompdf.wrapper');
            $pdf->loadHTML($html_view);

            $pdf->setPaper('A4', 'landscape');

            echo $pdf->stream();
        }, 'site_visits_' . str_replace(' ', '_', strtolower($this->getOperatorName())) . '_' . date('Y-m-d_H-i-s') . '.pdf');
    }

    /**
     * Export current filtered data to Excel
     */
    public function exportToExcel()
    {
        if (!$this->operator) {
            session()->flash('error', 'No operator specified for export.');
            return;
        }

        return response()->streamDownload(function () {
            $siteVisits = $this->getSiteVisitQuery()->get();

            $headers = [
                'Site Name',
                'Site Description',
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
        }, 'site_visits_' . str_replace(' ', '_', strtolower($this->getOperatorName())) . '_export_' . date('Y-m-d_H-i-s') . '.csv', [
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

    /**
     * Mount the component with an operator
     *
     * @param User|null $operator The operator model to display site visits for
     */
    public function mount($operator = null)
    {
        // Set the operator from the parameter
        $this->operator = $operator;

        // If no operator is provided, you might want to handle this case
        if (!$this->operator) {
            // Option 1: Use current user as operator (fallback)
            // $this->operator = currentUser();

            // Option 2: Throw an error or redirect
            // abort(404, 'Operator not found');

            // Option 3: Just leave it null and handle in queries
            // (current implementation handles this)
        }

        // Set location filters based on user permissions and operator's primary location
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
            'ward',
            'status',
            'approved_by',
        ]);

        // Don't reset region/district for non-admin/root users
        if ($this->isAdminOrRoot()) {
            $this->reset(['region', 'district']);
        }

        // Hide mobile filters after refresh
        $this->showMobileFilters = false;
    }

    /**
     * Get total site visits count for current operator
     */
    public function getTotalSiteVisitsProperty()
    {
        if (!$this->operator) {
            return 0;
        }

        return SiteVisit::where('operator_id', $this->getOperatorId())->count();
    }

    /**
     * Get approved site visits count for current operator
     */
    public function getApprovedSiteVisitsProperty()
    {
        if (!$this->operator) {
            return 0;
        }

        return SiteVisit::where('operator_id', $this->getOperatorId())
            ->whereNotNull('approved_at')
            ->count();
    }

    /**
     * Get pending site visits count for current operator
     */
    public function getPendingSiteVisitsProperty()
    {
        if (!$this->operator) {
            return 0;
        }

        return SiteVisit::where('operator_id', $this->getOperatorId())
            ->whereNull('approved_at')
            ->whereNull('rejection_reason')
            ->count();
    }

    /**
     * Get rejected site visits count for current operator
     */
    public function getRejectedSiteVisitsProperty()
    {
        if (!$this->operator) {
            return 0;
        }

        return SiteVisit::where('operator_id', $this->getOperatorId())
            ->whereNotNull('rejection_reason')
            ->count();
    }

    public function render()
    {
        return view('livewire.operator-site-visits', [
            'siteVisits' => $this->fetch(),
        ]);
    }
}

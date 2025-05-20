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

    public $districts = [];
    public $wards = [];
    public $status_options = [];
    public $operators = [];
    public $approvers = [];
    public $isAdminOrRoot = false;

    // New properties for mobile responsiveness
    public $showMobileFilters = false;
    public $compactView = false;

    public function updatedDistrict($value)
    {
        $this->ward = null;
        $this->wards = $this->getWards($value);
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
        $this->getData();

        $query = SiteVisit::with(['ward.district', 'operator', 'approver', 'photos']);

        // Apply district filter only if not admin/root or if a district is explicitly selected
        if (!$this->isAdminOrRoot || $this->district) {
            $query->whereHas('ward', function ($query) {
                $query->whereHas('district', function ($subQuery) {
                    if ($this->district) {
                        $subQuery->where('id', $this->district);
                    }
                });
            });
        }

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
            ->when($this->ward, function ($query, $ward) {
                $query->where('ward_id', $ward);
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

    public function getData()
    {
        // Get all districts for admins/root, otherwise just get the user's districts
        $this->districts = $this->isAdminOrRoot ? $this->getAllDistricts() : $this->getDistricts();

        $this->wards = $this->getWards($this->district);
        $this->status_options = [
            'approved' => 'Approved',
            'pending' => 'Pending Approval',
            'rejected' => 'Rejected'
        ];
        $this->operators = $this->getOperators();
        $this->approvers = $this->getUsers('approved_by');
    }

    /**
     * Get all districts without user restrictions
     */
    private function getAllDistricts()
    {
        return \App\Models\District::orderBy('name')->get();
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
                $rows[] = [
                    'site_name' => $visit->site_name,
                    'operator' => $visit->operator->full_name ?? 'N/A',
                    'location' => $visit->ward->name . ', ' . $visit->ward->district->name,
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
                'Visit Date',
                'GPS Latitude',
                'GPS Longitude',
                'Status',
                'Approved By',
                'Approved At',
                'Rejection Reason',
                'Photos Count',
                'Fundis Count',
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
        $currentUser = currentUser();

        // Check if user is admin or root
        $this->isAdminOrRoot = $currentUser && ($currentUser->role->name === 'admin' || $currentUser->role->name === 'root');

        // For non-admin/root users, set their primary district
        if (!$this->isAdminOrRoot) {
            $this->district = $currentUser?->primary_district?->id ?? 2; // fallback to Dar es Salaam
        }

        // Auto-hide mobile filters initially on mobile
        $this->showMobileFilters = false;

        // Set compact view based on screen size (you can add logic to detect this)
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

        // Don't reset district for non-admin/root users
        if ($this->isAdminOrRoot) {
            $this->reset(['district']);
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

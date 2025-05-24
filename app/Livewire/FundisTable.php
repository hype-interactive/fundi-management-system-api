<?php

namespace App\Livewire;

use App\Models\Fundi;
use Livewire\Component;
use Livewire\Attributes\Url;
use Livewire\WithPagination;
use App\Traits\LocationTrait;

class FundisTable extends Component
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
    public $gender = null;

    #[Url]
    public $ranking = null;

    #[Url]
    public $approval_status = null;

    #[Url]
    public $created_by = null;

    #[Url]
    public $approved_by = null;

    #[Url]
    public $legacy_match = null;

    public $regions = [];
    public $districts = [];
    public $wards = [];
    public $gender_options = [];
    public $ranking_options = [];
    public $approval_status_options = [];
    public $creators = [];
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

    public function updatedGender()
    {
        $this->resetPage();
    }

    public function updatedRanking()
    {
        $this->resetPage();
    }

    public function updatedApprovalStatus()
    {
        $this->resetPage();
    }

    public function updatedCreatedBy()
    {
        $this->resetPage();
    }

    public function updatedApprovedBy()
    {
        $this->resetPage();
    }

    public function updatedLegacyMatch()
    {
        $this->resetPage();
    }

    public function fetch()
    {
        return $this->getFundiQuery()->paginate($this->perPage);
    }

    public function getFundiQuery()
    {
        $this->loadData();

        $query = Fundi::with('ward.district.region');

        // Apply location-based filters using the trait method
        $this->applyLocationFiltersToQuery($query, $this->region, $this->district, $this->ward);

        return $query
            ->when($this->search_key, function ($query, $search_key) {
                $query->whereRaw("concat(fname, ' ', mname, ' ', lname) like ?", ['%' . $search_key . '%'])
                    ->orWhere('primary_phone', 'like', '%' . $search_key . '%')
                    ->orWhere('transaction_phone', 'like', '%' . $search_key . '%');
            })
            // ->when($this->ward, function ($query, $ward) {
            //     $query->where('ward_id', $ward);
            // })
            ->when($this->gender, function ($query, $gender) {
                $query->where('gender', $gender);
            })
            ->when($this->ranking, function ($query, $ranking) {
                $query->where('ranking', $ranking);
            })
            ->when($this->approval_status, function ($query, $approval_status) {
                if ($approval_status == 'approved') {
                    $query->whereNotNull('approved_at');
                } elseif ($approval_status == 'pending') {
                    $query->whereNull('approved_at');
                }
            })
            ->when($this->created_by, function ($query, $created_by) {
                $query->where('created_by', $created_by);
            })
            ->when($this->approved_by, function ($query, $approved_by) {
                $query->where('approved_by', $approved_by);
            })
            ->when($this->legacy_match, function ($query, $legacy_match) {
                $query->where('legacy_match', $legacy_match);
            })
            // ->orderBy('fname', 'asc')
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

        $this->gender_options = ['male' => 'Male', 'female' => 'Female'];
        $this->ranking_options = ['fundi_kuu' => 'Fundi Mkuu', 'fundi_msaidizi' => 'Fundi'];
        $this->approval_status_options = ['approved' => 'Approved', 'pending' => 'Pending Approval'];
        $this->creators = $this->getUsers('created_by');
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
     * Get users who have created or approved fundis
     */
    private function getUsers(string $type)
    {
        return \App\Models\User::whereIn('id', function ($query) use ($type) {
            $query->select($type)
                ->from('fundis')
                ->whereNotNull($type)
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
     * Download the PDF of fundis
     */
    public function downloadFundiPDF()
    {
        set_time_limit(300); // execution time to 5min
        ini_set("memory_limit", "1000M");

        return response()->streamDownload(function () {
            // Get the fundis query
            $fundis = $this->getFundiQuery()->get();

            // Create an array of rows
            $rows = [];
            foreach ($fundis as $fundi) {
                $location = $this->getLocationBreadcrumb(
                    $fundi->ward_id,
                    $fundi->ward->district_id,
                    $fundi->ward->district->region_id
                );

                $rows[] = [
                    'name' => $fundi->full_name,
                    'address' => $location,
                    'primary_phone' => $fundi->primary_phone,
                    'transaction_phone' => $fundi->transaction_phone,
                    'gender' => ucfirst($fundi->gender),
                    'ranking' => $this->ranking_options[$fundi->ranking] ?? 'N/A',
                    'status' => $fundi->approved_at ? 'Approved' : 'Pending',
                ];
            }

            $title = 'Mafundi | Magic Builders';
            $date = date('D, M/d/Y');
            $html_view = view('documents.fundis', compact('title', 'date', 'rows'))->render();
            $pdf = app('dompdf.wrapper');
            $pdf->loadHTML($html_view);

            $pdf->setPaper('A4', 'landscape');

            echo $pdf->stream();
        }, 'magic_builders_mafundi.pdf');
    }

    /**
     * Export current filtered data to Excel
     */
    public function exportToExcel()
    {
        return response()->streamDownload(function () {
            $fundis = $this->getFundiQuery()->get();

            $headers = [
                'Name',
                'Gender',
                'Primary Phone',
                'Transaction Phone',
                'NIDA Number',
                'Ward',
                'District',
                'Region',
                'Ranking',
                'Status',
                'Registered By',
                'Approved By',
                'Created At',
                'Approved At'
            ];

            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);

            foreach ($fundis as $fundi) {
                fputcsv($handle, [
                    $fundi->full_name,
                    ucfirst($fundi->gender),
                    $fundi->primary_phone,
                    $fundi->transaction_phone,
                    $fundi->nida_number,
                    $fundi->ward->name ?? 'N/A',
                    $fundi->ward->district->name ?? 'N/A',
                    $fundi->ward->district->region->name ?? 'N/A',
                    $this->ranking_options[$fundi->ranking] ?? 'N/A',
                    $fundi->approved_at ? 'Approved' : 'Pending',
                    $fundi->creator->full_name ?? 'N/A',
                    $fundi->approver->full_name ?? 'N/A',
                    $fundi->created_at->format('Y-m-d H:i:s'),
                    $fundi->approved_at ? $fundi->approved_at->format('Y-m-d H:i:s') : 'N/A'
                ]);
            }

            fclose($handle);
        }, 'fundis_export_' . date('Y-m-d_H-i-s') . '.csv', [
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

        // Set compact view based on screen size (you can add logic to detect this)
        $this->compactView = false;
    }

    public function refresh()
    {
        $this->reset([
            'search_key',
            'ward',
            'gender',
            'ranking',
            'approval_status',
            'created_by',
            'approved_by',
            'legacy_match'
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
        return view('livewire.fundis-table', [
            'fundis' => $this->fetch(),
        ]);
    }
}

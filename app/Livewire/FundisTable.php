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

    public $districts = [];
    public $wards = [];
    public $gender_options = [];
    public $ranking_options = [];
    public $approval_status_options = [];
    public $creators = [];
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
        $this->getData();

        $query = Fundi::with('ward.district');

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
                $query->whereRaw("concat(fname, ' ', mname, ' ', lname) like ?", ['%' . $search_key . '%'])
                    ->orWhere('primary_phone', 'like', '%' . $search_key . '%')
                    ->orWhere('transaction_phone', 'like', '%' . $search_key . '%');
            })
            ->when($this->ward, function ($query, $ward) {
                $query->where('ward_id', $ward);
            })
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
            ->orderBy('fname', 'asc')
            ->latest('created_at');
    }

    public function getData()
    {
        // Get all districts for admins/root, otherwise just get the user's districts
        $this->districts = $this->isAdminOrRoot ? $this->getAllDistricts() : $this->getDistricts();

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
                $rows[] = [
                    'name' => $fundi->full_name,
                    'address' => $fundi->ward->name . ', ' . $fundi->ward->district->name,
                    'phone' => $fundi->primary_phone,
                    'gender' => ucfirst($fundi->gender),
                    'ranking' => $this->ranking_options[$fundi->ranking] ?? 'N/A',
                    'status' => $fundi->approved_at ? 'Approved' : 'Pending',
                ];
            }

            $title = 'Fundis | Mafundi';
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
            'gender',
            'ranking',
            'approval_status',
            'created_by',
            'approved_by',
            'legacy_match'
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
        return view('livewire.fundis-table', [
            'fundis' => $this->fetch(),
        ]);
    }
}

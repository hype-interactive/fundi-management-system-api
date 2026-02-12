<?php

namespace App\Livewire;

use App\Models\Fundi;
use App\Traits\HasAlerts;
use Livewire\Component;
use Livewire\Attributes\Url;
use Livewire\WithPagination;
use App\Traits\LocationTrait;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Jobs\GenerateFundiIdCards;
use Illuminate\Support\Str;

class FundisTable extends Component
{
    use WithPagination, LocationTrait, HasAlerts;

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

    // Bulk selection properties
    public $selectedFundis = [];
    public $selectAll = false;

    protected $listeners = [
        'executeSingleDelete' => 'executeSingleDelete',
        'executeBulkDelete' => 'executeBulkDelete'
    ];

    public $bulkDownloadInProgress = false;
    public $bulkDownloadBatchId = null;
    public $downloadUrl = null;

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

        // Display any flashed alerts
        $this->displayFlashedAlerts();

        $this->processQrCodesInBackground();
    }

    public function processQrCodesInBackground()
    {
        // Get fundis without QR codes (limit to avoid timeout)
        $fundisWithoutQr = Fundi::whereNull('qr_code_path')
            ->orWhere('qr_code_path', '')
            ->limit(100)
            ->latest('created_at')
            ->get();

        foreach ($fundisWithoutQr as $fundi) {
            $fundi->generateQrCode();
        }
    }



    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedFundis = $this->fetch()->pluck('id')->toArray();
        } else {
            $this->selectedFundis = [];
        }
    }

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

    /**
     * Bulk download ID cards for filtered fundis
     */
    public function bulkDownloadIdCards()
    {
        // Get filtered fundi IDs
        $fundiIds = $this->getFilteredQuery()->pluck('id')->toArray();

        if (empty($fundiIds)) {
            session()->flash('error', 'No fundis found to download.');
            return;
        }

        if (count($fundiIds) > 5000) {
            session()->flash('error', 'Too many fundis selected. Please narrow your filters (max 5000).');
            return;
        }

        // Generate unique batch ID (cast to string for Livewire)
        $batchId = (string) Str::uuid();
        $this->bulkDownloadBatchId = $batchId;
        $this->bulkDownloadInProgress = true;
        $this->downloadUrl = null;

        // Dispatch job
        GenerateFundiIdCards::dispatch($fundiIds, auth()->id(), $batchId);

        session()->flash('success', 'ID cards are being generated. This may take a few minutes.');
    }

    /**
     * Check if bulk download is ready
     */
    public function checkBulkDownloadStatus()
    {
        if (!$this->bulkDownloadBatchId || !$this->bulkDownloadInProgress) {
            return;
        }

        $fileName = "fundi_ids_{$this->bulkDownloadBatchId}.zip";
        
        if (Storage::disk('public')->exists("downloads/{$fileName}")) {
            $this->bulkDownloadInProgress = false;
            $this->downloadUrl = Storage::url("downloads/{$fileName}");
            
            session()->flash('success', 'Download ready! Click the button to download.');
            
            // Trigger browser download via JavaScript
            $this->dispatch('trigger-download', url: $this->downloadUrl);
        }
    }

        public function resetDownload()
    {
        $this->bulkDownloadInProgress = false;
        $this->bulkDownloadBatchId = null;
        $this->downloadUrl = null;
    }

        /**
     * Get filtered query (reusable method)
     */
    protected function getFilteredQuery()
    {
        $query = Fundi::query()
            ->with(['ward.district.region', 'creator']);

        // Apply search
        if ($this->search_key) {
            $query->where(function ($q) {
                $q->where('fname', 'like', '%' . $this->search_key . '%')
                    ->orWhere('lname', 'like', '%' . $this->search_key . '%')
                    ->orWhere('mname', 'like', '%' . $this->search_key . '%')
                    ->orWhere('unique_id', 'like', '%' . $this->search_key . '%')
                    ->orWhere('primary_phone', 'like', '%' . $this->search_key . '%');
            });
        }

        // Apply region filter
        if ($this->region) {
            $query->whereHas('ward.district.region', function ($q) {
                $q->where('id', $this->region);
            });
        }

        // Apply district filter
        if ($this->district) {
            $query->whereHas('ward.district', function ($q) {
                $q->where('id', $this->district);
            });
        }

        // Apply ward filter
        if ($this->ward) {
            $query->where('ward_id', $this->ward);
        }

        // Apply ranking filter
        if ($this->ranking) {
            $query->where('ranking', $this->ranking);
        }

        // Apply approval filter
        if ($this->approval_status) {
            if ($this->approval_status === 'approved') {
                $query->approved();
            } elseif ($this->approval_status === 'rejected') {
                $query->rejected();
            } elseif ($this->approval_status === 'pending') {
                $query->pending();
            }
        }

        return $query;
    }

   public function getFundiQuery()
{
    $this->loadData();

    $query = Fundi::query()
        ->select([
            'fundis.id',
            'fundis.fname',
            'fundis.mname',
            'fundis.lname',
            'fundis.gender',
            'fundis.primary_phone',
            'fundis.transaction_phone',
            'fundis.nida_number',
            'fundis.ranking',
            'fundis.status',
            'fundis.ward_id',
            'fundis.approved_at',
            'fundis.rejected_at',
            'fundis.approved_by',
            'fundis.created_by',
            'fundis.created_at',
            'fundis.legacy_match',
            'fundis.legacy_id',
            DB::raw("CONCAT_WS(' ', fname, mname, lname) as full_name")
        ])
        ->with([
            'ward:id,name,district_id',
            'ward.district:id,name,region_id',
            'ward.district.region:id,name'
        ]);

    $this->applyLocationFiltersToQuery($query, $this->region, $this->district, $this->ward);

    if ($this->search_key) {
        $searchTerm = '%' . $this->search_key . '%';
        $query->where(function ($q) use ($searchTerm) {
            $q->whereRaw("CONCAT_WS(' ', fname, mname, lname) LIKE ?", [$searchTerm]);

            // Handle phone search if the search term looks like a phone number
            if (preg_match('/^[0-9\s\-\+]+$/', $this->search_key)) {
                $formattedPhone = $this->formatSearchStringForPhoneSearch($this->search_key);
                
                // Search by removing spaces from stored phone numbers
                $q->orWhereRaw("REPLACE(REPLACE(primary_phone, ' ', ''), '-', '') LIKE ?", [$formattedPhone])
                  ->orWhereRaw("REPLACE(REPLACE(transaction_phone, ' ', ''), '-', '') LIKE ?", [$formattedPhone]);
            }
        });
    }

    return $query
        ->when($this->gender, fn($q, $gender) => $q->where('fundis.gender', $gender))
        ->when($this->ranking, fn($q, $ranking) => $q->where('fundis.ranking', $ranking))
        ->when($this->approval_status, function ($q, $approval_status) {
            if ($approval_status === 'approved') {
                $q->whereNotNull('fundis.approved_at')->whereNull('fundis.rejected_at');
            } elseif ($approval_status === 'rejected') {
                $q->whereNotNull('fundis.rejected_at');
            } elseif ($approval_status === 'pending') {
                $q->whereNull('fundis.approved_at')->whereNull('fundis.rejected_at');
            }
        })
        ->when($this->created_by, fn($q, $created_by) => $q->where('fundis.created_by', $created_by))
        ->when($this->approved_by, fn($q, $approved_by) => $q->where('fundis.approved_by', $approved_by))
        ->when($this->legacy_match, fn($q, $legacy_match) => $q->where('fundis.legacy_match', $legacy_match))
        ->latest('fundis.created_at');
}

// Format search string for phone searchability  
public function formatSearchStringForPhoneSearch($searchTerm)
{
    // Remove all non-digit characters (spaces, dashes, etc.)
    $digitsOnly = preg_replace('/\D/', '', $searchTerm);
    
    // Remove leading zeros
    $digitsOnly = ltrim($digitsOnly, '0');
    
    // Add country code and wildcard for LIKE query
    return '%+255' . $digitsOnly . '%';
}

    public function loadData()
    {
        // Load regions based on user permissions (with caching)
        $this->regions = $this->getAccessibleRegions();

        // Load districts based on current region selection and user permissions
        $this->districts = $this->getDistricts((int)$this->region);

        // Load wards based on current district selection
        $this->wards = $this->getWards($this->district);

        // Static options - no need to set every time
        $this->gender_options = ['male' => 'Male', 'female' => 'Female'];
        $this->ranking_options = ['fundi_kuu' => 'Fundi Mkuu', 'fundi_msaidizi' => 'Fundi'];
        $this->approval_status_options = [
            'approved' => 'Approved',
            'pending' => 'Pending Approval',
            'rejected' => 'Rejected'
        ];

        // Cache user lists for better performance
        $this->creators = $this->getCachedUsers('created_by');
        $this->approvers = $this->getCachedUsers('approved_by');
    }

    /**
     * Get cached users who have created or approved fundis
     */
    private function getCachedUsers(string $type): \Illuminate\Support\Collection
    {
        $cacheKey = "fundis_users_{$type}";

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($type) {
            return \App\Models\User::select('id', 'fname', 'lname', 'email')
                ->whereIn('id', function ($query) use ($type) {
                    $query->select($type)
                        ->from('fundis')
                        ->whereNotNull($type)
                        ->distinct();
                })
                ->orderBy('fname')
                ->get();
        });
    }

    /**
     * Get all districts without user restrictions
     */
    private function getAllDistricts()
    {
        return Cache::remember('all_districts', now()->addHours(24), function () {
            return \App\Models\District::select('id', 'name', 'region_id')
                ->orderBy('name')
                ->get();
        });
    }

    /**
     * Toggle compact view for better mobile experience
     */
    public function toggleCompactView()
    {
        $this->compactView = !$this->compactView;
    }

    /**
     * Handle fundi updated event
     */
    public function handleFundiUpdated($message = 'Fundi updated successfully.')
    {
        $this->showSuccessAlert('Success', $message);
        $this->clearUserCache(); // Clear cache when data changes
        $this->refreshData();
    }

    /**
     * Delete single fundi - Show confirmation
     */
    public function deleteFundi($fundi_id)
    {
        $this->showDeleteConfirmation(
            'executeSingleDelete',
            ['fundi_id' => $fundi_id],
            'Delete Fundi?',
            'Are you sure you want to permanently delete this fundi? This action cannot be undone.'
        );
    }

    /**
     * Execute single delete - Called after confirmation
     */
    public function executeSingleDelete($fundi_id)
    {
        $result = $this->handleOperation(
            function () use ($fundi_id) {
                return Fundi::where('id', $fundi_id)->delete();
            },
            'Deleted!',
            'Fundi deleted successfully',
            'Error',
            'Failed to delete fundi',
            true // Use toast
        );

        if ($result) {
            // Remove from selected array
            $this->selectedFundis = array_diff($this->selectedFundis, [$fundi_id]);
        }
    }

    /**
     * Bulk delete fundis - Show confirmation
     */
    public function bulkDelete()
    {
        if (empty($this->selectedFundis)) {
            $this->showWarningAlert('No Selection', 'Please select at least one fundi to delete');
            return;
        }

        $count = count($this->selectedFundis);

        $this->showBulkDeleteConfirmation(
            $count,
            'executeBulkDelete'
        );
    }

    /**
     * Execute bulk delete - Called after confirmation
     */
    public function executeBulkDelete()
    {
        if (empty($this->selectedFundis)) {
            return;
        }

        $count = count($this->selectedFundis);

        $result = $this->handleOperation(
            function () {
                return Fundi::whereIn('id', $this->selectedFundis)->delete();
            },
            'Deleted!',
            "Successfully deleted {$count} fundi(s)",
            'Error',
            'Failed to delete fundis'
        );

        if ($result) {
            $this->selectedFundis = [];
            $this->selectAll = false;
        }
    }

    /**
     * Download the PDF of fundis
     */
    public function downloadFundisPDF()
    {
        set_time_limit(300);
        ini_set("memory_limit", "1000M");

        return response()->streamDownload(function () {
            // Use chunk to avoid memory issues with large datasets
            $query = $this->getFundiQuery();

            $rows = [];
            $query->chunk(500, function ($fundis) use (&$rows) {
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
            });

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
     * Export current filtered data to Excel - optimized for large datasets
     */
    public function exportToExcel()
    {
        return response()->streamDownload(function () {
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

            // Use cursor for memory efficiency with large datasets
            $this->getFundiQuery()
                ->with(['creator:id,fname,lname', 'approver:id,fname,lname'])
                ->chunk(500, function ($fundis) use ($handle) {
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
                            $fundi->approval_status ?? 'N/A',
                            $fundi->creator->full_name ?? 'N/A',
                            $fundi->approver->full_name ?? 'N/A',
                            $fundi->created_at->format('Y-m-d H:i:s'),
                            $fundi->approved_at ? $fundi->approved_at->format('Y-m-d H:i:s') : 'N/A'
                        ]);
                    }
                });

            fclose($handle);
        }, 'fundis_export_' . date('Y-m-d_H-i-s') . '.csv', [
            'Content-Type' => 'text/csv',
        ]);
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
            'legacy_match',
            'selectedFundis',
            'selectAll',
            'region',
            'district'
        ]);

        // Don't reset region/district for non-admin/root users
        // if ($this->isAdminOrRoot()) {
        //     $this->reset(['region', 'district']);
        // }

        // Clear cache on refresh
        // $this->clearUserCache();

        // Hide mobile filters after refresh
        // $this->showMobileFilters = false;
    }

    public function render()
    {
        return view('livewire.fundis-table', [
            'fundis' => $this->fetch(),
        ]);
    }
}

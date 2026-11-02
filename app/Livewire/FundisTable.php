<?php

namespace App\Livewire;

use App\Jobs\GenerateFundiIdCards;
use App\Models\Fundi;
use App\Services\FundiListQuery;
use App\Traits\HasAlerts;
use App\Traits\LocationTrait;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class FundisTable extends Component
{
    use HasAlerts, LocationTrait, WithPagination;

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
    public $site_visit_count = null;

    #[Url]
    public $approval_status = null;

    #[Url]
    public $created_by = null;

    #[Url]
    public $approved_by = null;

    #[Url]
    public $legacy_match = null;

    #[Url]
    public $date_from = null;

    #[Url]
    public $date_to = null;

    public $regions = [];

    public $districts = [];

    public $wards = [];

    public $gender_options = [];

    public $site_visit_count_options = [];

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
        'executeBulkDelete' => 'executeBulkDelete',
    ];

    public $bulkDownloadInProgress = false;

    public $bulkDownloadBatchId = null;

    public $downloadUrl = null;

    public function mount()
    {
        // For non-admin/root users, set their primary location
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
        $this->districts = $this->getDistricts((int) $value);
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

    // With:
    public function updatedSiteVisitCount()
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
        return $this->getFundiQuery()->paginate($this->perPage);
    }

    /**
     * Bulk download ID cards for filtered fundis
     */
    public function bulkDownloadIdCards()
    {
        // Get filtered fundi IDs
        $fundiIds = $this->getFundiQuery()->pluck('fundis.id')->toArray();

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
        if (! $this->bulkDownloadBatchId || ! $this->bulkDownloadInProgress) {
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

    public function getFundiQuery()
    {
        $this->loadData();

        return app(FundiListQuery::class)->build($this->filters());
    }

    /**
     * The active filters, in the form the export routes accept them.
     */
    public function filters(): array
    {
        return collect([
            'search_key' => $this->search_key,
            'region' => $this->region,
            'district' => $this->district,
            'ward' => $this->ward,
            'gender' => $this->gender,
            'site_visit_count' => $this->site_visit_count,
            'approval_status' => $this->approval_status,
            'created_by' => $this->created_by,
            'approved_by' => $this->approved_by,
            'legacy_match' => $this->legacy_match,
            'date_from' => $this->date_from,
            'date_to' => $this->date_to,
        ])->reject(fn ($value) => $value === null || $value === '')->all();
    }

    public function loadData()
    {
        // Load regions based on user permissions (with caching)
        $this->regions = $this->getAccessibleRegions();

        // Load districts based on current region selection and user permissions
        $this->districts = $this->getDistricts((int) $this->region);

        // Load wards based on current district selection
        $this->wards = $this->getWards($this->district);

        // Static options - no need to set every time
        $this->gender_options = ['male' => 'Male', 'female' => 'Female'];
        // Replace with:
        $this->site_visit_count_options = [
            '0' => '0 Site Visits',
            '1-5' => '1–5 Site Visits',
            '5+' => 'Above 5 Site Visits',
        ];
        $this->approval_status_options = [
            'approved' => 'Approved',
            'pending' => 'Pending Approval',
            'rejected' => 'Rejected',
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
        $this->compactView = ! $this->compactView;
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

    public function refresh()
    {
        $this->reset([
            'search_key',
            'ward',
            'gender',
            'site_visit_count',  // was 'ranking'
            'approval_status',
            'created_by',
            'approved_by',
            'legacy_match',
            'date_from',
            'date_to',
            'selectedFundis',
            'selectAll',
            'region',
            'district',
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

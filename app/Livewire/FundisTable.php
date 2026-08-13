<?php

namespace App\Livewire;

use App\Jobs\GenerateFundiIdCards;
use App\Jobs\GenerateFundisPdfReport;
use App\Models\Fundi;
use App\Traits\HasAlerts;
use App\Traits\LocationTrait;
use Illuminate\Support\Facades\DB;
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
    public $ranking = null;

    #[Url]
    public $site_visit_min = null;

    #[Url]
    public $site_visit_max = null;

    #[Url]
    public $bags_min = null;

    #[Url]
    public $bags_max = null;

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

    // New properties for mobile responsiveness
    public $showMobileFilters = false;

    public $compactView = false;

    // Bulk selection properties
    public $selectedFundis = [];

    public $selectAll = false;

    protected $listeners = [
        'executeSingleDelete' => 'executeSingleDelete',
        'executeBulkDelete' => 'executeBulkDelete',
        'filtersApplied' => 'applyFiltersFromModal',
        'filtersCleared' => 'clearFiltersFromModal',
    ];

    public $bulkDownloadInProgress = false;

    public $bulkDownloadBatchId = null;

    public $downloadUrl = null;

    public $pdfExportInProgress = false;

    public $pdfExportBatchId = null;

    public $pdfDownloadUrl = null;

    public $pdfExportFailedChunks = [];

    public $pdfExportPendingConfirm = false;

    public $pdfExportRowCount = null;

    public $pdfExportDistrictCount = null;

    public $pdfExportFileCount = null;

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

    public function updatedSearchKey()
    {
        $this->resetPage();
    }

    public function updatedApprovalStatus()
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

    /**
     * Relay the currently committed filter values to the filter modal so it can
     * populate its pending state - covers reopening after Apply, after a chip
     * removal, or after a shared URL hydrated the committed properties.
     */
    public function openFilterModal()
    {
        $this->dispatch('sync-filter-modal', filters: [
            'region' => $this->region,
            'district' => $this->district,
            'ward' => $this->ward,
            'gender' => $this->gender,
            'ranking' => $this->ranking,
            'site_visit_min' => $this->site_visit_min,
            'site_visit_max' => $this->site_visit_max,
            'bags_min' => $this->bags_min,
            'bags_max' => $this->bags_max,
            'created_by' => $this->created_by,
            'date_from' => $this->date_from,
            'date_to' => $this->date_to,
        ]);
    }

    /**
     * Commit the filter modal's Apply payload - the only point at which the
     * committed filter properties change and the list query re-runs.
     */
    public function applyFiltersFromModal(array $filters)
    {
        $this->region = $filters['region'] ?? null;
        $this->district = $filters['district'] ?? null;
        $this->ward = $filters['ward'] ?? null;
        $this->gender = $filters['gender'] ?? null;
        $this->ranking = $filters['ranking'] ?? null;
        $this->site_visit_min = $filters['site_visit_min'] ?? null;
        $this->site_visit_max = $filters['site_visit_max'] ?? null;
        $this->bags_min = $filters['bags_min'] ?? null;
        $this->bags_max = $filters['bags_max'] ?? null;
        $this->created_by = $filters['created_by'] ?? null;
        $this->date_from = $filters['date_from'] ?? null;
        $this->date_to = $filters['date_to'] ?? null;

        $this->resetPage();
    }

    public function clearFiltersFromModal()
    {
        $this->reset([
            'region', 'district', 'ward', 'gender', 'ranking',
            'site_visit_min', 'site_visit_max', 'bags_min', 'bags_max',
            'created_by', 'date_from', 'date_to',
        ]);

        $this->resetPage();
    }

    /**
     * Remove a single active filter chip and re-run the query.
     */
    public function removeFilter(string $key)
    {
        $this->reset([$key]);
        $this->resetPage();
    }

    /**
     * Active filter chips for display on the page when the modal is closed.
     * Location/registrar values are resolved to names via cheap indexed lookups.
     */
    public function activeFilters(): array
    {
        $chips = [];

        if ($this->region) {
            $chips[] = ['key' => 'region', 'label' => 'Region: '.(\App\Models\Region::find($this->region)?->name ?? $this->region)];
        }
        if ($this->district) {
            $chips[] = ['key' => 'district', 'label' => 'District: '.(\App\Models\District::find($this->district)?->name ?? $this->district)];
        }
        if ($this->ward) {
            $chips[] = ['key' => 'ward', 'label' => 'Ward: '.(\App\Models\Ward::find($this->ward)?->name ?? $this->ward)];
        }
        if ($this->gender) {
            $chips[] = ['key' => 'gender', 'label' => 'Gender: '.ucfirst($this->gender)];
        }
        if ($this->ranking) {
            $chips[] = ['key' => 'ranking', 'label' => 'Ranking: '.($this->ranking === 'fundi_kuu' ? 'Fundi Mkuu' : 'Fundi Msaidizi')];
        }
        if ($this->site_visit_min !== null && $this->site_visit_min !== '') {
            $chips[] = ['key' => 'site_visit_min', 'label' => 'Min Sites: '.$this->site_visit_min];
        }
        if ($this->site_visit_max !== null && $this->site_visit_max !== '') {
            $chips[] = ['key' => 'site_visit_max', 'label' => 'Max Sites: '.$this->site_visit_max];
        }
        if ($this->bags_min !== null && $this->bags_min !== '') {
            $chips[] = ['key' => 'bags_min', 'label' => 'Min Putty Bags: '.$this->bags_min];
        }
        if ($this->bags_max !== null && $this->bags_max !== '') {
            $chips[] = ['key' => 'bags_max', 'label' => 'Max Putty Bags: '.$this->bags_max];
        }
        if ($this->created_by) {
            $chips[] = ['key' => 'created_by', 'label' => 'Registrar: '.(\App\Models\User::find($this->created_by)?->full_name ?? $this->created_by)];
        }
        if ($this->date_from) {
            $chips[] = ['key' => 'date_from', 'label' => 'From: '.$this->date_from];
        }
        if ($this->date_to) {
            $chips[] = ['key' => 'date_to', 'label' => 'To: '.$this->date_to];
        }

        return $chips;
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
                DB::raw("CONCAT_WS(' ', fname, mname, lname) as full_name"),
            ])
            ->with([
                'ward:id,name,district_id',
                'ward.district:id,name,region_id',
                'ward.district.region:id,name',
                'creator:id,fname,lname',
            ])
            ->withMafundiReportStats();

        $this->applyLocationFiltersToQuery($query, $this->region, $this->district, $this->ward);

        if ($this->search_key) {
            $searchTerm = '%'.$this->search_key.'%';
            $query->where(function ($q) use ($searchTerm) {
                $q->whereRaw("CONCAT_WS(' ', fname, mname, lname) LIKE ?", [$searchTerm])
                    ->orWhere('fundis.unique_id', 'like', $searchTerm);

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
            ->when($this->gender, fn ($q, $gender) => $q->where('fundis.gender', $gender))
            ->when($this->ranking, fn ($q, $ranking) => $q->where('fundis.ranking', $ranking))
            ->when($this->site_visit_min !== null && $this->site_visit_min !== '', fn ($q) => $q->having('site_visits_count', '>=', (int) $this->site_visit_min))
            ->when($this->site_visit_max !== null && $this->site_visit_max !== '', fn ($q) => $q->having('site_visits_count', '<=', (int) $this->site_visit_max))
            ->when($this->bags_min !== null && $this->bags_min !== '', fn ($q) => $q->having('bags_total', '>=', (int) $this->bags_min))
            ->when($this->bags_max !== null && $this->bags_max !== '', fn ($q) => $q->having('bags_total', '<=', (int) $this->bags_max))
            ->when($this->approval_status, function ($q, $approval_status) {
                if ($approval_status === 'approved') {
                    $q->approved();
                } elseif ($approval_status === 'rejected') {
                    $q->rejected();
                } elseif ($approval_status === 'pending') {
                    $q->pending();
                }
            })
            ->when($this->created_by, fn ($q, $created_by) => $q->where('fundis.created_by', $created_by))
            ->when($this->approved_by, fn ($q, $approved_by) => $q->where('fundis.approved_by', $approved_by))
            ->when($this->legacy_match, fn ($q, $legacy_match) => $q->where('fundis.legacy_match', $legacy_match))
            ->when($this->date_from, fn ($q, $date_from) => $q->whereDate('fundis.created_at', '>=', $date_from))
            ->when($this->date_to, fn ($q, $date_to) => $q->whereDate('fundis.created_at', '<=', $date_to))
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
        return '%+255'.$digitsOnly.'%';
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

    /**
     * Start a PDF export. At or below the row cap, dispatches immediately as
     * today. Above the cap, the export splits into a district ZIP - show the
     * row/district/file counts and require explicit confirmation before
     * dispatch, since a rejected/confirmed choice here means the job never
     * exists rather than leaving a reserved row behind an OOM.
     */
    public function downloadFundisPDF()
    {
        $distribution = $this->districtDistribution();
        $totalRows = (int) $distribution->sum('row_count');

        if ($totalRows === 0) {
            session()->flash('error', 'No fundis found to download.');

            return;
        }

        $cap = config('fundis_export.pdf_row_cap');

        if ($totalRows <= $cap) {
            $this->dispatchPdfExport();

            return;
        }

        $this->pdfExportRowCount = $totalRows;
        $this->pdfExportDistrictCount = $distribution->count();
        $this->pdfExportFileCount = (int) $distribution->sum(fn ($row) => (int) ceil($row->row_count / $cap));
        $this->pdfExportPendingConfirm = true;
    }

    public function confirmPdfExport()
    {
        $this->pdfExportPendingConfirm = false;
        $this->dispatchPdfExport();
    }

    public function cancelPdfExport()
    {
        $this->pdfExportPendingConfirm = false;
        $this->pdfExportRowCount = null;
        $this->pdfExportDistrictCount = null;
        $this->pdfExportFileCount = null;
    }

    protected function dispatchPdfExport(): void
    {
        $fundiIds = $this->getFundiQuery()->pluck('fundis.id')->toArray();

        $batchId = (string) Str::uuid();
        $this->pdfExportBatchId = $batchId;
        $this->pdfExportInProgress = true;
        $this->pdfDownloadUrl = null;
        $this->pdfExportFailedChunks = [];

        GenerateFundisPdfReport::dispatch($fundiIds, auth()->id(), $batchId);

        session()->flash('success', 'PDF report is being generated. This may take a few minutes.');
    }

    /**
     * Row count per district for the currently filtered set, via count
     * queries against the same filtered query getFundiQuery() already
     * builds - never hydrates fundi models to count them. Fundis with no
     * resolvable district are bucketed as "Unassigned" rather than dropped.
     */
    protected function districtDistribution(): \Illuminate\Support\Collection
    {
        return DB::query()
            ->fromSub($this->getFundiQuery()->reorder(), 'filtered_fundis')
            ->join('wards', 'filtered_fundis.ward_id', '=', 'wards.id')
            ->leftJoin('districts', 'wards.district_id', '=', 'districts.id')
            ->groupBy('districts.id', 'districts.name')
            ->orderByRaw('districts.name IS NULL, districts.name asc')
            ->get([
                'districts.id as district_id',
                DB::raw("COALESCE(districts.name, 'Unassigned') as district_name"),
                DB::raw('count(*) as row_count'),
            ]);
    }

    /**
     * Check if the PDF report is ready - either a single PDF (at/below the
     * row cap) or a chunked ZIP (above it). A ZIP result also checks for a
     * failed-chunks status file so partial success surfaces in the UI
     * instead of silently shipping an incomplete archive as if whole.
     */
    public function checkPdfExportStatus()
    {
        if (! $this->pdfExportBatchId || ! $this->pdfExportInProgress) {
            return;
        }

        $pdfFile = "downloads/fundis_report_{$this->pdfExportBatchId}.pdf";
        $zipFile = "downloads/fundis_report_{$this->pdfExportBatchId}.zip";

        if (Storage::disk('public')->exists($pdfFile)) {
            $this->pdfExportInProgress = false;
            $this->pdfDownloadUrl = Storage::url($pdfFile);

            session()->flash('success', 'PDF report ready! Click the button to download.');

            $this->dispatch('trigger-download', url: $this->pdfDownloadUrl);

            return;
        }

        if (Storage::disk('public')->exists($zipFile)) {
            $this->pdfExportInProgress = false;
            $this->pdfDownloadUrl = Storage::url($zipFile);

            $statusFile = "downloads/fundis_report_{$this->pdfExportBatchId}_status.json";
            if (Storage::disk('public')->exists($statusFile)) {
                $status = json_decode(Storage::disk('public')->get($statusFile), true);
                $this->pdfExportFailedChunks = $status['failed_chunks'] ?? [];
            }

            if (empty($this->pdfExportFailedChunks)) {
                session()->flash('success', 'PDF report ready! Click the button to download.');
            }
            // else: the blade renders a partial-success warning banner directly
            // from $pdfExportFailedChunks - a session flash set here would never
            // be shown, since displayFlashedAlerts() only runs from mount(), not
            // from this wire:poll-triggered method.

            $this->dispatch('trigger-download', url: $this->pdfDownloadUrl);
        }
    }

    public function resetPdfExport()
    {
        $this->pdfExportInProgress = false;
        $this->pdfExportBatchId = null;
        $this->pdfDownloadUrl = null;
        $this->pdfExportFailedChunks = [];
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
                'Approved At',
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
                            $fundi->fundi_ranking,
                            $fundi->approval_status ?? 'N/A',
                            $fundi->creator->full_name ?? 'N/A',
                            $fundi->approver->full_name ?? 'N/A',
                            $fundi->created_at->format('Y-m-d H:i:s'),
                            $fundi->approved_at ? $fundi->approved_at->format('Y-m-d H:i:s') : 'N/A',
                        ]);
                    }
                });

            fclose($handle);
        }, 'fundis_export_'.date('Y-m-d_H-i-s').'.csv', [
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
            'site_visit_min',
            'site_visit_max',
            'bags_min',
            'bags_max',
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
            'activeFilters' => $this->activeFilters(),
        ]);
    }
}

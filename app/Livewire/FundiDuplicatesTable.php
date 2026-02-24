<?php

namespace App\Livewire;

use App\Models\Fundi;
use App\Traits\HasAlerts;
use Livewire\Component;
use Livewire\WithPagination;
use App\Traits\LocationTrait;
use Illuminate\Support\Facades\DB;

class FundiDuplicatesTable extends Component
{
    use WithPagination, LocationTrait, HasAlerts;

    protected $paginationTheme = 'bootstrap';

    public $duplicateType = 'primary_phone';
    public $perPage = 20;
    public $selectedDuplicates = [];
    public $selectAll = false;
    public $showDetails = [];

    // Statistics
    public $stats = [
        'primary_phone' => 0,
        'transaction_phone' => 0,
        'name_ward' => 0,
        'nida_number' => 0,
        'total_duplicates' => 0,
    ];

    protected $listeners = [
        'executeDeleteDuplicates' => 'executeDeleteDuplicates',
        'executeDeleteSingle' => 'executeDeleteSingle',
        'executeKeepOldestDeleteRest' => 'executeKeepOldestDeleteRest',
    ];

    public function mount()
    {
        $this->loadStats();
        $this->displayFlashedAlerts();
    }

    /**
     * Load duplicate statistics
     */
    public function loadStats()
    {
        // Primary phone duplicates
        $this->stats['primary_phone'] = Fundi::select('primary_phone')
            ->whereNotNull('primary_phone')
            ->where('primary_phone', '!=', '')
            ->groupBy('primary_phone')
            ->havingRaw('COUNT(*) > 1')
            ->count();

        // Transaction phone duplicates
        $this->stats['transaction_phone'] = Fundi::select('transaction_phone')
            ->whereNotNull('transaction_phone')
            ->where('transaction_phone', '!=', '')
            ->groupBy('transaction_phone')
            ->havingRaw('COUNT(*) > 1')
            ->count();

        // Name + Ward duplicates
        $this->stats['name_ward'] = Fundi::select('fname', 'lname', 'ward_id')
            ->whereNotNull('fname')
            ->whereNotNull('lname')
            ->whereNotNull('ward_id')
            ->groupBy('fname', 'lname', 'ward_id')
            ->havingRaw('COUNT(*) > 1')
            ->count();

        // NIDA duplicates
        $this->stats['nida_number'] = Fundi::select('nida_number')
            ->whereNotNull('nida_number')
            ->where('nida_number', '!=', '')
            ->groupBy('nida_number')
            ->havingRaw('COUNT(*) > 1')
            ->count();

        $this->stats['total_duplicates'] = $this->stats['primary_phone'] 
            + $this->stats['transaction_phone'] 
            + $this->stats['name_ward'] 
            + $this->stats['nida_number'];
    }

    /**
     * Change duplicate type filter
     */
    public function updatedDuplicateType()
    {
        $this->resetPage();
        $this->selectedDuplicates = [];
        $this->selectAll = false;
        $this->showDetails = [];
    }

    /**
     * Toggle details for a duplicate group
     */
    public function toggleDetails($key)
    {
        if (in_array($key, $this->showDetails)) {
            $this->showDetails = array_diff($this->showDetails, [$key]);
        } else {
            $this->showDetails[] = $key;
        }
    }

    /**
     * Get duplicates based on selected type
     */
    public function getDuplicates()
    {
        switch ($this->duplicateType) {
            case 'primary_phone':
                return $this->getPrimaryPhoneDuplicates();
            case 'transaction_phone':
                return $this->getTransactionPhoneDuplicates();
            case 'name_ward':
                return $this->getNameWardDuplicates();
            case 'nida_number':
                return $this->getNidaDuplicates();
            default:
                return collect();
        }
    }

    /**
     * Get primary phone duplicates
     */
    private function getPrimaryPhoneDuplicates()
    {
        $duplicatePhones = Fundi::select('primary_phone', DB::raw('COUNT(*) as count'), DB::raw('MIN(id) as oldest_id'))
            ->whereNotNull('primary_phone')
            ->where('primary_phone', '!=', '')
            ->groupBy('primary_phone')
            ->havingRaw('COUNT(*) > 1')
            ->orderByDesc('count')
            ->paginate($this->perPage);

        // Load full details for each duplicate group
        $phones = $duplicatePhones->pluck('primary_phone')->toArray();
        
        $fundisGrouped = Fundi::whereIn('primary_phone', $phones)
            ->with(['ward.district.region', 'creator'])
            ->orderBy('created_at', 'asc')
            ->get()
            ->groupBy('primary_phone');

        // Attach fundis to each duplicate record
        $duplicatePhones->getCollection()->transform(function ($item) use ($fundisGrouped) {
            $item->fundis = $fundisGrouped[$item->primary_phone] ?? collect();
            $item->duplicate_key = $item->primary_phone;
            $item->duplicate_field = 'primary_phone';
            return $item;
        });

        return $duplicatePhones;
    }

    /**
     * Get transaction phone duplicates
     */
    private function getTransactionPhoneDuplicates()
    {
        $duplicatePhones = Fundi::select('transaction_phone', DB::raw('COUNT(*) as count'), DB::raw('MIN(id) as oldest_id'))
            ->whereNotNull('transaction_phone')
            ->where('transaction_phone', '!=', '')
            ->groupBy('transaction_phone')
            ->havingRaw('COUNT(*) > 1')
            ->orderByDesc('count')
            ->paginate($this->perPage);

        $phones = $duplicatePhones->pluck('transaction_phone')->toArray();
        
        $fundisGrouped = Fundi::whereIn('transaction_phone', $phones)
            ->with(['ward.district.region', 'creator'])
            ->orderBy('created_at', 'asc')
            ->get()
            ->groupBy('transaction_phone');

        $duplicatePhones->getCollection()->transform(function ($item) use ($fundisGrouped) {
            $item->fundis = $fundisGrouped[$item->transaction_phone] ?? collect();
            $item->duplicate_key = $item->transaction_phone;
            $item->duplicate_field = 'transaction_phone';
            return $item;
        });

        return $duplicatePhones;
    }

    /**
     * Get name + ward duplicates
     */
    private function getNameWardDuplicates()
    {
        $duplicates = Fundi::select(
                'fname', 
                'lname', 
                'ward_id', 
                DB::raw('COUNT(*) as count'), 
                DB::raw('MIN(id) as oldest_id'),
                DB::raw("CONCAT(fname, '|', lname, '|', ward_id) as composite_key")
            )
            ->whereNotNull('fname')
            ->whereNotNull('lname')
            ->whereNotNull('ward_id')
            ->groupBy('fname', 'lname', 'ward_id')
            ->havingRaw('COUNT(*) > 1')
            ->orderByDesc('count')
            ->paginate($this->perPage);

        // Get all fundis for these combinations
        $keys = $duplicates->map(function ($item) {
            return [
                'fname' => $item->fname,
                'lname' => $item->lname,
                'ward_id' => $item->ward_id,
            ];
        })->toArray();

        if (!empty($keys)) {
            $query = Fundi::with(['ward.district.region', 'creator']);
            
            $query->where(function ($q) use ($keys) {
                foreach ($keys as $key) {
                    $q->orWhere(function ($subQ) use ($key) {
                        $subQ->where('fname', $key['fname'])
                            ->where('lname', $key['lname'])
                            ->where('ward_id', $key['ward_id']);
                    });
                }
            });

            $allFundis = $query->orderBy('created_at', 'asc')->get();

            $fundisGrouped = $allFundis->groupBy(function ($fundi) {
                return $fundi->fname . '|' . $fundi->lname . '|' . $fundi->ward_id;
            });

            $duplicates->getCollection()->transform(function ($item) use ($fundisGrouped) {
                $key = $item->fname . '|' . $item->lname . '|' . $item->ward_id;
                $item->fundis = $fundisGrouped[$key] ?? collect();
                $item->duplicate_key = $key;
                $item->duplicate_field = 'name_ward';
                return $item;
            });
        }

        return $duplicates;
    }

    /**
     * Get NIDA duplicates
     */
    private function getNidaDuplicates()
    {
        $duplicates = Fundi::select('nida_number', DB::raw('COUNT(*) as count'), DB::raw('MIN(id) as oldest_id'))
            ->whereNotNull('nida_number')
            ->where('nida_number', '!=', '')
            ->groupBy('nida_number')
            ->havingRaw('COUNT(*) > 1')
            ->orderByDesc('count')
            ->paginate($this->perPage);

        $nidas = $duplicates->pluck('nida_number')->toArray();
        
        $fundisGrouped = Fundi::whereIn('nida_number', $nidas)
            ->with(['ward.district.region', 'creator'])
            ->orderBy('created_at', 'asc')
            ->get()
            ->groupBy('nida_number');

        $duplicates->getCollection()->transform(function ($item) use ($fundisGrouped) {
            $item->fundis = $fundisGrouped[$item->nida_number] ?? collect();
            $item->duplicate_key = $item->nida_number;
            $item->duplicate_field = 'nida_number';
            return $item;
        });

        return $duplicates;
    }

    /**
     * Delete a single fundi
     */
    public function deleteFundi($fundiId)
    {
        $this->showDeleteConfirmation(
            'executeDeleteSingle',
            ['fundiId' => $fundiId],
            'Delete Fundi?',
            'Are you sure you want to delete this fundi? This action cannot be undone.'
        );
    }

    /**
     * Execute single delete
     */
    public function executeDeleteSingle($fundiId)
    {
        $result = $this->handleOperation(
            function () use ($fundiId) {
                return Fundi::where('id', $fundiId)->delete();
            },
            'Deleted!',
            'Fundi deleted successfully',
            'Error',
            'Failed to delete fundi',
            true
        );

        if ($result) {
            $this->loadStats();
        }
    }

    /**
     * Keep oldest and delete rest for a duplicate group
     */
    public function keepOldestDeleteRest($duplicateKey, $duplicateField)
    {
        $this->showDeleteConfirmation(
            'executeKeepOldestDeleteRest',
            ['duplicateKey' => $duplicateKey, 'duplicateField' => $duplicateField],
            'Keep Oldest & Delete Duplicates?',
            'This will keep the oldest record and delete all other duplicates. This action cannot be undone.'
        );
    }

    /**
     * Execute keep oldest delete rest
     */
    public function executeKeepOldestDeleteRest($duplicateKey, $duplicateField)
    {
        $result = $this->handleOperation(
            function () use ($duplicateKey, $duplicateField) {
                $query = Fundi::query();

                switch ($duplicateField) {
                    case 'primary_phone':
                        $query->where('primary_phone', $duplicateKey);
                        break;
                    case 'transaction_phone':
                        $query->where('transaction_phone', $duplicateKey);
                        break;
                    case 'nida_number':
                        $query->where('nida_number', $duplicateKey);
                        break;
                    case 'name_ward':
                        $parts = explode('|', $duplicateKey);
                        if (count($parts) === 3) {
                            $query->where('fname', $parts[0])
                                ->where('lname', $parts[1])
                                ->where('ward_id', $parts[2]);
                        }
                        break;
                }

                // Get oldest ID
                $oldestId = $query->min('id');

                // Delete all except oldest
                return $query->where('id', '!=', $oldestId)->delete();
            },
            'Duplicates Removed!',
            'Kept oldest record and deleted duplicates',
            'Error',
            'Failed to remove duplicates',
            true
        );

        if ($result) {
            $this->loadStats();
        }
    }

    /**
     * Bulk delete all duplicates keeping oldest
     */
    public function bulkDeleteAllDuplicates()
    {
        $count = $this->stats[$this->duplicateType] ?? 0;
        
        if ($count === 0) {
            $this->showWarningAlert('No Duplicates', 'No duplicates found to delete.');
            return;
        }

        $this->showDeleteConfirmation(
            'executeDeleteDuplicates',
            [],
            'Delete All Duplicates?',
            "This will process {$count} duplicate groups, keeping the oldest record in each group and deleting the rest. This action cannot be undone."
        );
    }

    /**
     * Execute bulk delete duplicates
     */
    public function executeDeleteDuplicates()
    {
        $result = $this->handleOperation(
            function () {
                $deleted = 0;

                switch ($this->duplicateType) {
                    case 'primary_phone':
                        $deleted = $this->bulkDeleteByField('primary_phone');
                        break;
                    case 'transaction_phone':
                        $deleted = $this->bulkDeleteByField('transaction_phone');
                        break;
                    case 'nida_number':
                        $deleted = $this->bulkDeleteByField('nida_number');
                        break;
                    case 'name_ward':
                        $deleted = $this->bulkDeleteNameWardDuplicates();
                        break;
                }

                return $deleted;
            },
            'Duplicates Removed!',
            'Successfully removed duplicate records',
            'Error',
            'Failed to remove duplicates'
        );

        if ($result) {
            $this->loadStats();
        }
    }

    /**
     * Bulk delete by single field
     */
    private function bulkDeleteByField($field)
    {
        $duplicates = Fundi::select($field, DB::raw('MIN(id) as oldest_id'))
            ->whereNotNull($field)
            ->where($field, '!=', '')
            ->groupBy($field)
            ->havingRaw('COUNT(*) > 1')
            ->get();

        $deleted = 0;

        foreach ($duplicates as $dup) {
            $deleted += Fundi::where($field, $dup->$field)
                ->where('id', '!=', $dup->oldest_id)
                ->delete();
        }

        return $deleted;
    }

    /**
     * Bulk delete name + ward duplicates
     */
    private function bulkDeleteNameWardDuplicates()
    {
        $duplicates = Fundi::select('fname', 'lname', 'ward_id', DB::raw('MIN(id) as oldest_id'))
            ->whereNotNull('fname')
            ->whereNotNull('lname')
            ->whereNotNull('ward_id')
            ->groupBy('fname', 'lname', 'ward_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        $deleted = 0;

        foreach ($duplicates as $dup) {
            $deleted += Fundi::where('fname', $dup->fname)
                ->where('lname', $dup->lname)
                ->where('ward_id', $dup->ward_id)
                ->where('id', '!=', $dup->oldest_id)
                ->delete();
        }

        return $deleted;
    }

    /**
     * Refresh data
     */
    public function refresh()
    {
        $this->loadStats();
        $this->selectedDuplicates = [];
        $this->selectAll = false;
        $this->showDetails = [];
    }

    public function render()
    {
        return view('livewire.fundi-duplicates-table', [
            'duplicates' => $this->getDuplicates(),
        ]);
    }
}
<?php

namespace App\Livewire;

use App\Models\Fundi;
use App\Traits\HasAlerts;
use App\Traits\LocationTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class UnapprovedFundis extends Component
{
    use HasAlerts, LocationTrait, WithPagination;

    public $district;

    public $selectedFundis = [];

    public $selectAll = false;

    protected $paginationTheme = 'bootstrap';

    protected $listeners = [
        'refreshFundis' => '$refresh',
        'executeBulkDelete' => 'executeBulkDelete',
        'executeSingleDelete' => 'executeSingleDelete',
    ];

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedFundis = $this->fetch()->pluck('id')->toArray();
        } else {
            $this->selectedFundis = [];
        }
    }

    public function approve(int $fundi_id)
    {
        $fundi = Fundi::find($fundi_id);

        if (! $fundi) {
            $this->showErrorAlert('Error', 'Fundi not found');

            return;
        }

        // Check for duplicate
        if (Fundi::where([
            ['fname', $fundi->fname],
            ['lname', $fundi->lname],
            ['primary_phone', $fundi->primary_phone],
            ['ward_id', $fundi->ward_id],
        ])->where('id', '!=', $fundi_id)
            ->whereNotNull('approved_at')
            ->count() > 0
        ) {
            $this->showWarningAlert('Duplicate Found', 'Fundi already exists. Duplicate entry has been removed.');
            $fundi->delete();

            return;
        }

        // Use toast for quick feedback
        $result = $this->handleOperation(
            function () use ($fundi) {
                return $fundi->update([
                    'approved_at' => now(),
                    'approved_by' => Auth::id(),
                    'status' => 'active',
                ]);
            },
            'Approved!',
            'Fundi approved successfully',
            'Error',
            'Failed to approve fundi',
            true // Use toast
        );

        if ($result) {
            // Remove from selected array
            $this->selectedFundis = array_diff($this->selectedFundis, [$fundi_id]);
        }
    }

    public function reject(int $fundi_id)
    {
        $fundi = Fundi::find($fundi_id);

        if (! $fundi) {
            session()->flash('error', 'Fundi not found');

            return;
        }

        // Check for duplicate
        if (Fundi::where([
            ['primary_phone', $fundi->primary_phone],
            ['transaction_phone', $fundi->transaction_phone],
            ['ward_id', $fundi->ward_id],
        ])->where('id', '!=', $fundi_id)
            ->whereNotNull('approved_at')
            ->count() > 0
        ) {
            session()->flash('message', 'Fundi already exists');
            $fundi->delete();

            return;
        }

        $fundi->update([
            'status' => 'inactive',
        ]);

        session()->flash('message', 'Registration rejected');

        // addLog('edit', "Fundi [" . $fundi_id . "] approved");
    }

    public function bulkApprove()
    {
        if (empty($this->selectedFundis)) {
            $this->showWarningAlert('No Selection', 'Please select at least one fundi to approve');

            return;
        }

        $approved = 0;
        $duplicates = 0;

        DB::beginTransaction();

        try {
            foreach ($this->selectedFundis as $fundi_id) {
                $fundi = Fundi::find($fundi_id);

                if (! $fundi) {
                    continue;
                }

                // Check for duplicate
                $isDuplicate = Fundi::where([
                    ['fname', $fundi->fname],
                    ['lname', $fundi->lname],
                    ['primary_phone', $fundi->primary_phone],
                    ['ward_id', $fundi->ward_id],
                ])->where('id', '!=', $fundi_id)
                    ->whereNotNull('approved_at')
                    ->exists();

                if ($isDuplicate) {
                    $fundi->delete();
                    $duplicates++;

                    continue;
                }

                $fundi->update([
                    'approved_at' => now(),
                    'approved_by' => Auth::id(),
                    'status' => 'active',
                ]);

                $approved++;
            }

            DB::commit();

            // Clear selections
            $this->selectedFundis = [];
            $this->selectAll = false;

            $message = "Successfully approved {$approved} fundi(s)";
            if ($duplicates > 0) {
                $message .= " and removed {$duplicates} duplicate(s)";
            }

            $this->showSuccessAlert('Bulk Approval Complete', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            $this->showErrorAlert('Error', 'An error occurred during bulk approval');
        }
    }

    /**
     * Show delete confirmation for single fundi
     */
    public function delete($fundi_id)
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
        // Use toast for quick feedback
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
     * Show bulk delete confirmation
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

    public function mount()
    {
        $currentUser = currentUser();

        // Set default district based on user permissions
        if ($currentUser && ! ($currentUser->role->name === 'admin' || $currentUser->role->name === 'root')) {
            $this->district = $currentUser->primary_district->id ?? null;
        }

        // Display any flashed alerts
        $this->displayFlashedAlerts();
    }

    public function fetch()
    {
        $query = Fundi::with(['ward.district', 'creator'])
            ->whereNull('approved_at');

        // Apply district filter if set
        if ($this->district) {
            $query->whereHas('ward', function ($q) {
                $q->whereHas('district', function ($sq) {
                    $sq->where('id', $this->district);
                });
            });
        }

        return $query->latest('created_at')->paginate(10);
    }

    public function render()
    {
        return view('livewire.unapproved-fundis', [
            'fundis' => $this->fetch(),
        ]);
    }
}

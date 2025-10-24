<?php

namespace App\Livewire;

use App\Models\BudgetAllocation;
use App\Models\DisbursementBatch;
use App\Models\Fundi;
use App\Traits\HasAlerts;
use Livewire\Component;
use Exception;
use Illuminate\Support\Facades\Auth;

class DisbursementForm extends Component
{
    use HasAlerts;

    // Form properties
    public $budget_allocation_id = '';
    public $title = '';
    public $description = '';
    public $disbursement_date = '';
    public $recipients = [];
    public $selected_fundis = [];

    // UI state
    public $saving = false;
    public $step = 1; // 1: Basic Info, 2: Select Fundis, 3: Review
    public $total_amount = 0;

    // Data
    public $budget_allocations = [];
    public $available_fundis = [];
    public $search_fundi = '';

    protected $listeners = [
        'createNewDisbursement' => 'resetForm',
    ];

    public function mount()
    {
        $this->disbursement_date = now()->format('Y-m-d');
        $this->loadBudgetAllocations();
        $this->loadAvailableFundis();

        // Display any flashed alerts
        $this->displayFlashedAlerts();
    }

    public function loadBudgetAllocations()
    {
        $this->budget_allocations = BudgetAllocation::where('status', 'active')
            ->where('remaining_amount', '>', 0)
            ->get();
    }

    public function loadAvailableFundis()
    {
        $query = Fundi::where('status', 'active')
            ->whereNotNull('primary_phone');

        if ($this->search_fundi) {
            $query->where(function ($q) {
                $q->whereRaw("concat(fname, ' ', lname) like ?", ['%' . $this->search_fundi . '%'])
                    ->orWhere('primary_phone', 'like', '%' . $this->search_fundi . '%');
            });
        }

        $this->available_fundis = $query->limit(50)->get();
    }

    public function updatedSearchFundi()
    {
        $this->loadAvailableFundis();
    }

    public function nextStep()
    {
        if ($this->step == 1) {
            $this->validateStep1();
            $this->step = 2;
        } elseif ($this->step == 2) {
            if (empty($this->selected_fundis)) {
                $this->showErrorAlert('Error', 'Please select at least one fundi.');
                return;
            }
            $this->calculateTotalAmount();
            $this->step = 3;
        }
    }

    public function previousStep()
    {
        if ($this->step > 1) {
            $this->step--;
        }
    }

    public function validateStep1()
    {
        $this->validate([
            'budget_allocation_id' => 'required|exists:budget_allocations,id',
            'title' => 'required|string|max:255',
            'disbursement_date' => 'required|date|after_or_equal:today',
        ]);
    }

    public function toggleFundi($fundiId)
    {
        if (isset($this->selected_fundis[$fundiId])) {
            unset($this->selected_fundis[$fundiId]);
        } else {
            $fundi = Fundi::find($fundiId);
            $this->selected_fundis[$fundiId] = [
                'fundi_id' => $fundiId,
                'name' => $fundi->full_name,
                'phone' => $fundi->primary_phone,
                'amount' => 50000, // Default amount
            ];
        }
    }

    public function updateAmount($fundiId, $amount)
    {
        if (isset($this->selected_fundis[$fundiId])) {
            $this->selected_fundis[$fundiId]['amount'] = (float) $amount;
        }
        $this->calculateTotalAmount();
    }

    public function calculateTotalAmount()
    {
        $this->total_amount = array_sum(array_column($this->selected_fundis, 'amount'));
    }

    public function bulkSetAmount($amount)
    {
        foreach ($this->selected_fundis as $key => $fundi) {
            $this->selected_fundis[$key]['amount'] = (float) $amount;
        }
        $this->calculateTotalAmount();
    }

    public function createDisbursement()
    {
        $this->saving = true;

        // Validate final form
        try {
            $this->validate([
                'budget_allocation_id' => 'required|exists:budget_allocations,id',
                'title' => 'required|string|max:255',
                'disbursement_date' => 'required|date|after_or_equal:today',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->saving = false;
            $this->showErrorAlert('Validation Error', 'Please check the form fields and try again.');
            throw $e;
        }

        if (empty($this->selected_fundis)) {
            $this->saving = false;
            $this->showErrorAlert('Error', 'Please select at least one fundi.');
            return;
        }

        // Show loading alert
        $this->showLoadingAlert('Creating Disbursement', 'Please wait while we process the disbursement...');

        $result = $this->handleOperation(
            function () {
                // Prepare recipients data
                $recipients = array_map(function ($fundi) {
                    return [
                        'fundi_id' => $fundi['fundi_id'],
                        'amount' => $fundi['amount'],
                    ];
                }, $this->selected_fundis);

                // Use the Financial Management Service
                $financialService = app(\App\Services\Financial\FinancialManagementService::class);

                $batch = $financialService->processDisbursementBatch(
                    $this->budget_allocation_id,
                    [
                        'title' => $this->title,
                        'description' => $this->description,
                        'disbursement_date' => $this->disbursement_date,
                        'total_amount' => $this->total_amount,
                        'recipients' => $recipients,
                    ]
                );

                $this->resetForm();
                $this->dispatch('disbursementCreated', $batch->id);

                return true;
            },
            'Success',
            'Disbursement batch created successfully!',
            'Error',
            'Failed to create disbursement batch. Please try again.',
            false // Use alert for important actions
        );

        $this->saving = false;
    }

    public function resetForm()
    {
        $this->reset([
            'budget_allocation_id',
            'title',
            'description',
            'selected_fundis',
            'total_amount',
            'saving',
            'search_fundi'
        ]);
        $this->step = 1;
        $this->disbursement_date = now()->format('Y-m-d');
        $this->loadBudgetAllocations();
        $this->loadAvailableFundis();
    }

    public function render()
    {
        return view('livewire.disbursement-form');
    }
}

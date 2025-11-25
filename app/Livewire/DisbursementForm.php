<?php

namespace App\Livewire;

use App\Models\BudgetAllocation;
use App\Models\DisbursementBatch;
use App\Models\Fundi;
use App\Services\Financial\FinancialManagementService;
use App\Services\Payment\SelcomService;
use App\Traits\HasAlerts;
use Livewire\Component;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DisbursementForm extends Component
{
    use HasAlerts;

    // Form properties
    public $budget_allocation_id = '';
    public $title = '';
    public $description = '';
    public $disbursement_date = '';
    public $selected_fundis = [];

    // UI state
    public $saving = false;
    public $checking_balance = false;
    public $step = 1;
    public $total_amount = 0;
    public $provider_balance = null;
    public $balance_check_error = null;

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

            // Check provider balance before review
            $this->checkProviderBalance();

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
            if ($fundi) {
                $this->selected_fundis[$fundiId] = [
                    'fundi_id' => $fundiId,
                'name' => $fundi->full_name,
                    'phone' => $fundi->primary_phone,
                    'amount' => 50000,
                ];
            }
        }
        $this->calculateTotalAmount();
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

    /**
     * Check SELCOM provider balance
     */
    public function checkProviderBalance()
    {
        $this->checking_balance = true;
        $this->balance_check_error = null;

        try {
            $selcomService = app(SelcomService::class);
            $result = $selcomService->checkBalance();

            if ($result['success']) {
                $this->provider_balance = $result['balance'];

                Log::info('Provider balance checked', [
                    'balance' => $this->provider_balance,
                    'required' => $this->total_amount,
                ]);

                if ($this->provider_balance < $this->total_amount) {
                    $this->balance_check_error = sprintf(
                        'Insufficient provider balance. Available: %s TZS, Required: %s TZS',
                        number_format($this->provider_balance),
                        number_format($this->total_amount)
                    );
                }
            } else {
                $this->balance_check_error = 'Unable to verify provider balance. Please try again.';
                Log::error('Balance check failed', ['error' => $result['error'] ?? 'Unknown']);
            }

        } catch (Exception $e) {
            $this->balance_check_error = 'Balance check error: ' . $e->getMessage();
            Log::error('Balance check exception', ['error' => $e->getMessage()]);
        } finally {
            $this->checking_balance = false;
        }
    }

    public function createDisbursement()
    {
        $this->saving = true;

        // Final validation
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

        // Check if balance error exists
        if ($this->balance_check_error) {
            $this->saving = false;
            $this->showErrorAlert('Balance Error', $this->balance_check_error);
            return;
        }

        $this->showLoadingAlert('Creating Disbursement', 'Please wait while we process the disbursement batch...');

        $result = $this->handleOperation(
            function () {
                // Prepare recipients data
                $recipients = array_map(function ($fundi) {
                    return [
                        'fundi_id' => $fundi['fundi_id'],
                        'amount' => $fundi['amount'],
                    ];
                }, $this->selected_fundis);

                // Process through Financial Management Service
                $financialService = app(FinancialManagementService::class);

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

                Log::info('Disbursement batch created via Livewire', [
                    'batch_id' => $batch->id,
                    'created_by' => Auth::id(),
                ]);

                $this->resetForm();
                $this->dispatch('disbursementCreated', $batch->id);

                return true;
            },
            'Success',
            'Disbursement batch created successfully! Processing will continue in the background.',
            'Error',
            'Failed to create disbursement batch. Please try again.',
            false
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
            'search_fundi',
            'provider_balance',
            'balance_check_error',
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

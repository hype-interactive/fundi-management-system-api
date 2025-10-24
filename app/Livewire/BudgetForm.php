<?php

namespace App\Livewire;

use Exception;
use Livewire\Component;
use App\Traits\HasAlerts;
use App\Models\BudgetAllocation;
use Illuminate\Support\Facades\Auth;

class BudgetForm extends Component
{
    use HasAlerts;

    // Form properties
    public $budget_id = null;
    public $title = '';
    public $description = '';
    public $total_amount = '';
    public $status = 'active';

    // UI state
    public $action_text = 'Create';
    public $editing = false;
    public $saving = false;

    protected $listeners = [
        'editBudget' => 'loadBudget',
        'createNewBudget' => 'resetForm',
    ];

    public function mount($budget = null)
    {
        if ($budget) {
            $this->loadBudgetData($budget);
        } else {
            $this->resetForm();
        }

        // Display any flashed alerts
        $this->displayFlashedAlerts();
    }

    /**
     * Load budget data for editing
     */
    public function loadBudget($budget)
    {
        try {
            $budgetModel = is_array($budget)
                ? BudgetAllocation::find($budget['id'])
                : $budget;

            if ($budgetModel) {
                $this->loadBudgetData($budgetModel);
            }
        } catch (Exception $e) {
            $this->showErrorAlert('Error', 'Failed to load budget data');
        }
    }

    /**
     * Load budget data into form
     */
    public function loadBudgetData($budget)
    {
        $this->budget_id = $budget->id;
        $this->title = $budget->title;
        $this->description = $budget->description;
        $this->total_amount = $budget->total_amount;
        $this->status = $budget->status;
        $this->action_text = 'Update';
        $this->editing = true;
    }

    /**
     * Reset form to default state
     */
    public function resetForm()
    {
        $this->reset([
            'budget_id',
            'title',
            'description',
            'total_amount',
            'editing',
            'saving'
        ]);
        $this->status = 'active';
        $this->action_text = 'Create';
    }

    /**
     * Create or update budget allocation
     */
    public function createOrUpdateBudget()
    {
        $this->saving = true;

        $rules = [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'total_amount' => 'required|numeric|min:1000|max:1000000000',
            'status' => 'required|in:active,cancelled',
        ];

        try {
            $this->validate($rules);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->saving = false;
            $this->showErrorAlert('Validation Error', 'Please check the form fields and try again.');
            throw $e;
        }

        $isCreating = empty($this->budget_id);

        // Show loading indicator
        if ($isCreating) {
            $this->showLoadingAlert('Creating Budget', 'Please wait while we create the budget allocation...');
        } else {
            $this->showLoadingAlert('Updating Budget', 'Please wait while we update the budget allocation...');
        }

        $result = $this->handleOperation(
            function () use ($isCreating) {
                $budgetData = [
                    'title' => $this->title,
                    'description' => $this->description,
                    'total_amount' => $this->total_amount,
                    'status' => $this->status,
                ];

                if ($isCreating) {
                    $budgetData['remaining_amount'] = $this->total_amount;
                    $budgetData['created_by'] = Auth::id();
                }

                $budget = BudgetAllocation::updateOrCreate(
                    ['id' => $this->budget_id],
                    $budgetData
                );

                if ($budget) {
                    if ($isCreating) {
                        $this->resetForm();
                        $this->dispatch('budgetCreated', $budget->id);
                    } else {
                        $this->dispatch('budgetUpdated', $budget->id);
                    }
                    return true;
                }

                return false;
            },
            $isCreating ? 'Created!' : 'Updated!',
            'Budget allocation ' . ($isCreating ? 'created' : 'updated') . ' successfully',
            'Error',
            'Failed to ' . ($isCreating ? 'create' : 'update') . ' budget allocation',
            false // Use alert for important actions
        );

        $this->saving = false;
        return $result;
    }

    /**
     * Cancel editing and reset form
     */
    public function cancelEdit()
    {
        $this->resetForm();
        $this->showInfoAlert('Cancelled', 'Form has been reset');
    }

    /**
     * Validate specific field on blur
     */
    public function validateField($field)
    {
        $rules = [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'total_amount' => 'required|numeric|min:1000|max:1000000000',
            'status' => 'required|in:active,cancelled',
        ];

        if (isset($rules[$field])) {
            $this->validateOnly($field, [$field => $rules[$field]]);
        }
    }

    public function render()
    {
        return view('livewire.budget-form');
    }
}

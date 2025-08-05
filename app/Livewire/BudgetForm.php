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
    }

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

        try {
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

            $this->saving = false;

            if ($budget) {
                $message = $isCreating ? 'Budget allocation created successfully!' : 'Budget allocation updated successfully!';
                $this->showSuccessAlert('Success', $message);

                if ($isCreating) {
                    $this->resetForm();
                    $this->dispatch('budgetCreated', $budget->id);
                } else {
                    $this->dispatch('budgetUpdated', $budget->id);
                }

                return true;
            }

        } catch (Exception $e) {
            $this->saving = false;
            $this->showErrorAlert('Error', 'Failed to save budget allocation. Please try again.');
            return false;
        }
    }

    public function render()
    {
        return view('livewire.budget-form');
    }
}

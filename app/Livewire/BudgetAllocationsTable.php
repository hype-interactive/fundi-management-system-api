<?php

namespace App\Livewire;

use App\Models\BudgetAllocation;
use App\Traits\HasAlerts;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class BudgetAllocationsTable extends Component
{
    use HasAlerts, WithPagination;

    protected $paginationTheme = 'bootstrap';

    #[Url]
    public $search_key = '';

    public $perPage = 10;

    #[Url]
    public $status = '';

    #[Url]
    public $created_by = '';

    #[Url]
    public $date_from = '';

    #[Url]
    public $date_to = '';

    // UI state
    public $showMobileFilters = false;

    public $compactView = false;

    // Modal states
    public $showCreateModal = false;

    public $showEditModal = false;

    // Budget being edited
    public $editingBudget = null;

    // Listeners for budget form events
    protected $listeners = [
        'budgetCreated' => 'handleBudgetCreated',
        'budgetUpdated' => 'handleBudgetUpdated',
        'executeDeleteBudget' => 'executeDeleteBudget',
    ];

    public function mount()
    {
        $this->displayFlashedAlerts();
    }

    public function updatedSearchKey()
    {
        $this->resetPage();
    }

    public function updatedStatus()
    {
        $this->resetPage();
    }

    public function updatedCreatedBy()
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

    /**
     * Open create modal
     */
    public function openCreateModal()
    {
        $this->editingBudget = null;
        $this->showCreateModal = true;
        $this->dispatch('createNewBudget');
    }

    /**
     * Open edit modal
     */
    public function editBudget($budgetId)
    {
        $budget = BudgetAllocation::find($budgetId);

        if ($budget) {
            $this->editingBudget = $budget;
            $this->showEditModal = true;
            $this->dispatch('editBudget', budget: $budget->toArray());
        }
    }

    /**
     * Close create modal
     */
    public function closeCreateModal()
    {
        $this->showCreateModal = false;
        $this->editingBudget = null;
    }

    /**
     * Close edit modal
     */
    public function closeEditModal()
    {
        $this->showEditModal = false;
        $this->editingBudget = null;
    }

    /**
     * Delete budget - Show confirmation
     */
    public function deleteBudget($budgetId)
    {
        $budget = BudgetAllocation::find($budgetId);

        if (! $budget) {
            $this->showErrorAlert('Error', 'Budget allocation not found');

            return;
        }

        // Check if budget has disbursements
        if ($budget->disbursementBatches->count() > 0) {
            $this->showWarningAlert(
                'Cannot Delete',
                'Cannot delete budget allocation with existing disbursements. Please remove disbursements first.'
            );

            return;
        }

        $this->showDeleteConfirmation(
            'executeDeleteBudget',
            ['budgetId' => $budgetId],
            'Delete Budget Allocation?',
            "Are you sure you want to delete '{$budget->title}'? This action cannot be undone."
        );
    }

    /**
     * Execute delete - Called after confirmation
     */
    public function executeDeleteBudget($budgetId)
    {
        $result = $this->handleOperation(
            function () use ($budgetId) {
                $budget = BudgetAllocation::find($budgetId);

                if (! $budget) {
                    return false;
                }

                // Double-check for disbursements before deletion
                if ($budget->disbursementBatches->count() > 0) {
                    return false;
                }

                return $budget->delete();
            },
            'Deleted!',
            'Budget allocation deleted successfully',
            'Error',
            'Failed to delete budget allocation',
            true // Use toast
        );
    }

    /**
     * Handle budget created event
     */
    public function handleBudgetCreated($budgetId)
    {
        $this->closeCreateModal();
        $this->showSuccessToast('Created!', 'Budget allocation created successfully');
    }

    /**
     * Handle budget updated event
     */
    public function handleBudgetUpdated($budgetId)
    {
        $this->closeEditModal();
        $this->showSuccessToast('Updated!', 'Budget allocation updated successfully');
    }

    public function getBudgetQuery()
    {
        $query = BudgetAllocation::with(['creator', 'disbursementBatches']);

        return $query
            ->when($this->search_key, function ($query, $search_key) {
                $query->where('title', 'like', '%'.$search_key.'%')
                    ->orWhere('description', 'like', '%'.$search_key.'%');
            })
            ->when($this->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($this->created_by, function ($query, $created_by) {
                $query->where('created_by', $created_by);
            })
            ->when($this->date_from, function ($query, $date_from) {
                $query->whereDate('created_at', '>=', $date_from);
            })
            ->when($this->date_to, function ($query, $date_to) {
                $query->whereDate('created_at', '<=', $date_to);
            })
            ->latest('created_at');
    }

    public function fetch()
    {
        return $this->getBudgetQuery()->paginate($this->perPage);
    }

    public function toggleCompactView()
    {
        $this->compactView = ! $this->compactView;
    }

    public function exportToExcel()
    {
        return response()->streamDownload(function () {
            $budgets = $this->getBudgetQuery()->get();

            $headers = [
                'Title',
                'Description',
                'Total Amount (TZS)',
                'Remaining Amount (TZS)',
                'Utilized Amount (TZS)',
                'Utilization Rate (%)',
                'Status',
                'Created By',
                'Created At',
                'Disbursements Count',
            ];

            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);

            foreach ($budgets as $budget) {
                $utilizedAmount = $budget->total_amount - $budget->remaining_amount;
                $utilizationRate = $budget->total_amount > 0
                    ? round(($utilizedAmount / $budget->total_amount) * 100, 2)
                    : 0;

                fputcsv($handle, [
                    $budget->title,
                    $budget->description,
                    number_format($budget->total_amount, 2),
                    number_format($budget->remaining_amount, 2),
                    number_format($utilizedAmount, 2),
                    $utilizationRate,
                    ucfirst($budget->status),
                    $budget->creator->full_name ?? 'N/A',
                    $budget->created_at->format('Y-m-d H:i:s'),
                    $budget->disbursementBatches->count(),
                ]);
            }

            fclose($handle);
        }, 'budget_allocations_'.date('Y-m-d_H-i-s').'.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function refresh()
    {
        $this->reset([
            'search_key',
            'status',
            'created_by',
            'date_from',
            'date_to',
        ]);
        $this->showMobileFilters = false;
    }

    public function render()
    {
        return view('livewire.budget-allocations-table', [
            'budgets' => $this->fetch(),
            'creators' => $this->getCreators(),
            'statusOptions' => $this->getStatusOptions(),
        ]);
    }

    private function getCreators()
    {
        return \App\Models\User::whereIn('id', function ($query) {
            $query->select('created_by')
                ->from('budget_allocations')
                ->whereNotNull('created_by')
                ->distinct();
        })->get();
    }

    private function getStatusOptions()
    {
        return [
            'active' => 'Active',
            'cancelled' => 'Cancelled',
            'completed' => 'Completed',
        ];
    }
}

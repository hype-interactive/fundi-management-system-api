<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Attributes\On;

class OperatorProfile extends Component
{
    public $operator;
    public $siteVisits;
    public $incentives;
    public $districts;
    public $recentActivity = [];

    public function mount(User $operator)
    {
        $this->operator = $operator;
        $this->loadSiteVisits();
        $this->loadIncentives();
        $this->loadDistricts();
        $this->loadRecentActivity();
    }

    public function loadSiteVisits()
    {
        // Get the latest 3 site visits for the sidebar
        $this->siteVisits = $this->operator->siteVisits()
            ->with(['approver'])
            ->latest()
            ->take(3)
            ->get();
    }

    public function loadIncentives()
    {
        // Get the latest 3 incentives for the sidebar
        $this->incentives = $this->operator->incentives()
            ->with(['approver', 'siteVisit'])
            ->latest()
            ->take(3)
            ->get();
    }

    public function loadDistricts()
    {
        // Load assigned districts
        $this->districts = $this->operator->districts;
    }

    public function loadRecentActivity()
    {
        // Get recent activity logs
        $this->recentActivity = $this->operator->activityLogs()
            ->latest()
            ->take(5)
            ->get();
    }

    public function confirmActivate()
    {
        $this->dispatch('showConfirmation', [
            'title' => 'Activate Operator',
            'message' => 'Are you sure you want to activate this operator?',
            'type' => 'activate',
            'itemName' => $this->operator->full_name,
            'itemDetails' => [
                'Email' => $this->operator->email ?? 'Not specified',
                'Phone' => $this->operator->phone ?? 'Not specified',
                'Role' => $this->operator->role?->name ?? 'Not specified',
                'Status' => $this->operator->status ?? 'Not specified',
            ],
            'confirmMethod' => 'activateOperator',
            'confirmParams' => []
        ]);
    }

    public function confirmDeactivate()
    {
        $this->dispatch('showConfirmation', [
            'title' => 'Deactivate Operator',
            'message' => 'Please provide a reason for deactivating this operator.',
            'type' => 'deactivate',
            'itemName' => $this->operator->full_name,
            'itemDetails' => [
                'Email' => $this->operator->email ?? 'Not specified',
                'Phone' => $this->operator->phone ?? 'Not specified',
                'Role' => $this->operator->role?->name ?? 'Not specified',
                'Status' => $this->operator->status ?? 'Not specified',
            ],
            'confirmMethod' => 'deactivateOperator',
            'confirmParams' => []
        ]);
    }

    #[On('activateOperator')]
    public function activateOperator()
    {
        if ($this->operator->status === 'active') {
            session()->flash('message', 'Operator is already active.');
            return;
        }

        $this->operator->update([
            'status' => 'active',
        ]);

        $this->dispatch('operatorStatusChanged');
        session()->flash('message', 'Operator activated successfully.');
    }

    #[On('deactivateOperator')]
    public function deactivateOperator($reason = null)
    {
        $this->operator->update([
            'status' => 'inactive',
        ]);

        // Log the deactivation reason in activity logs if needed
        if ($reason) {
            $this->operator->activityLogs()->create([
                'action' => 'deactivated',
                'description' => 'Operator deactivated. Reason: ' . $reason,
                'performed_by' => Auth::id(),
            ]);
        }

        $this->dispatch('operatorStatusChanged');
        session()->flash('message', 'Operator deactivated successfully.');
    }

    public function getTotalIncentivesAttribute()
    {
        return $this->operator->incentives()->where('status', 'paid')->sum('amount');
    }

    public function getTotalSiteVisitsAttribute()
    {
        return $this->operator->siteVisits()->count();
    }

    public function getCompletedSiteVisitsAttribute()
    {
        return $this->operator->siteVisits()->where('status', 'completed')->count();
    }

    public function render()
    {
        return view('livewire.operator-profile');
    }
}

<?php

namespace App\Livewire;

use App\Models\Fundi;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Attributes\On;

class FundiProfile extends Component
{
    public $fundi;
    public $transactions;
    public $siteVisits;
    public $legacyMatch = false;
    public $legacyData = null;

    public function mount(Fundi $fundi)
    {
        $this->fundi = $fundi;
        $this->loadTransactions();
        $this->loadSiteVisits();
        $this->checkLegacyMatch();
    }

    public function loadTransactions()
    {
        // Get the latest 3 transactions for the sidebar
        $this->transactions = $this->fundi->transactions()
            ->latest()
            ->take(3)
            ->get();
    }

    public function loadSiteVisits()
    {
        // Get the latest 3 site visits for the sidebar
        $this->siteVisits = $this->fundi->siteVisits()
            ->latest()
            ->take(3)
            ->get();
    }

    public function checkLegacyMatch()
    {
        if ($this->fundi->legacy_match) {
            $this->legacyMatch = true;
            // In a real application, you would fetch legacy data here
            // For now we'll just simulate it
            $this->legacyData = [
                'legacy_id' => $this->fundi->legacy_id,
                'registration_date' => '2021-05-15', // Example date
            ];

            // Dispatch browser event to notify about legacy match
            $this->dispatch('fundiLegacyMatch');
        }
    }

    public function confirmApprove()
    {
        $this->dispatch('showConfirmation', [
            'title' => 'Approve Fundi',
            'message' => 'Are you sure you want to approve this fundi?',
            'type' => 'approve',
            'itemName' => $this->fundi->full_name,
            'itemDetails' => [
                'NIDA Number' => $this->fundi->nida_number ?? 'Not specified',
                'Primary Phone' => $this->fundi->primary_phone ?? 'Not specified',
                'Ward' => $this->fundi->ward?->name ?? 'Not specified',
                'Ranking' => $this->fundi->ranking ?? 'Not specified',
            ],
            'confirmMethod' => 'approveFundi',
            'confirmParams' => []
        ]);
    }

    public function confirmReject()
    {
        $this->dispatch('showConfirmation', [
            'title' => 'Reject Fundi',
            'message' => 'Please provide a reason for rejecting this fundi.',
            'type' => 'reject',
            'itemName' => $this->fundi->full_name,
            'itemDetails' => [
                'NIDA Number' => $this->fundi->nida_number ?? 'Not specified',
                'Primary Phone' => $this->fundi->primary_phone ?? 'Not specified',
                'Ward' => $this->fundi->ward?->name ?? 'Not specified',
                'Ranking' => $this->fundi->ranking ?? 'Not specified',
            ],
            'confirmMethod' => 'rejectFundi',
            'confirmParams' => []
        ]);
    }

    #[On('approveFundi')]
    public function approveFundi()
    {
        if ($this->fundi->approved_at) {
            session()->flash('message', 'Fundi has already been approved.');
            return;
        }

        $this->fundi->update([
            'status' => 'active',
            'approved_at' => now(),
            'approved_by' => Auth::id(),
        ]);

        $this->dispatch('fundiStatusChanged');
        session()->flash('message', 'Fundi approved successfully.');
    }

    #[On('rejectFundi')]
    public function rejectFundi($reason = null)
    {
        if ($this->fundi->approved_at) {
            session()->flash('message', 'Fundi cannot be rejected at this stage.');
            return;
        }

        $this->fundi->update([
            'status' => 'inactive',
            'rejection_reason' => $reason,
            'rejected_at' => now(),
            'rejected_by' => Auth::id(),
        ]);

        $this->dispatch('fundiStatusChanged');
        session()->flash('message', 'Fundi rejected successfully.');
    }

    public function render()
    {
        return view('livewire.fundi-profile');
    }
}

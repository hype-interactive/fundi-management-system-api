<?php

namespace App\Livewire\SiteVisit;

use Livewire\Component;
use App\Models\SiteVisit;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Auth;

class Sidebar extends Component
{
    public SiteVisit $siteVisit;

    protected $listeners = ['visitStatusChanged' => '$refresh'];

    public function mount(SiteVisit $siteVisit)
    {
        $this->siteVisit = $siteVisit;
    }

    public function confirmApprove()
    {
        $this->dispatch('showConfirmation', [
            'title' => 'Approve Site Visit',
            'message' => 'Are you sure you want to approve this site visit?',
            'type' => 'approve',
            'itemName' => "Site Visit #{$this->siteVisit->id}",
            'itemDetails' => [
                // 'Location' => $this->siteVisit->location ?? 'Not specified',
                'Created by' => $this->siteVisit->operator?->full_name ?? 'Unknown',
                'Date' => $this->siteVisit->created_at?->format('M d, Y g:i A'),
                'Photos' => $this->siteVisit->photos?->count(),
                'Products' => $this->siteVisit->products?->count(),
            ],
            'confirmMethod' => 'approveVisit',
            'confirmParams' => []
        ]);
    }

    public function confirmReject()
    {
        $this->dispatch('showConfirmation', [
            'title' => 'Reject Site Visit',
            'message' => 'Please provide a reason for rejecting this site visit.',
            'type' => 'reject',
            'itemName' => "Site Visit #{$this->siteVisit->id}",
            'itemDetails' => [
                // 'Location' => $this->siteVisit->location ?? 'Not specified',
                'Created by' => $this->siteVisit->operator?->full_name ?? 'Unknown',
                'Date' => $this->siteVisit->created_at?->format('M d, Y g:i A'),
            ],
            'confirmMethod' => 'rejectVisit',
            'confirmParams' => []
        ]);
    }

    #[On('approveVisit')]
    public function approveVisit()
    {
        if ($this->siteVisit->status !== 'pending') {
            return;
        }

        $this->siteVisit->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => Auth::id(),
        ]);

        $this->dispatch('visitStatusChanged');
        session()->flash('message', 'Site visit approved successfully.');
    }

    #[On('rejectVisit')]
    public function rejectVisit($reason = null)
    {
        if ($this->siteVisit->status !== 'pending') {
            return;
        }

        $this->siteVisit->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
        ]);

        $this->dispatch('visitStatusChanged');
        session()->flash('message', 'Site visit rejected.');
    }

    public function exportVisit()
    {
        dd("Exporting visit data...");
        // Logic for PDF export
        // return redirect()->route('site-visit.export', $this->siteVisit);
    }

    public function render()
    {
        return view('livewire.site-visit.sidebar');
    }
}

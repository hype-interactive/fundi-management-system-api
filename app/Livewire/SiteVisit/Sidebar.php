<?php

namespace App\Livewire\SiteVisit;

use Livewire\Component;
use App\Models\SiteVisit;
use Illuminate\Support\Facades\Auth;

class Sidebar extends Component
{
    public SiteVisit $siteVisit;

    protected $listeners = ['visitStatusChanged' => '$refresh'];

    public function mount(SiteVisit $siteVisit)
    {
        $this->siteVisit = $siteVisit;
    }

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

    public function rejectVisit($reason)
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

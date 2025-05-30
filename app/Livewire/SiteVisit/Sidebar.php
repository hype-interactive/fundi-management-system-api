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
        try {
            set_time_limit(300); // Set execution time to 5min
            ini_set("memory_limit", "1000M");

            return response()->streamDownload(function () {
                // Load the site visit with all necessary relationships
                $siteVisit = SiteVisit::with([
                    'operator',
                    'ward.district.region',
                    'products',
                    'fundis',
                    'photos',
                    'approver'
                ])->find($this->siteVisit->id);

                // Prepare data for the PDF template
                $title = "Site Visit Report - {$siteVisit->site_name}";
                $date = now()->format('D, M d, Y');

                // Generate the HTML view using the site visit report template
                $html_view = view('documents.site-visit-report', compact('siteVisit', 'title', 'date'))->render();

                // Create PDF instance
                $pdf = app('dompdf.wrapper');
                $pdf->loadHTML($html_view);

                // Set PDF orientation and paper size
                $pdf->setPaper('A4', 'portrait');

                // Stream the PDF
                echo $pdf->stream();
            }, "site_visit_report_{$this->siteVisit->id}_{$this->siteVisit->site_name}.pdf");
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to export site visit report: ' . $e->getMessage());
            return;
        }
    }

    public function render()
    {
        return view('livewire.site-visit.sidebar');
    }
}

<?php

namespace App\Livewire\SiteVisit;

use Livewire\Component;
use App\Models\SiteVisit;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class Sidebar extends Component
{
    public SiteVisit $siteVisit;

    public $isExporting = false;

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

    public function confirmDelete()
    {
        $this->dispatch('showConfirmation', [
            'title' => 'Delete Site Visit',
            'message' => 'Are you sure you want to permanently delete this site visit? This action cannot be undone.',
            'type' => 'delete',
            'itemName' => "Site Visit #{$this->siteVisit->id}",
            'itemDetails' => [
                'Site Name' => $this->siteVisit->site_name ?? 'Not specified',
                'Created by' => $this->siteVisit->operator?->full_name ?? 'Unknown',
                'Date' => $this->siteVisit->created_at?->format('M d, Y g:i A'),
                'Photos' => $this->siteVisit->photos?->count(),
                'Products' => $this->siteVisit->products?->count(),
                'Mafundi' => $this->siteVisit->fundis?->count(),
            ],
            'confirmMethod' => 'deleteVisit',
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

    #[On('deleteVisit')]
    public function deleteVisit()
    {
        try {
            $siteVisitId = $this->siteVisit->id;
            
            // Delete the site visit (photos, products, and fundis relationships should be handled by cascade or model events)
            $this->siteVisit->delete();

            session()->flash('message', "Site visit #{$siteVisitId} has been deleted successfully.");
            
            // Redirect to site visits list
            return redirect()->route('site-visits');
        } catch (\Exception $e) {
            Log::error("Failed to delete site visit {$this->siteVisit->id}: " . $e->getMessage());
            session()->flash('error', 'Failed to delete site visit. Please try again.');
        }
    }

    public function exportVisit()
    {
        try {
            $this->isExporting = true; // Set loading state

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

                // Convert photos to base64 for PDF embedding
                $processedPhotos = collect();

                if ($siteVisit->photos->count() > 0) {
                    $processedPhotos = $siteVisit->photos->map(function ($photo) {
                        try {

                            // Validate photo_url exists and is not empty
                            if (empty($photo->photo_url)) {
                                Log::warning("Photo {$photo->id} has empty photo_url");
                                return null;
                            }

                            // Validate URL format
                            if (!filter_var($photo->photo_url, FILTER_VALIDATE_URL)) {
                                Log::warning("Photo {$photo->id} has invalid URL: {$photo->photo_url}");
                                return null;
                            }

                            // Get image contents from URL
                            $imageData = @file_get_contents($photo->photo_url);

                            if ($imageData === false || empty($imageData)) {
                                Log::error("Failed to fetch photo {$photo->id} from URL: {$photo->photo_url}");
                                return null;
                            }

                            // Detect image type
                            $finfo = new \finfo(FILEINFO_MIME_TYPE);
                            $mimeType = $finfo->buffer($imageData);

                            // Validate it's actually an image
                            if (!str_starts_with($mimeType, 'image/')) {
                                Log::error("Photo {$photo->id} is not a valid image. MIME type: {$mimeType}");
                                return null;
                            }

                            // Convert to base64
                            $base64 = base64_encode($imageData);

                            return [
                                'base64' => "data:{$mimeType};base64,{$base64}",
                                'original_url' => $photo->photo_url
                            ];
                        } catch (\Exception $e) {
                            Log::error("Failed to process photo {$photo->id}: " . $e->getMessage());
                        }

                        return null;
                    })->filter(); // Remove null values
                }

                // Prepare data for the PDF template
                $title = "Site Visit Report - {$siteVisit->site_name}";
                $date = now()->format('D, M d, Y');

                // Generate the HTML view using the site visit report template
                $html_view = view('documents.site-visit-report', compact('siteVisit', 'title', 'date', 'processedPhotos'))->render();

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
        } finally {
            $this->isExporting = false; // Reset loading state
        }
    }

    public function render()
    {
        return view('livewire.site-visit.sidebar');
    }
}
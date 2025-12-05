<?php

namespace App\Livewire;

use App\Models\Fundi;
use Livewire\Component;
use Livewire\Attributes\On;
use App\Traits\HasAlerts;
use App\Services\SmsService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class FundiProfile extends Component
{
    use HasAlerts;

    public $fundi;
    public $transactions;
    public $siteVisits;
    public $legacyMatch = false;
    public $legacyData = null;

    protected $smsService;

    protected $listeners = [
        'refreshProfile' => '$refresh',
        'fundiUpdated' => 'handleFundiUpdate',
    ];

    public function boot(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    public function mount(Fundi $fundi)
    {
        $this->fundi = $fundi;
        $this->loadTransactions();
        $this->loadSiteVisits();
        $this->checkLegacyMatch();

        // Display any flashed alerts using trait method
        $this->displayFlashedAlerts();
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

    /**
     * Refresh profile data
     */
    public function refreshProfile()
    {
        $this->fundi->refresh();
        $this->loadTransactions();
        $this->loadSiteVisits();
        $this->checkLegacyMatch();

        // Using trait method for simple toast notification
        $this->showSuccessToast('Refreshed', 'Profile data has been updated.');
    }

public function downloadQrCode()
{
    if (!$this->fundi->qr_code_path || !Storage::disk('public')->exists($this->fundi->qr_code_path)) {
        session()->flash('error', 'QR Code not found');
        return;
    }

    // Prepare data for ID card generation
    $data = [
        'company_name' => 'MAGIC BUILDERS AFRICA',
        'id_number' => $this->fundi->unique_id ?? 'FNS-01X251',
        'name' => $this->fundi->full_name,
        'gender' => $this->fundi->gender ?? 'N/A',
        'address' => $this->fundi->address ?? 'Sinza, Kinondoni, Dar Es Salaam',
        'registered' => $this->fundi->created_at ? $this->fundi->created_at->format('d/m/Y') : date('d/m/Y'),
        'expire' => $this->fundi->expire_date ? $this->fundi->expire_date->format('d/m/Y') : date('d/m/Y', strtotime('+5 years')),
        'photo' => $this->fundi->photo_path ? Storage::disk('public')->path($this->fundi->photo_path) : public_path('images/profile.jpg'),
        'logo' => public_path('images/logo.png'),
        'qrcode' => Storage::disk('public')->path($this->fundi->qr_code_path),
    ];

    // Store data in session temporarily
    session()->put('id_card_data', $data);

    // Redirect to the ID card generation route
    return redirect()->route('fundi_id_download');
}

    /**
     * Handle fundi update event
     */
    public function handleFundiUpdate($message = null)
    {
        $this->fundi->refresh();
        $this->loadTransactions();
        $this->loadSiteVisits();

        if ($message) {
            // Using trait method for simple toast notification
            $this->showSuccessToast('Updated', $message);
        }
    }

    /**
     * Show approval confirmation dialog
     * Using custom ConfirmationModal for complex UI with item details
     */
    public function confirmApprove()
    {
        if ($this->fundi->approved_at) {
            // Using trait method for simple validation warning
            $this->showWarningAlert('Already Approved', 'This fundi has already been approved.');
            return;
        }

        // Use custom ConfirmationModal for complex confirmation with item details
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

    /**
     * Show rejection confirmation dialog
     * Using custom ConfirmationModal because it requires rejection reason input
     */
    public function confirmReject()
    {
        if ($this->fundi->approved_at) {
            // Using trait method for simple validation warning
            $this->showWarningAlert('Cannot Reject', 'This fundi has already been approved and cannot be rejected.');
            return;
        }

        // Use custom ConfirmationModal for rejection with reason input
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

    /**
     * Show delete confirmation dialog
     * Using custom ConfirmationModal for complex confirmation with related data display
     */
    public function confirmDelete()
    {
        // Check if fundi has related data
        $hasTransactions = $this->fundi->transactions()->exists();
        $hasSiteVisits = $this->fundi->siteVisits()->exists();

        $warningMessage = 'Are you sure you want to delete this fundi? This action cannot be undone.';

        if ($hasTransactions || $hasSiteVisits) {
            $relatedData = [];
            if ($hasTransactions) {
                $transactionCount = $this->fundi->transactions()->count();
                $relatedData[] = "{$transactionCount} transaction(s)";
            }
            if ($hasSiteVisits) {
                $visitCount = $this->fundi->siteVisits()->count();
                $relatedData[] = "{$visitCount} site visit(s)";
            }

            $warningMessage = 'WARNING: This fundi has ' . implode(' and ', $relatedData) . '. ' . $warningMessage;
        }

        // Use custom ConfirmationModal for delete with detailed item information
        $this->dispatch('showConfirmation', [
            'title' => 'Delete Fundi',
            'message' => $warningMessage,
            'type' => 'delete',
            'itemName' => $this->fundi->full_name,
            'itemDetails' => [
                'NIDA Number' => $this->fundi->nida_number ?? 'Not specified',
                'Primary Phone' => $this->fundi->primary_phone ?? 'Not specified',
                'Ward' => $this->fundi->ward?->name ?? 'Not specified',
                'Status' => $this->fundi->approved_at ? 'Approved' : 'Pending',
                'Transactions' => $hasTransactions ? $this->fundi->transactions()->count() : '0',
                'Site Visits' => $hasSiteVisits ? $this->fundi->siteVisits()->count() : '0',
            ],
            'confirmMethod' => 'deleteFundi',
            'confirmParams' => []
        ]);
    }

    /**
     * Approve the fundi
     * Using trait method for operation handling
     */
    #[On('approveFundi')]
    public function approveFundi()
    {
        if ($this->fundi->approved_at) {
            // Using trait method for simple flash message
            $this->flashWarning('Already Approved', 'Fundi has already been approved.');
            return;
        }

        // Using trait method for operation with flash (for page refresh/redirect scenarios)
        $result = $this->handleOperationWithFlash(
            function () {
                $this->fundi->update([
                    'status' => 'active',
                    'approved_at' => now(),
                    'approved_by' => Auth::id(),
                ]);

                // Send welcome SMS message
                $this->sendWelcomeSms();

                // Dispatch event to notify other components
                $this->dispatch('fundiStatusChanged');

                Log::info("Fundi approved: {$this->fundi->full_name} (ID: {$this->fundi->id})", [
                    'approved_by' => Auth::id(),
                    'approved_by_name' => Auth::user()->full_name ?? 'Unknown',
                    'approved_at' => now(),
                ]);

                return true;
            },
            'Fundi Approved',
            "Fundi {$this->fundi->full_name} has been approved successfully. A welcome SMS has been sent.",
            'Approval Failed',
            'Failed to approve fundi. Please try again.'
        );

        if ($result) {
            // Refresh the fundi data
            $this->fundi->refresh();
        }
    }

    /**
     * Reject the fundi
     * Using trait method for operation handling
     */
    #[On('rejectFundi')]
    public function rejectFundi($reason = null)
    {
        if ($this->fundi->approved_at) {
            // Using trait method for simple flash message
            $this->flashWarning('Cannot Reject', 'Fundi cannot be rejected after approval.');
            return;
        }

        // Using trait method for operation with flash
        $result = $this->handleOperationWithFlash(
            function () use ($reason) {
                $this->fundi->update([
                    'status' => 'inactive',
                    'rejection_reason' => $reason,
                    'rejected_at' => now(),
                    'rejected_by' => Auth::id(),
                ]);

                // Optionally send rejection SMS
                if ($reason) {
                    $this->sendRejectionSms($reason);
                }

                // Dispatch event to notify other components
                $this->dispatch('fundiStatusChanged');

                Log::info("Fundi rejected: {$this->fundi->full_name} (ID: {$this->fundi->id})", [
                    'rejected_by' => Auth::id(),
                    'rejected_by_name' => Auth::user()->full_name ?? 'Unknown',
                    'rejected_at' => now(),
                    'reason' => $reason,
                ]);

                return true;
            },
            'Fundi Rejected',
            "Fundi {$this->fundi->full_name} has been rejected successfully.",
            'Rejection Failed',
            'Failed to reject fundi. Please try again.'
        );

        if ($result) {
            // Refresh the fundi data
            $this->fundi->refresh();
        }
    }

    /**
     * Delete the fundi
     * Using trait method for operation handling with flash
     */
    #[On('deleteFundi')]
    public function deleteFundi()
    {
        // Using trait method for operation with flash (for redirect scenario)
        $result = $this->handleOperationWithFlash(
            function () {
                DB::transaction(function () {
                    $fundiName = $this->fundi->full_name;
                    $fundiId = $this->fundi->id;

                    // Log the deletion with related data count
                    $transactionCount = $this->fundi->transactions()->count();
                    $siteVisitCount = $this->fundi->siteVisits()->count();

                    // Soft delete if your model uses SoftDeletes trait
                    // Or hard delete if not
                    $this->fundi->delete();

                    // Log the deletion
                    Log::info("Fundi deleted: {$fundiName} (ID: {$fundiId})", [
                        'deleted_by' => Auth::id(),
                        'deleted_by_name' => Auth::user()->full_name ?? 'Unknown',
                        'had_transactions' => $transactionCount,
                        'had_site_visits' => $siteVisitCount,
                        'deleted_at' => now(),
                    ]);
                });

                return true;
            },
            'Fundi Deleted',
            'Fundi has been deleted successfully.',
            'Deletion Failed',
            'Failed to delete fundi. Please try again or contact support.'
        );

        if ($result) {
            // Redirect to fundis list page
            return redirect()->route('fundis');
        }
    }

    /**
     * Reactivate an inactive fundi
     * Using trait method for simple confirmation
     */
    public function reactivateFundi()
    {
        if ($this->fundi->status === 'active') {
            // Using trait method for simple validation warning
            $this->showWarningAlert('Already Active', 'This fundi is already active.');
            return;
        }

        // Using trait method for simple confirmation (no complex UI needed)
        $this->showConfirmation(
            'Reactivate Fundi',
            "Are you sure you want to reactivate {$this->fundi->full_name}? This will change their status to active.",
            'confirmReactivate',
            [],
            '',
            [],
            'Yes, reactivate',
            'Cancel'
        );
    }

    /**
     * Confirm reactivation
     * Using trait method for operation handling
     */
    public function confirmReactivate()
    {
        // Using trait method for simple operation
        $this->handleOperation(
            function () {
                $this->fundi->update([
                    'status' => 'active',
                    'reactivated_at' => now(),
                    'reactivated_by' => Auth::id(),
                ]);

                Log::info("Fundi reactivated: {$this->fundi->full_name} (ID: {$this->fundi->id})", [
                    'reactivated_by' => Auth::id(),
                    'reactivated_at' => now(),
                ]);

                $this->dispatch('fundiStatusChanged');
                $this->fundi->refresh();

                return true;
            },
            'Fundi Reactivated',
            "{$this->fundi->full_name} has been reactivated successfully.",
            'Reactivation Failed',
            'Failed to reactivate fundi. Please try again.'
        );
    }

    /**
     * Deactivate an active fundi
     * Using trait method for simple confirmation
     */
    public function deactivateFundi()
    {
        if ($this->fundi->status === 'inactive') {
            // Using trait method for simple validation warning
            $this->showWarningAlert('Already Inactive', 'This fundi is already inactive.');
            return;
        }

        // Using trait method for simple confirmation (no complex UI needed)
        $this->showConfirmation(
            'Deactivate Fundi',
            "Are you sure you want to deactivate {$this->fundi->full_name}? They will not be able to receive new assignments.",
            'confirmDeactivate',
            [],
            '',
            [],
            'Yes, deactivate',
            'Cancel'
        );
    }

    /**
     * Confirm deactivation
     * Using trait method for operation handling
     */
    public function confirmDeactivate()
    {
        // Using trait method for simple operation
        $this->handleOperation(
            function () {
                $this->fundi->update([
                    'status' => 'inactive',
                    'deactivated_at' => now(),
                    'deactivated_by' => Auth::id(),
                ]);

                Log::info("Fundi deactivated: {$this->fundi->full_name} (ID: {$this->fundi->id})", [
                    'deactivated_by' => Auth::id(),
                    'deactivated_at' => now(),
                ]);

                $this->dispatch('fundiStatusChanged');
                $this->fundi->refresh();

                return true;
            },
            'Fundi Deactivated',
            "{$this->fundi->full_name} has been deactivated successfully.",
            'Deactivation Failed',
            'Failed to deactivate fundi. Please try again.'
        );
    }

    /**
     * Resend welcome SMS
     * Using trait method for simple confirmation
     */
    public function resendWelcomeSms()
    {
        if (!$this->fundi->approved_at) {
            // Using trait method for simple validation warning
            $this->showWarningAlert('Not Approved', 'Welcome SMS can only be sent to approved fundis.');
            return;
        }

        // Using trait method for simple confirmation (no complex UI needed)
        $this->showConfirmation(
            'Resend Welcome SMS',
            "Send a welcome SMS to {$this->fundi->full_name} at {$this->fundi->primary_phone}?",
            'confirmResendWelcomeSms',
            [],
            '',
            [],
            'Yes, send SMS',
            'Cancel'
        );
    }

    /**
     * Confirm resend welcome SMS
     * Using trait method for operation handling
     */
    public function confirmResendWelcomeSms()
    {
        // Using trait method for simple operation
        $this->handleOperation(
            function () {
                $phoneNumber = $this->getValidPhoneNumber();

                if (!$phoneNumber) {
                    $this->showErrorAlert('Invalid Phone', 'No valid phone number found for this fundi.');
                    return false;
                }

                $message = $this->generateWelcomeMessage();
                $result = $this->smsService->sendSms($phoneNumber, $message);

                if (!$result['success']) {
                    Log::error("Failed to resend welcome SMS to fundi {$this->fundi->full_name}: " . ($result['error'] ?? 'Unknown error'));
                    return false;
                }

                Log::info("Welcome SMS resent to fundi {$this->fundi->full_name} ({$phoneNumber})");
                return true;
            },
            'SMS Sent',
            'Welcome SMS has been sent successfully.',
            'SMS Failed',
            'Failed to send SMS. Please try again.',
            true // Use toast for SMS operations
        );
    }

    /**
     * Send welcome SMS to newly approved fundi
     */
    private function sendWelcomeSms()
    {
        try {
            // Get the primary phone number
            $phoneNumber = $this->getValidPhoneNumber();

            if (!$phoneNumber) {
                Log::warning("No valid phone number found for fundi ID: {$this->fundi->id}");
                return;
            }

            // Create personalized welcome message
            $message = $this->generateWelcomeMessage();

            // Send SMS using the SmsService
            $result = $this->smsService->sendSms($phoneNumber, $message);

            // Log the result
            if ($result['success']) {
                Log::info("Welcome SMS sent successfully to fundi {$this->fundi->full_name} ({$phoneNumber})");
            } else {
                Log::error("Failed to send welcome SMS to fundi {$this->fundi->full_name}: " . ($result['error'] ?? 'Unknown error'));

                // Note: Don't fail the approval, just log the SMS failure
                // The approval already succeeded at this point
            }
        } catch (\Exception $e) {
            Log::error("Exception sending welcome SMS to fundi {$this->fundi->id}: " . $e->getMessage());

            // Note: Don't fail the approval, just log the SMS failure
        }
    }

    /**
     * Send rejection SMS to fundi
     */
    private function sendRejectionSms($reason = null)
    {
        try {
            $phoneNumber = $this->getValidPhoneNumber();

            if (!$phoneNumber) {
                Log::warning("No valid phone number found for fundi ID: {$this->fundi->id}");
                return;
            }

            $message = $this->generateRejectionMessage($reason);
            $result = $this->smsService->sendSms($phoneNumber, $message);

            if ($result['success']) {
                Log::info("Rejection SMS sent to fundi {$this->fundi->full_name} ({$phoneNumber})");
            } else {
                Log::error("Failed to send rejection SMS to fundi {$this->fundi->full_name}: " . ($result['error'] ?? 'Unknown error'));
            }
        } catch (\Exception $e) {
            Log::error("Exception sending rejection SMS to fundi {$this->fundi->id}: " . $e->getMessage());
        }
    }

    /**
     * Get valid phone number for the fundi
     */
    private function getValidPhoneNumber(): ?string
    {
        // Try primary phone first, then transaction phone
        $phoneNumber = $this->fundi->primary_phone ?? $this->fundi->transaction_phone;

        if (!$phoneNumber) {
            return null;
        }

        // Clean and format phone number
        $phoneNumber = preg_replace('/[^\d+]/', '', $phoneNumber);

        // Ensure it starts with +255 for Tanzania
        if (!str_starts_with($phoneNumber, '+')) {
            if (str_starts_with($phoneNumber, '0')) {
                $phoneNumber = '+255' . substr($phoneNumber, 1);
            } elseif (str_starts_with($phoneNumber, '255')) {
                $phoneNumber = '+' . $phoneNumber;
            } elseif (str_starts_with($phoneNumber, '7') && strlen($phoneNumber) == 9) {
                $phoneNumber = '+255' . $phoneNumber;
            }
        }

        return $phoneNumber;
    }

    /**
     * Generate personalized welcome message
     */
    private function generateWelcomeMessage(): string
    {
        $name = $this->fundi->fname ?? explode(' ', $this->fundi->full_name)[0] ?? 'Fundi';
        $ranking = $this->fundi->ranking == 'fundi_kuu' ? 'Fundi Mkuu' : 'Fundi Msaidizi';
        $wardName = $this->fundi->ward?->name ?? 'your area';

        return "Hongera {$name}! Umeidhinishwa rasmi kama {$ranking} katika kata ya {$wardName}. " .
            "Sasa unaweza kuanza kufanya kazi. Kwa maelezo zaidi wasiliana nasi. Asante!";
    }

    /**
     * Generate rejection message
     */
    private function generateRejectionMessage($reason = null): string
    {
        $name = $this->fundi->fname ?? explode(' ', $this->fundi->full_name)[0] ?? 'Fundi';

        $message = "Samahani {$name}, ombi lako la kuidhinishwa kama fundi halikukubaliwa.";

        if ($reason) {
            $message .= " Sababu: {$reason}.";
        }

        $message .= " Unaweza kujaribu tena baadaye. Asante.";

        return $message;
    }

    /**
     * View all transactions for this fundi
     */
    public function viewAllTransactions()
    {
        return redirect()->route('transactions', ['fundi_id' => $this->fundi->id]);
    }

    /**
     * View all site visits for this fundi
     */
    public function viewAllSiteVisits()
    {
        return redirect()->route('site-visits', ['fundi_id' => $this->fundi->id]);
    }

    public function render()
    {
        return view('livewire.fundi-profile');
    }
}

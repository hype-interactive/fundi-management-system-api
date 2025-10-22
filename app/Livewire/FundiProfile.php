<?php

namespace App\Livewire;

use App\Models\Fundi;
use Livewire\Component;
use Livewire\Attributes\On;
use App\Services\SmsService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FundiProfile extends Component
{
    public $fundi;
    public $transactions;
    public $siteVisits;
    public $legacyMatch = false;
    public $legacyData = null;

    protected $smsService;

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

        // Send welcome SMS message
        $this->sendWelcomeSms();

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

        // Optionally send rejection SMS
        // $this->sendRejectionSms($reason);

        $this->dispatch('fundiStatusChanged');
        session()->flash('message', 'Fundi rejected successfully.');
    }

    #[On('deleteFundi')]
    public function deleteFundi()
    {
        try {
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

                // Optional: Add to activity log if you have one
                // addLog('delete', "Fundi [{$fundiId}] {$fundiName} deleted");
            });

            session()->flash('message', 'Fundi deleted successfully.');
            
            // Redirect to fundis list page
            return redirect()->route('fundis');
            
        } catch (\Exception $e) {
            Log::error("Failed to delete fundi {$this->fundi->id}: " . $e->getMessage(), [
                'exception' => $e,
                'user_id' => Auth::id(),
            ]);

            session()->flash('error', 'Failed to delete fundi. Please try again or contact support.');
        }
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
            }
        } catch (\Exception $e) {
            Log::error("Exception sending welcome SMS to fundi {$this->fundi->id}: " . $e->getMessage());
        }
    }

    /**
     * Send rejection SMS to fundi (optional)
     */
    private function sendRejectionSms($reason = null)
    {
        try {
            $phoneNumber = $this->getValidPhoneNumber();

            if (!$phoneNumber) {
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

    public function render()
    {
        return view('livewire.fundi-profile');
    }
}
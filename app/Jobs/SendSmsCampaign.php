<?php

namespace App\Jobs;

use App\Models\Fundi;
use App\Models\SmsCampaign;
use App\Models\User;
use App\Services\SmsService;
use App\Traits\LocationTrait;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendSmsCampaign implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, LocationTrait, Queueable, SerializesModels;

    protected $campaign;

    public $timeout = 300; // 5 minutes timeout

    public $tries = 3;

    public function __construct(SmsCampaign $campaign)
    {
        $this->campaign = $campaign;
        $this->onQueue('sms_campaigns'); // Specify the queue for SMS campaigns
        $this->queue = 'sms_campaigns'; // Set the queue name
    }

    public function handle(SmsService $smsService)
    {
        try {
            // Update campaign status to sending
            $this->campaign->update(['status' => 'processing']);

            // Get recipients based on campaign configuration
            $recipients = $this->getRecipients();

            $successCount = 0;
            $failureCount = 0;

            foreach ($recipients as $recipient) {
                $recipientType = $recipient['type'];
                $recipientData = $recipient['data'];

                $success = $smsService->sendCampaignMessage(
                    $this->campaign,
                    $recipientData,
                    $recipientType
                );

                if ($success) {
                    $successCount++;
                } else {
                    $failureCount++;
                }

                // Add small delay between messages to avoid rate limiting
                usleep(200000); // 200ms delay
            }

            // Update campaign status
            $finalStatus = $failureCount === 0 ? 'completed' : ($successCount === 0 ? 'failed' : 'partially_completed');

            $this->campaign->update([
                'status' => $finalStatus,
                'sent_at' => now(),
            ]);

            Log::info("SMS Campaign {$this->campaign->id} completed. Success: {$successCount}, Failed: {$failureCount}");

        } catch (\Exception $e) {
            Log::error("SMS Campaign {$this->campaign->id} failed: ".$e->getMessage());

            $this->campaign->update([
                'status' => 'failed',
            ]);

            throw $e;
        }
    }

    private function getRecipients(): array
    {
        $recipients = [];
        $filters = $this->campaign->filters;

        // Group filters by type
        $regionIds = $filters->where('filter_type', 'region')->pluck('filter_value')->toArray();
        $districtIds = $filters->where('filter_type', 'district')->pluck('filter_value')->toArray();
        $wardIds = $filters->where('filter_type', 'ward')->pluck('filter_value')->toArray();
        $userIds = $filters->where('filter_type', 'user')->pluck('filter_value')->toArray();
        $fundiIds = $filters->where('filter_type', 'fundi')->pluck('filter_value')->toArray();

        // Get operators if needed
        if (in_array($this->campaign->recipient_type, ['both', 'operators'])) {
            $operatorQuery = User::whereHas('role', function ($query) {
                $query->where('name', 'operator');
            });

            // Apply specific user filters if custom selection
            if (! empty($userIds)) {
                $operatorQuery->whereIn('id', $userIds);
            }

            // Apply location filters
            $this->applyLocationFiltersToQuery($operatorQuery, $regionIds, $districtIds, $wardIds);

            foreach ($operatorQuery->get() as $operator) {
                $recipients[] = [
                    'type' => 'user',
                    'data' => $operator,
                ];
            }
        }

        // Get workers if needed
        if (in_array($this->campaign->recipient_type, ['both', 'fundis'])) {
            $fundiQuery = Fundi::query();

            // Apply specific fundi filters if custom selection
            if (! empty($fundiIds)) {
                $fundiQuery->whereIn('id', $fundiIds);
            }

            // Apply location filters
            $this->applyLocationFiltersToQuery($fundiQuery, $regionIds, $districtIds, $wardIds);

            foreach ($fundiQuery->get() as $fundi) {
                $recipients[] = [
                    'type' => 'fundi',
                    'data' => $fundi,
                ];
            }
        }

        return $recipients;
    }

    public function failed(\Throwable $exception)
    {
        Log::error("SMS Campaign Job {$this->campaign->id} failed permanently: ".$exception->getMessage());

        $this->campaign->update([
            'status' => 'failed',
        ]);
    }
}

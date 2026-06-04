<?php

namespace App\Jobs;

use App\Services\SmsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateSmsDeliveryStatuses implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120;

    public function handle(SmsService $smsService)
    {
        // Get messages that are sent but not yet delivered
        $pendingMessages = \App\Models\SmsMessage::whereIn('status', ['sent', 'pending'])
            ->whereNotNull('provider_message_id')
            ->where('created_at', '>', now()->subDays(7)) // Only check messages from last 7 days
            ->limit(100) // Process in batches
            ->get();

        foreach ($pendingMessages as $message) {
            $smsService->updateMessageDeliveryStatus($message);
            usleep(100000); // 100ms delay between status checks
        }
    }
}

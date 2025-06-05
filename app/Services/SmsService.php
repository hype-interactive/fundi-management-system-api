<?php

namespace App\Services;

use App\Models\SmsMessage;
use App\Models\SmsCampaign;
use Illuminate\Support\Facades\Log;

class SmsService
{
    private $provider;

    public function __construct()
    {
        $this->initializeProvider();
    }

    private function initializeProvider()
    {
        $providerName = config('sms.default_provider', 'beem');
        $config = config("sms.providers.{$providerName}");

        switch ($providerName) {
            case 'beem':
                $this->provider = new BeemSmsService($config);
                break;
            case 'nextsms':
                $this->provider = new NextSmsService($config);
                break;
            default:
                throw new \Exception("Unsupported SMS provider: {$providerName}");
        }

        if (!$this->provider->validateConfig()) {
            throw new \Exception("Invalid SMS provider configuration for: {$providerName}");
        }
    }

    public function sendSms(string $phoneNumber, string $message, string|null $campaignId = null): array
    {
        return $this->provider->sendSms($phoneNumber, $message, $campaignId);
    }

    public function checkDeliveryStatus(string $messageId): array
    {
        return $this->provider->checkDeliveryStatus($messageId);
    }

    public function sendToMany(string $message, array $phoneNumbers): array
    {
        $results = [];
        $successCount = 0;
        $failureCount = 0;

        foreach ($phoneNumbers as $phoneNumber) {
            try {
                $result = $this->sendSms($phoneNumber, $message, $phoneNumber);

                $results[] = [
                    'phone_number' => $phoneNumber,
                    'success' => $result['success'],
                    'message_id' => $result['message_id'] ?? null,
                    'error' => $result['success'] ? null : ($result['error'] ?? 'Unknown error')
                ];

                if ($result['success']) {
                    $successCount++;
                } else {
                    $failureCount++;
                }

                // Add small delay between messages to avoid rate limiting
                usleep(200000); // 200ms delay

            } catch (\Throwable $e) {
                Log::error("Failed to send SMS to {$phoneNumber}: " . $e->getMessage());

                $results[] = [
                    'phone_number' => $phoneNumber,
                    'success' => false,
                    'message_id' => null,
                    'error' => $e->getMessage()
                ];

                $failureCount++;
            }
        }

        return [
            'total_sent' => count($phoneNumbers),
            'successful' => $successCount,
            'failed' => $failureCount,
            'results' => $results
        ];
    }

    public function sendCampaignMessage(SmsCampaign $campaign, $recipient, string $recipientType): bool
    {
        try {
            // Get phone number based on recipient type
            $phoneNumber = $this->getRecipientPhoneNumber($recipient, $recipientType);

            if (!$phoneNumber) {
                Log::warning("No phone number found for {$recipientType} ID: {$recipient->id}");
                return false;
            }

            // Send SMS
            $result = $this->sendSms($phoneNumber, $campaign->message, $campaign->id);

            Log::info("SMS sent to {$recipientType} {$recipient->id}: " . json_encode($result));

            // Create SMS message record
            SmsMessage::create([
                'sms_campaign_id' => $campaign->id,
                'recipient_type' => $recipientType,
                'recipient_id' => $recipient->id,
                'phone_number' => $phoneNumber,
                'message' => $campaign->message,
                'status' => $result['status'],
                'provider_message_id' => $result['message_id'] ?? null,
                'sent_at' => $result['success'] ? now() : null,
                'failure_reason' => $result['success'] ? null : ($result['error'] ?? 'Unknown error'),
            ]);

            return $result['success'];
        } catch (\Exception $e) {
            Log::error("Failed to send SMS to {$recipientType} {$recipient->id}: " . $e->getMessage());

            // Create failed message record
            SmsMessage::create([
                'sms_campaign_id' => $campaign->id,
                'recipient_type' => $recipientType,
                'recipient_id' => $recipient->id,
                'phone_number' => $phoneNumber ?? 'unknown',
                'message' => $campaign->message,
                'status' => 'failed',
                'failure_reason' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function getRecipientPhoneNumber($recipient, string $recipientType): ?string
    {
        switch ($recipientType) {
            case 'user':
            case 'operator':
                return $recipient->phone ?? $recipient->phone_number ?? null;
            case 'fundi':
            case 'worker':
                return $recipient->phone ?? $recipient->primary_phone ?? $recipient->transaction_phone ?? null;
            case 'custom':
                return $recipient['phone'] ?? $recipient['phone_number'] ?? null;
            default:
                return null;
        }
    }

    public function updateMessageDeliveryStatus(SmsMessage $message): void
    {
        if (!$message->provider_message_id) {
            return;
        }

        try {
            $result = $this->checkDeliveryStatus($message->provider_message_id);

            if ($result['success']) {
                $message->update([
                    'status' => $result['status'],
                    'delivered_at' => $result['delivered_at'] ?
                        \Carbon\Carbon::parse($result['delivered_at']) : null,
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Failed to update delivery status for message {$message->id}: " . $e->getMessage());
        }
    }
}

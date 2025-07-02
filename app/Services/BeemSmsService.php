<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class BeemSmsService extends BaseSmsService
{
    public function sendSms(string $phoneNumber, string $message, string|null $campaignId = null): array
    {
        try {
            // Use the exact same structure as the working implementation
            $postData = [
                'source_addr' => $this->config['sender_id'],
                'encoding' => 0,
                'schedule_time' => '',
                'message' => $message,
                'recipients' => [
                    [
                        // 'recipient_id' => $campaignId ?? uniqid(),
                        'recipient_id' => $this->formatPhoneNumber($phoneNumber), // Use phone number as recipient ID
                        'dest_addr' => $this->formatPhoneNumber($phoneNumber)
                    ]
                ]
            ];

            // Use Basic Authentication as in the working implementation
            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . base64_encode($this->config['api_key'] . ':' . $this->config['api_secret']),
                'Content-Type' => 'application/json',
            ])->post($this->config['base_url'], $postData);

            if ($response->successful()) {
                $data = $response->json();
                Log::info("Beem SMS Response: " . $response->body());

                return [
                    'success' => true,
                    'message_id' => $data['request_id'] ?? $data['id'] ?? null,
                    'status' => 'sent',
                    'response' => $data
                ];
            } else {
                Log::error("Beem SMS Error Response: " . $response->body());
                return [
                    'success' => false,
                    'error' => $response->body(),
                    'status' => 'failed'
                ];
            }
        } catch (\Exception $e) {
            Log::error('Beem SMS Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'status' => 'failed'
            ];
        }
    }

    public function checkDeliveryStatus(string $messageId): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->config['api_key'],
            ])->get("https://apisms.beem.africa/public/v1/delivery-reports/{$messageId}");

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'status' => $this->mapBeemStatus($data['status'] ?? 'unknown'),
                    'delivered_at' => $data['delivered_at'] ?? null
                ];
            }

            return ['success' => false, 'error' => 'Status check failed'];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function mapBeemStatus(string $status): string
    {
        $statusMap = [
            'DELIVRD' => 'delivered',
            'EXPIRED' => 'failed',
            'DELETED' => 'failed',
            'UNDELIV' => 'failed',
            'ACCEPTD' => 'sent',
            'UNKNOWN' => 'pending',
            'REJECTD' => 'failed'
        ];

        return $statusMap[$status] ?? 'pending';
    }

    public function validateConfig(): bool
    {
        return !empty($this->config['api_key']) &&
            !empty($this->config['api_secret']) &&
            !empty($this->config['sender_id']) &&
            !empty($this->config['base_url']);
    }

    private function formatPhoneNumber(string $phoneNumber): string
    {
        // Remove any non-digit characters
        $cleaned = preg_replace('/[^0-9]/', '', $phoneNumber);

        // If it starts with 0, replace with country code (255 for Tanzania)
        if (substr($cleaned, 0, 1) === '0') {
            $cleaned = '255' . substr($cleaned, 1);
        }

        // If it doesn't start with country code, add it
        if (!str_starts_with($cleaned, '255')) {
            $cleaned = '255' . $cleaned;
        }

        return $cleaned;
    }
}

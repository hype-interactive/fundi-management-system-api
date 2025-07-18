<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class NextSmsService extends BaseSmsService
{
    private $baseUrl = 'https://messaging-service.co.tz/api/sms/v1/text/single';

    public function sendSms(string $phoneNumber, string $message, string|null $campaignId = null): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . base64_encode($this->config['username'] . ':' . $this->config['password']),
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post($this->baseUrl, [
                'from' => $this->config['sender_id'],
                'to' => $this->formatPhoneNumber($phoneNumber),
                'text' => $message,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'message_id' => $data['messageId'] ?? null,
                    'status' => 'sent',
                    'response' => $data
                ];
            } else {
                return [
                    'success' => false,
                    'error' => $response->body(),
                    'status' => 'failed'
                ];
            }
        } catch (\Exception $e) {
            Log::error('NextSMS Error: ' . $e->getMessage());
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
                'Authorization' => 'Basic ' . base64_encode($this->config['username'] . ':' . $this->config['password']),
            ])->get("https://messaging-service.co.tz/api/sms/v1/reports/{$messageId}");

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'status' => $this->mapNextSmsStatus($data['status'] ?? 'unknown'),
                    'delivered_at' => $data['deliveredAt'] ?? null
                ];
            }

            return ['success' => false, 'error' => 'Status check failed'];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function mapNextSmsStatus(string $status): string
    {
        $statusMap = [
            'DELIVERED' => 'delivered',
            'FAILED' => 'failed',
            'SENT' => 'sent',
            'PENDING' => 'pending',
            'EXPIRED' => 'failed',
            'REJECTED' => 'failed'
        ];

        return $statusMap[strtoupper($status)] ?? 'pending';
    }

    public function validateConfig(): bool
    {
        return !empty($this->config['username']) &&
            !empty($this->config['password']) &&
            !empty($this->config['sender_id']);
    }

    protected function formatPhoneNumber(string $phoneNumber): string
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

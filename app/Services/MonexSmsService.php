<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MonexSmsService extends BaseSmsService
{
    public function sendSms(string $phoneNumber, string $message, ?string $campaignId = null): array
    {
        try {
            $postData = [
                'recipient' => $this->formatPhoneNumber($phoneNumber),
                'sender_id' => $this->config['sender_id'],
                'type' => 'plain',
                'message' => $message,
            ];

            // Add optional fields if provided
            if (! empty($this->config['dlt_template_id'])) {
                $postData['dlt_template_id'] = $this->config['dlt_template_id'];
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->config['api_token'],
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post($this->config['base_url'].'/send', $postData);

            if ($response->successful()) {
                $data = $response->json();
                Log::info('Monex SMS Response: '.$response->body());

                if ($data['status'] === 'success') {
                    return [
                        'success' => true,
                        'message_id' => $data['data']['uid'] ?? null,
                        'status' => 'sent',
                        'response' => $data,
                    ];
                } else {
                    return [
                        'success' => false,
                        'error' => $data['message'] ?? 'Unknown error',
                        'status' => 'failed',
                    ];
                }
            } else {
                Log::error('Monex SMS Error Response: '.$response->body());

                return [
                    'success' => false,
                    'error' => $response->body(),
                    'status' => 'failed',
                ];
            }
        } catch (\Exception $e) {
            Log::error('Monex SMS Error: '.$e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'status' => 'failed',
            ];
        }
    }

    public function sendToMany(string $message, array $phoneNumbers): array
    {
        try {
            // Monex SMS supports multiple recipients in a single request
            $recipients = array_map([$this, 'formatPhoneNumber'], $phoneNumbers);
            $recipientString = implode(',', $recipients);

            $postData = [
                'recipient' => $recipientString,
                'sender_id' => $this->config['sender_id'],
                'type' => 'plain',
                'message' => $message,
            ];

            // Add optional fields if provided
            if (! empty($this->config['dlt_template_id'])) {
                $postData['dlt_template_id'] = $this->config['dlt_template_id'];
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->config['api_token'],
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post($this->config['base_url'].'/send', $postData);

            if ($response->successful()) {
                $data = $response->json();
                Log::info('Monex SMS Bulk Response: '.$response->body());

                if ($data['status'] === 'success') {
                    // Create results for each phone number
                    $results = [];
                    foreach ($phoneNumbers as $phoneNumber) {
                        $results[] = [
                            'phone_number' => $phoneNumber,
                            'success' => true,
                            'message_id' => $data['data']['uid'] ?? null,
                            'error' => null,
                        ];
                    }

                    return [
                        'total_sent' => count($phoneNumbers),
                        'successful' => count($phoneNumbers),
                        'failed' => 0,
                        'results' => $results,
                    ];
                } else {
                    // All failed
                    $results = [];
                    foreach ($phoneNumbers as $phoneNumber) {
                        $results[] = [
                            'phone_number' => $phoneNumber,
                            'success' => false,
                            'message_id' => null,
                            'error' => $data['message'] ?? 'Unknown error',
                        ];
                    }

                    return [
                        'total_sent' => count($phoneNumbers),
                        'successful' => 0,
                        'failed' => count($phoneNumbers),
                        'results' => $results,
                    ];
                }
            } else {
                Log::error('Monex SMS Bulk Error Response: '.$response->body());

                // All failed
                $results = [];
                foreach ($phoneNumbers as $phoneNumber) {
                    $results[] = [
                        'phone_number' => $phoneNumber,
                        'success' => false,
                        'message_id' => null,
                        'error' => 'API request failed',
                    ];
                }

                return [
                    'total_sent' => count($phoneNumbers),
                    'successful' => 0,
                    'failed' => count($phoneNumbers),
                    'results' => $results,
                ];
            }
        } catch (\Exception $e) {
            Log::error('Monex SMS Bulk Error: '.$e->getMessage());

            // All failed
            $results = [];
            foreach ($phoneNumbers as $phoneNumber) {
                $results[] = [
                    'phone_number' => $phoneNumber,
                    'success' => false,
                    'message_id' => null,
                    'error' => $e->getMessage(),
                ];
            }

            return [
                'total_sent' => count($phoneNumbers),
                'successful' => 0,
                'failed' => count($phoneNumbers),
                'results' => $results,
            ];
        }
    }

    public function sendCampaign(array $contactListIds, string $message): array
    {
        try {
            $contactListString = implode(',', $contactListIds);

            $postData = [
                'contact_list_id' => $contactListString,
                'sender_id' => $this->config['sender_id'],
                'type' => 'plain',
                'message' => $message,
            ];

            // Add optional fields if provided
            if (! empty($this->config['dlt_template_id'])) {
                $postData['dlt_template_id'] = $this->config['dlt_template_id'];
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->config['api_token'],
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post($this->config['base_url'].'/campaign', $postData);

            if ($response->successful()) {
                $data = $response->json();
                Log::info('Monex SMS Campaign Response: '.$response->body());

                return [
                    'success' => $data['status'] === 'success',
                    'message_id' => $data['data']['uid'] ?? null,
                    'status' => $data['status'] === 'success' ? 'sent' : 'failed',
                    'response' => $data,
                ];
            } else {
                Log::error('Monex SMS Campaign Error Response: '.$response->body());

                return [
                    'success' => false,
                    'error' => $response->body(),
                    'status' => 'failed',
                ];
            }
        } catch (\Exception $e) {
            Log::error('Monex SMS Campaign Error: '.$e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'status' => 'failed',
            ];
        }
    }

    public function checkDeliveryStatus(string $messageId): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->config['api_token'],
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->get($this->config['base_url']."/{$messageId}");

            if ($response->successful()) {
                $data = $response->json();

                if ($data['status'] === 'success') {
                    return [
                        'success' => true,
                        'status' => $this->mapMonexStatus($data['data']['status'] ?? 'unknown'),
                        'delivered_at' => $data['data']['delivered_at'] ?? null,
                    ];
                } else {
                    return [
                        'success' => false,
                        'error' => $data['message'] ?? 'Status check failed',
                    ];
                }
            }

            return ['success' => false, 'error' => 'Status check failed'];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getAllMessages(): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->config['api_token'],
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->get($this->config['base_url']);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'success' => $data['status'] === 'success',
                    'data' => $data['data'] ?? [],
                    'response' => $data,
                ];
            }

            return ['success' => false, 'error' => 'Failed to retrieve messages'];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function mapMonexStatus(string $status): string
    {
        $statusMap = [
            'sent' => 'sent',
            'delivered' => 'delivered',
            'failed' => 'failed',
            'pending' => 'pending',
            'rejected' => 'failed',
            'expired' => 'failed',
        ];

        return $statusMap[strtolower($status)] ?? 'pending';
    }

    public function validateConfig(): bool
    {
        return ! empty($this->config['api_token']) &&
            ! empty($this->config['sender_id']) &&
            ! empty($this->config['base_url']);
    }

    protected function formatPhoneNumber(string $phoneNumber): string
    {
        // Remove any non-digit characters
        $cleaned = preg_replace('/[^0-9]/', '', $phoneNumber);

        // If it starts with 0, replace with country code (255 for Tanzania)
        if (substr($cleaned, 0, 1) === '0') {
            $cleaned = '255'.substr($cleaned, 1);
        }

        // If it doesn't start with country code, add it
        if (! str_starts_with($cleaned, '255')) {
            $cleaned = '255'.$cleaned;
        }

        return $cleaned;
    }
}

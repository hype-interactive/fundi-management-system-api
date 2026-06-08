<?php

namespace App\Services\Payment;

use App\Exceptions\SelcomException;
use App\Models\Transaction;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Complete SELCOM Payment Service
 * Handles all SELCOM API interactions for disbursements
 */
class SelcomService
{
    // API Endpoints
    private const BASE_URL = 'https://apigw.selcommobile.com';

    private const ENDPOINT_WALLET_CASHIN = '/v1/walletcashin/process';

    private const ENDPOINT_WALLET_QUERY = '/v1/walletcashin/query';

    private const ENDPOINT_NAME_LOOKUP = '/v1/walletcashin/namelookup';

    private const ENDPOINT_VENDOR_BALANCE = '/v1/vendor/balance';

    // Result Codes
    private const RESULT_SUCCESS = '000';

    private const RESULT_PENDING = '111';

    // Wallet Type Codes
    private const WALLET_CODES = [
        'vodacom' => 'VMCASHIN',
        'mpesa' => 'VMCASHIN',
        'airtel' => 'AMCASHIN',
        'airtelmoney' => 'AMCASHIN',
        'tigo' => 'TPCASHIN',
        'tigopesa' => 'TPCASHIN',
        'halopesa' => 'HPCASHIN',
        'ttcl' => 'TTCASHIN',
        'ezy' => 'EZCASHIN',
        'auto' => 'CASHIN',
    ];

    // Configuration
    protected string $vendorId;

    protected string $vendorPin;

    protected string $apiKey;

    protected string $apiSecret;

    protected int $timeout;

    protected bool $debug;

    public function __construct()
    {
        $this->vendorId = config('selcom.vendor_id');
        $this->vendorPin = config('selcom.vendor_pin');
        $this->apiKey = config('selcom.api_key');
        $this->apiSecret = config('selcom.api_secret');
        $this->timeout = config('selcom.timeout', 60);
        $this->debug = config('selcom.debug', false);

        $this->validateConfiguration();
    }

    /**
     * Check SELCOM vendor balance
     */
    public function checkBalance(): array
    {
        try {
            Log::info('Checking SELCOM vendor balance');

            $payload = [
                'vendor' => $this->vendorId,
            ];

            $response = $this->makeRequest(self::ENDPOINT_VENDOR_BALANCE, $payload);

            if ($this->isSuccessfulResponse($response)) {
                $balance = (float) ($response['balance'] ?? 0);

                Log::info('Balance check successful', [
                    'balance' => $balance,
                    'currency' => $response['currency'] ?? 'TZS',
                ]);

                return [
                    'success' => true,
                    'balance' => $balance,
                    'currency' => $response['currency'] ?? 'TZS',
                ];
            }

            return [
                'success' => false,
                'error' => $response['message'] ?? 'Failed to fetch balance',
                'response' => $response,
            ];

        } catch (Exception $e) {
            Log::error('Balance check failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => 'Unable to check balance: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Verify recipient name before disbursement
     */
    public function verifyRecipient(string $phoneNumber, ?string $walletType = null): array
    {
        try {
            $formattedPhone = $this->formatPhoneNumber($phoneNumber);
            $utilityCode = $this->getUtilityCode($walletType ?? $this->detectWalletType($formattedPhone));

            Log::info('Verifying recipient', [
                'phone' => $this->maskPhoneNumber($formattedPhone),
                'utility_code' => $utilityCode,
            ]);

            $payload = [
                'utilitycode' => $utilityCode,
                'utilityref' => $formattedPhone,
            ];

            $response = $this->makeRequest(self::ENDPOINT_NAME_LOOKUP, $payload);

            if ($this->isSuccessfulResponse($response)) {
                return [
                    'success' => true,
                    'name' => $response['custname'] ?? 'Unknown',
                    'phone' => $formattedPhone,
                    'utility_code' => $utilityCode,
                ];
            }

            return [
                'success' => false,
                'error' => $response['message'] ?? 'Verification failed',
                'response' => $response,
            ];

        } catch (Exception $e) {
            Log::error('Recipient verification failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Unable to verify recipient',
            ];
        }
    }

    /**
     * Initiate disbursement for a transaction
     */
    public function initiateDisbursement(Transaction $transaction): array
    {
        try {
            Log::info('=== DISBURSEMENT INITIATION ===', [
                'transaction_id' => $transaction->id,
                'fundi_id' => $transaction->fundi_id,
                'amount' => $transaction->amount,
            ]);

            // Validate transaction
            $validation = $this->validateTransaction($transaction);
            if (! $validation['valid']) {
                throw new SelcomException($validation['error']);
            }

            $fundi = $transaction->fundi;
            $phoneNumber = $this->formatPhoneNumber($fundi->primary_phone);
            $walletType = $this->detectWalletType($phoneNumber);
            $utilityCode = $this->getUtilityCode($walletType);

            Log::info('Disbursement details', [
                'transaction_id' => $transaction->id,
                'phone' => $this->maskPhoneNumber($phoneNumber),
                'wallet_type' => $walletType,
                'utility_code' => $utilityCode,
            ]);

            // Build payload
            $payload = [
                'transid' => "TXN-{$transaction->id}",
                'utilitycode' => $utilityCode,
                'utilityref' => $phoneNumber,
                'amount' => (float) $transaction->amount,
                'vendor' => $this->vendorId,
                'pin' => $this->vendorPin,
                'msisdn' => $phoneNumber,
            ];

            // Make API call
            $response = $this->makeRequest(self::ENDPOINT_WALLET_CASHIN, $payload);

            if (! $this->isSuccessfulResponse($response)) {
                throw new SelcomException(
                    $response['message'] ?? 'Disbursement failed',
                    (int) ($response['resultcode'] ?? 0)
                );
            }

            $selcomReference = $response['reference'] ?? "TXN-{$transaction->id}";

            Log::info('=== DISBURSEMENT SUCCESSFUL ===', [
                'transaction_id' => $transaction->id,
                'reference' => $selcomReference,
            ]);

            return [
                'success' => true,
                'reference' => $selcomReference,
                'transaction_id' => $response['transid'] ?? $payload['transid'],
                'message' => 'Disbursement initiated successfully',
                'response' => $response,
            ];

        } catch (SelcomException $e) {
            Log::error('=== DISBURSEMENT FAILED ===', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
                'failure_reason' => $e->getFailureReason(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'failure_reason' => $e->getFailureReason(),
            ];

        } catch (Exception $e) {
            Log::error('=== DISBURSEMENT EXCEPTION ===', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => 'Disbursement exception: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Query disbursement status
     */
    public function queryDisbursement(string $transactionReference): array
    {
        try {
            Log::info('Querying disbursement status', [
                'reference' => $transactionReference,
            ]);

            $payload = [
                'transid' => $transactionReference,
            ];

            $response = $this->makeRequest(self::ENDPOINT_WALLET_QUERY, $payload);

            $status = $this->mapStatusCode($response['resultcode'] ?? null);

            Log::info('Query response', [
                'reference' => $transactionReference,
                'status' => $status,
                'resultcode' => $response['resultcode'] ?? 'unknown',
            ]);

            return [
                'success' => true,
                'status' => $status,
                'response' => $response,
            ];

        } catch (Exception $e) {
            Log::error('Query failed', [
                'reference' => $transactionReference,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Query failed',
            ];
        }
    }

    /**
     * Make authenticated request to SELCOM API
     */
    protected function makeRequest(string $endpoint, array $payload): array
    {
        $url = self::BASE_URL.$endpoint;
        $timestamp = now()->toIso8601String();

        // Build headers with signature
        $headers = $this->buildHeaders($payload, $timestamp);

        if ($this->debug) {
            Log::debug('SELCOM Request', [
                'url' => $url,
                'payload' => $this->sanitizePayload($payload),
                'headers' => $this->sanitizeHeaders($headers),
            ]);
        }

        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders($headers)
                ->post($url, $payload);

            $responseData = $response->json();

            if ($this->debug) {
                Log::debug('SELCOM Response', [
                    'status' => $response->status(),
                    'response' => $responseData,
                ]);
            }

            if (! $response->successful()) {
                throw new SelcomException(
                    "API request failed: HTTP {$response->status()}",
                    $response->status()
                );
            }

            return $responseData ?? [];

        } catch (Exception $e) {
            Log::error('SELCOM Request Failed', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

            throw new SelcomException(
                'Request failed: '.$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Build authentication headers
     */
    protected function buildHeaders(array $payload, string $timestamp): array
    {
        $signedFields = implode(',', array_keys($payload));
        $signature = $this->computeSignature($payload, $signedFields, $timestamp);
        $authorization = base64_encode($this->apiKey);

        return [
            'Content-Type' => 'application/json;charset=utf-8',
            'Accept' => 'application/json',
            'Cache-Control' => 'no-cache',
            'Authorization' => "SELCOM {$authorization}",
            'Digest-Method' => 'HS256',
            'Digest' => $signature,
            'Timestamp' => $timestamp,
            'Signed-Fields' => $signedFields,
        ];
    }

    /**
     * Compute HMAC-SHA256 signature
     */
    protected function computeSignature(array $payload, string $signedFields, string $timestamp): string
    {
        $fieldsOrder = explode(',', $signedFields);
        $signData = "timestamp={$timestamp}";

        foreach ($fieldsOrder as $key) {
            $signData .= "&{$key}=".($payload[$key] ?? '');
        }

        return base64_encode(
            hash_hmac('sha256', $signData, $this->apiSecret, true)
        );
    }

    /**
     * Validate transaction before disbursement
     */
    protected function validateTransaction(Transaction $transaction): array
    {
        if (! $transaction->fundi) {
            return ['valid' => false, 'error' => 'Fundi not found'];
        }

        if (empty($transaction->fundi->primary_phone)) {
            return ['valid' => false, 'error' => 'Fundi phone number not found'];
        }

        if (! $this->isValidPhoneNumber($transaction->fundi->primary_phone)) {
            return ['valid' => false, 'error' => 'Invalid phone number format'];
        }

        if ($transaction->amount <= 0) {
            return ['valid' => false, 'error' => 'Invalid amount'];
        }

        return ['valid' => true];
    }

    /**
     * Format phone number to SELCOM format (255XXXXXXXXX)
     */
    protected function formatPhoneNumber(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (substr($phone, 0, 1) === '0') {
            return '255'.substr($phone, 1);
        }

        if (substr($phone, 0, 3) !== '255') {
            return '255'.$phone;
        }

        return $phone;
    }

    /**
     * Validate Tanzanian phone number
     */
    protected function isValidPhoneNumber(string $phone): bool
    {
        $formatted = $this->formatPhoneNumber($phone);

        return preg_match('/^255[67][0-9]{8}$/', $formatted);
    }

    /**
     * Detect wallet type from phone number
     */
    protected function detectWalletType(string $phone): string
    {
        $phone = $this->formatPhoneNumber($phone);

        // Extract prefix (4th-6th digits after 255)
        $prefix = substr($phone, 3, 3);

        return match (true) {
            in_array($prefix, ['074', '075', '076']) => 'vodacom',
            in_array($prefix, ['068', '069', '078']) => 'airtel',
            in_array($prefix, ['071', '065', '067']) => 'tigo',
            $prefix === '062' => 'halopesa',
            $prefix === '073' => 'ttcl',
            default => 'auto',
        };
    }

    /**
     * Get utility code for wallet type
     */
    protected function getUtilityCode(string $walletType): string
    {
        return self::WALLET_CODES[strtolower($walletType)] ?? self::WALLET_CODES['auto'];
    }

    /**
     * Check if response indicates success
     */
    protected function isSuccessfulResponse(array $response): bool
    {
        if (empty($response)) {
            return false;
        }

        $resultCode = $response['resultcode'] ?? null;
        $result = $response['result'] ?? null;

        return $resultCode === self::RESULT_SUCCESS
            || $resultCode === self::RESULT_PENDING
            || strtoupper($result) === 'SUCCESS';
    }

    /**
     * Map SELCOM result code to internal status
     */
    protected function mapStatusCode(?string $resultCode): string
    {
        return match ($resultCode) {
            self::RESULT_SUCCESS => 'completed',
            self::RESULT_PENDING => 'pending',
            default => 'failed',
        };
    }

    /**
     * Mask phone number for logging
     */
    protected function maskPhoneNumber(string $phone): string
    {
        if (strlen($phone) < 4) {
            return '***';
        }

        return substr($phone, 0, 3).'****'.substr($phone, -2);
    }

    /**
     * Sanitize payload for logging
     */
    protected function sanitizePayload(array $payload): array
    {
        $sanitized = $payload;

        if (isset($sanitized['pin'])) {
            $sanitized['pin'] = '****';
        }

        if (isset($sanitized['utilityref'])) {
            $sanitized['utilityref'] = $this->maskPhoneNumber($sanitized['utilityref']);
        }

        if (isset($sanitized['msisdn'])) {
            $sanitized['msisdn'] = $this->maskPhoneNumber($sanitized['msisdn']);
        }

        return $sanitized;
    }

    /**
     * Sanitize headers for logging
     */
    protected function sanitizeHeaders(array $headers): array
    {
        $sanitized = $headers;

        if (isset($sanitized['Authorization'])) {
            $sanitized['Authorization'] = 'SELCOM ****';
        }

        if (isset($sanitized['Digest'])) {
            $sanitized['Digest'] = '****';
        }

        return $sanitized;
    }

    /**
     * Validate configuration
     */
    protected function validateConfiguration(): void
    {
        if (empty($this->vendorId)) {
            throw new SelcomException('SELCOM Vendor ID is not configured');
        }

        if (empty($this->vendorPin)) {
            throw new SelcomException('SELCOM Vendor PIN is not configured');
        }

        if (empty($this->apiKey)) {
            throw new SelcomException('SELCOM API Key is not configured');
        }

        if (empty($this->apiSecret)) {
            throw new SelcomException('SELCOM API Secret is not configured');
        }
    }
}

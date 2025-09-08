<?php

namespace App\Services\Payment\Selcom;

use Exception;
use InvalidArgumentException;
use App\Exceptions\SelcomException;

/**
 * Selcom Payment Gateway Integration
 *
 * A modern, secure implementation for integrating with Selcom's payment API.
 * Supports checkout creation, wallet payments, and payment callbacks.
 *
 * @package App\Services\Payment\Selcom
 * @version 2.0
 */
class SelcomPaymentService
{
    protected SelcomConfig $config;
    protected HttpClient $httpClient;

    public function __construct(SelcomConfig $config = null, HttpClient $httpClient = null)
    {
        $this->config = $config ?? new SelcomConfig();
        $this->httpClient = $httpClient ?? new HttpClient();
    }

    /**
     * Create a minimal checkout order for predetermined recipients
     *
     * @param PaymentRequest $request
     * @return PaymentResponse
     * @throws SelcomException
     */
    public function createCheckout(PaymentRequest $request): PaymentResponse
    {
        $this->validateRecipient($request->getRecipient());

        $payload = $request->toArray();
        $response = $this->makeRequest('POST', $this->config->getCheckoutEndpoint(), $payload);

        return PaymentResponse::fromArray($response);
    }

    /**
     * Process wallet payment for predetermined recipients
     *
     * @param WalletPaymentRequest $request
     * @return PaymentResponse
     * @throws SelcomException
     */
    public function processWalletPayment(WalletPaymentRequest $request): PaymentResponse
    {
        $this->validateRecipient($request->getRecipient());

        $payload = $request->toArray();
        $response = $this->makeRequest('POST', $this->config->getWalletEndpoint(), $payload);

        return PaymentResponse::fromArray($response);
    }

    /**
     * Retrieve orders within date range
     *
     * @param string $fromDate
     * @param string $toDate
     * @return array
     * @throws SelcomException
     */
    public function getOrders(string $fromDate, string $toDate): array
    {
        $endpoint = str_replace(
            ['{from_date}', '{to_date}'],
            [$fromDate, $toDate],
            $this->config->getOrdersEndpoint()
        );

        return $this->makeRequest('GET', $endpoint);
    }

    /**
     * Validate that recipient is in the predetermined list
     *
     * @param string $recipient
     * @throws InvalidArgumentException
     */
    protected function validateRecipient(string $recipient): void
    {
        if (!$this->config->isAllowedRecipient($recipient)) {
            throw new InvalidArgumentException("Recipient '{$recipient}' is not in the predetermined list");
        }
    }

    /**
     * Make authenticated request to Selcom API
     *
     * @param string $method
     * @param string $url
     * @param array $data
     * @return array
     * @throws SelcomException
     */
    protected function makeRequest(string $method, string $url, array $data = []): array
    {
        $headers = $this->buildHeaders($data);

        try {
            $response = $this->httpClient->request($method, $url, $data, $headers);

            if ($response['status'] !== 200) {
                throw new SelcomException(
                    "API request failed: {$response['error']}",
                    $response['status']
                );
            }

            return $response['data'];

        } catch (Exception $e) {
            throw new SelcomException("Request failed: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Build authentication headers for API requests
     *
     * @param array $data
     * @return array
     */
    protected function buildHeaders(array $data): array
    {
        $timestamp = date('c');
        $signedFields = implode(',', array_keys($data));
        $digest = $this->computeSignature($data, $signedFields, $timestamp);
        $authorization = base64_encode($this->config->getApiKey());

        return [
            'Content-Type: application/json;charset=utf-8',
            'Accept: application/json',
            'Cache-Control: no-cache',
            "Authorization: SELCOM {$authorization}",
            'Digest-Method: HS256',
            "Digest: {$digest}",
            "Timestamp: {$timestamp}",
            "Signed-Fields: {$signedFields}",
        ];
    }

    /**
     * Compute HMAC signature for request authentication
     *
     * @param array $parameters
     * @param string $signedFields
     * @param string $timestamp
     * @return string
     */
    protected function computeSignature(array $parameters, string $signedFields, string $timestamp): string
    {
        $fieldsOrder = explode(',', $signedFields);
        $signData = "timestamp={$timestamp}";

        foreach ($fieldsOrder as $key) {
            $signData .= "&{$key}=" . $parameters[$key];
        }

        return base64_encode(hash_hmac('sha256', $signData, $this->config->getApiSecret(), true));
    }
}

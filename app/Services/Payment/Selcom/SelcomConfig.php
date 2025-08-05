<?php

namespace App\Services\Payment\Selcom;

use InvalidArgumentException;

/**
 * Configuration class for Selcom integration
 */
class SelcomConfig
{
    private const ENDPOINTS = [
        'checkout' => 'https://apigw.selcommobile.com/v1/checkout/create-order-minimal',
        'wallet' => 'https://apigw.selcommobile.com/v1/checkout/wallet-payment',
        'wallet_cashin' => 'https://apigw.selcommobile.com/v1/walletcashin/process',
        'wallet_lookup' => 'https://apigw.selcommobile.com/v1/walletcashin/namelookup',
        'wallet_query' => 'https://apigw.selcommobile.com/v1/walletcashin/query',
        'selcompesa_cashin' => 'https://apigw.selcommobile.com/v1/selcompesa/cashin',
        'selcompesa_lookup' => 'https://apigw.selcommobile.com/v1/selcompesa/namelookup',
        'selcompesa_query' => 'https://apigw.selcompesa/query',
        'orders' => 'https://apigw.selcommobile.com/v1/checkout/list-orders?fromdate={from_date}&todate={to_date}',
        'callback' => 'https://mafundi.magicbuilders.co.tz/api/selcom/payment-callback',
        'cancel' => 'https://mafundi.magicbuilders.co.tz/',
    ];

    private string $vendorId;
    private string $vendorPin;
    private string $apiKey;
    private string $apiSecret;
    private array $allowedRecipients;
    private int $paymentExpiry;

    public function __construct()
    {
        $this->vendorId = config('selcom.vendor_id', 'TILL60611001');
        $this->vendorPin = config('selcom.vendor_pin');
        $this->apiKey = config('selcom.api_key');
        $this->apiSecret = config('selcom.api_secret');
        $this->allowedRecipients = config('selcom.allowed_recipients', []);
        $this->paymentExpiry = config('selcom.payment_expiry', 24 * 60 * 10);

        $this->validateConfig();
    }

    public function getVendorId(): string { return $this->vendorId; }
    public function getVendorPin(): string { return $this->vendorPin; }
    public function getApiKey(): string { return $this->apiKey; }
    public function getApiSecret(): string { return $this->apiSecret; }
    public function getPaymentExpiry(): int { return $this->paymentExpiry; }

    // Existing methods
    public function getCheckoutEndpoint(): string { return self::ENDPOINTS['checkout']; }
    public function getWalletEndpoint(): string { return self::ENDPOINTS['wallet']; }
    public function getOrdersEndpoint(): string { return self::ENDPOINTS['orders']; }
    public function getCallbackEndpoint(): string { return self::ENDPOINTS['callback']; }
    public function getCancelEndpoint(): string { return self::ENDPOINTS['cancel']; }

    // New methods for financial operations
    public function getWalletCashinEndpoint(): string { return self::ENDPOINTS['wallet_cashin']; }
    public function getWalletLookupEndpoint(): string { return self::ENDPOINTS['wallet_lookup']; }
    public function getWalletQueryEndpoint(): string { return self::ENDPOINTS['wallet_query']; }
    public function getSelcompesaCashinEndpoint(): string { return self::ENDPOINTS['selcompesa_cashin']; }
    public function getSelcompesaLookupEndpoint(): string { return self::ENDPOINTS['selcompesa_lookup']; }
    public function getSelcompesaQueryEndpoint(): string { return self::ENDPOINTS['selcompesa_query']; }

    public function isAllowedRecipient(string $recipient): bool
    {
        return in_array($recipient, $this->allowedRecipients, true);
    }

    private function validateConfig(): void
    {
        if (empty($this->apiKey) || empty($this->apiSecret)) {
            throw new InvalidArgumentException('Selcom API credentials are required');
        }

        if (empty($this->vendorPin)) {
            throw new InvalidArgumentException('Vendor PIN is required for disbursements');
        }

        // if (empty($this->allowedRecipients)) {
        //     throw new InvalidArgumentException('At least one allowed recipient must be configured');
        // }
    }
}

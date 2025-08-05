<?php

namespace App\Services\Payment\Selcom;

use App\Models\Fundi;
use App\Models\Transaction;
use App\Exceptions\SelcomException;

/**
 * Extended Selcom Service for Financial Operations
 */
class FinancialSelcomService extends SelcomPaymentService
{
    /**
     * Process cashin payment to fundi
     */
    public function processCashin(Transaction $transaction): PaymentResponse
    {
        $fundi = $transaction->fundi;
        $mobileNumber = $fundi->primary_phone;

        // Determine wallet type and create appropriate request
        $walletType = $this->determineWalletType($mobileNumber);

        if ($walletType === 'selcompesa') {
            return $this->processSelcompesaCashin($transaction, $mobileNumber);
        } else {
            return $this->processWalletCashin($transaction, $mobileNumber, $walletType);
        }
    }

    /**
     * Process wallet cashin
     */
    private function processWalletCashin(Transaction $transaction, string $mobileNumber, string $walletType): PaymentResponse
    {
        $request = new WalletCashinRequest(
            transactionId: $transaction->transaction_reference,
            utilityCode: $walletType,
            utilityRef: $mobileNumber,
            amount: $transaction->amount,
            vendor: $this->config->getVendorId(),
            pin: $this->config->getVendorPin(),
            msisdn: $mobileNumber
        );

        $response = $this->makeRequest('POST', $this->config->getWalletCashinEndpoint(), $request->toArray());
        return PaymentResponse::fromArray($response);
    }

    /**
     * Process Selcompesa cashin
     */
    private function processSelcompesaCashin(Transaction $transaction, string $mobileNumber): PaymentResponse
    {
        $request = new SelcompesaCashinRequest(
            transactionId: $transaction->transaction_reference,
            utilityRef: $mobileNumber,
            amount: $transaction->amount,
            vendor: $this->config->getVendorId(),
            pin: $this->config->getVendorPin(),
            msisdn: $mobileNumber
        );

        $response = $this->makeRequest('POST', $this->config->getSelcompesaCashinEndpoint(), $request->toArray());
        return PaymentResponse::fromArray($response);
    }

    /**
     * Lookup name for wallet cashin
     */
    public function lookupWalletName(string $utilityCode, string $utilityRef, string $transactionId): array
    {
        $params = [
            'utilitycode' => $utilityCode,
            'utilityref' => $utilityRef,
            'transid' => $transactionId,
        ];

        $url = $this->config->getWalletLookupEndpoint() . '?' . http_build_query($params);
        return $this->makeRequest('GET', $url);
    }

    /**
     * Query transaction status
     */
    public function queryTransactionStatus(string $transactionId, string $type = 'wallet'): array
    {
        $endpoint = $type === 'selcompesa'
            ? $this->config->getSelcompesaQueryEndpoint()
            : $this->config->getWalletQueryEndpoint();

        $url = $endpoint . '?transid=' . $transactionId;
        return $this->makeRequest('GET', $url);
    }

    /**
     * Determine wallet type based on mobile number
     */
    private function determineWalletType(string $mobileNumber): string
    {
        // Clean the mobile number
        $number = preg_replace('/^(\+255|255|0)/', '', $mobileNumber);
        $prefix = substr($number, 0, 3);

        return match ($prefix) {
            '076', '077' => 'VMCASHIN', // Vodacom M-Pesa
            '068', '069' => 'AMCASHIN', // Airtel Money
            '065' => 'TPCASHIN',        // Mixx by Yas
            '077' => 'EZCASHIN',        // EzyPesa (overlaps with Vodacom, handle carefully)
            '062' => 'HPCASHIN',        // HaloPesa
            '073' => 'TTCASHIN',        // TTCL Pesa
            default => 'CASHIN',        // Auto-route
        };
    }

    /**
     * Validate fundi for disbursement
     */
    public function validateFundiForDisbursement(Fundi $fundi): array
    {
        $validation = [
            'approved' => $fundi->approved_at !== null,
            'has_mobile' => !empty($fundi->primary_phone),
            'mobile_valid' => $this->validateMobileNumber($fundi->primary_phone),
            'wallet_supported' => $this->isWalletSupported($fundi->primary_phone),
        ];

        return [
            'valid' => array_reduce($validation, fn($carry, $item) => $carry && $item, true),
            'validation' => $validation,
            'wallet_type' => $this->determineWalletType($fundi->primary_phone ?? ''),
        ];
    }

    /**
     * Validate Tanzanian mobile number
     */
    private function validateMobileNumber(?string $mobileNumber): bool
    {
        if (empty($mobileNumber)) {
            return false;
        }

        return preg_match('/^(\+255|255|0)(6[0-9]|7[0-9])[0-9]{7}$/', $mobileNumber);
    }

    /**
     * Check if wallet is supported
     */
    private function isWalletSupported(?string $mobileNumber): bool
    {
        if (empty($mobileNumber)) {
            return false;
        }

        $number = preg_replace('/^(\+255|255|0)/', '', $mobileNumber);
        $prefix = substr($number, 0, 3);

        $supportedPrefixes = ['076', '077', '068', '069', '065', '062', '073'];
        return in_array($prefix, $supportedPrefixes);
    }
}

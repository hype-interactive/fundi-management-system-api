<?php

namespace App\Services\Payment\Selcom;

/**
 * Wallet Cashin Request for disbursements
 */
class WalletCashinRequest extends PaymentRequest
{
    private string $utilityCode;
    private string $utilityRef;
    private string $vendor;
    private string $pin;
    private ?string $msisdn;

    public function __construct(
        string $transactionId,
        string $utilityCode,
        string $utilityRef,
        float $amount,
        string $vendor,
        string $pin,
        string $msisdn = null,
        string $currency = 'TZS'
    ) {
        parent::__construct($utilityRef, $amount, $currency, $transactionId);
        $this->utilityCode = $utilityCode;
        $this->utilityRef = $utilityRef;
        $this->vendor = $vendor;
        $this->pin = $pin;
        $this->msisdn = $msisdn;
    }

    public function toArray(): array
    {
        return [
            'transid' => $this->reference,
            'utilitycode' => $this->utilityCode,
            'utilityref' => $this->utilityRef,
            'amount' => $this->amount,
            'vendor' => $this->vendor,
            'pin' => $this->pin,
            'msisdn' => $this->msisdn,
        ];
    }
}

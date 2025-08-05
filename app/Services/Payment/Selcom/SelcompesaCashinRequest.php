<?php

namespace App\Services\Payment\Selcom;

/**
 * Selcompesa Cashin Request for disbursements
 */
class SelcompesaCashinRequest extends PaymentRequest
{
    private string $vendor;
    private string $pin;
    private ?string $msisdn;

    public function __construct(
        string $transactionId,
        string $utilityRef,
        float $amount,
        string $vendor,
        string $pin,
        string $msisdn = null,
        string $currency = 'TZS'
    ) {
        parent::__construct($utilityRef, $amount, $currency, $transactionId);
        $this->vendor = $vendor;
        $this->pin = $pin;
        $this->msisdn = $msisdn;
    }

    public function toArray(): array
    {
        return [
            'transid' => $this->reference,
            'utilityref' => $this->recipient,
            'utilitycode' => 'SPSCASHIN',
            'amount' => $this->amount,
            'vendor' => $this->vendor,
            'pin' => $this->pin,
            'msisdn' => $this->msisdn,
        ];
    }
}

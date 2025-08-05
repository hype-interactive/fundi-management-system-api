<?php

namespace App\Services\Payment\Selcom;

/**
 * Wallet payment request
 */
class WalletPaymentRequest extends PaymentRequest
{
    private string $walletId;

    public function __construct(string $recipient, float $amount, string $walletId, string $currency = 'TZS')
    {
        parent::__construct($recipient, $amount, $currency);
        $this->walletId = $walletId;
    }

    public function toArray(): array
    {
        return [
            'vendor' => (new SelcomConfig())->getVendorId(),
            'order_id' => $this->reference,
            'msisdn' => $this->recipient,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'wallet' => $this->walletId,
        ];
    }
}

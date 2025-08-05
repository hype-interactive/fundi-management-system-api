<?php

namespace App\Services\Payment\Selcom;

/**
 * Checkout payment request
 */
class CheckoutPaymentRequest extends PaymentRequest
{
    private string $description;
    private string $callbackUrl;

    public function __construct(
        string $recipient,
        float $amount,
        string $description,
        string $callbackUrl = null,
        string $currency = 'TZS'
    ) {
        parent::__construct($recipient, $amount, $currency);
        $this->description = $description;
        $this->callbackUrl = $callbackUrl ?? (new SelcomConfig())->getCallbackEndpoint();
    }

    public function toArray(): array
    {
        return [
            'vendor' => (new SelcomConfig())->getVendorId(),
            'order_id' => $this->reference,
            'buyer_email' => $this->recipient,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'buyer_name' => $this->recipient,
            'buyer_phone' => $this->recipient,
            'description' => $this->description,
            'webhook' => $this->callbackUrl,
            'redirect_url' => (new SelcomConfig())->getCancelEndpoint(),
        ];
    }
}

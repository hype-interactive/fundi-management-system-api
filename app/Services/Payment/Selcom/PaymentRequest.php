<?php

namespace App\Services\Payment\Selcom;

/**
 * Base payment request class
 */
abstract class PaymentRequest
{
    protected string $recipient;
    protected float $amount;
    protected string $currency;
    protected string $reference;

    public function __construct(string $recipient, float $amount, string $currency = 'TZS', string $reference = null)
    {
        $this->recipient = $recipient;
        $this->amount = $amount;
        $this->currency = $currency;
        $this->reference = $reference ?? $this->generateReference();
    }

    public function getRecipient(): string { return $this->recipient; }
    public function getAmount(): float { return $this->amount; }
    public function getCurrency(): string { return $this->currency; }
    public function getReference(): string { return $this->reference; }

    abstract public function toArray(): array;

    protected function generateReference(): string
    {
        return 'PAY_' . strtoupper(uniqid());
    }
}

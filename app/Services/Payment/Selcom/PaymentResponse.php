<?php

namespace App\Services\Payment\Selcom;

/**
 * Payment response wrapper
 */
class PaymentResponse
{
    private array $data;
    private bool $success;
    private string $message;

    private function __construct(array $data, bool $success, string $message = '')
    {
        $this->data = $data;
        $this->success = $success;
        $this->message = $message;
    }

    public static function fromArray(array $response): self
    {
        $success = isset($response['result']) && $response['result'] === 'SUCCESS';
        $message = $response['message'] ?? ($success ? 'Payment processed successfully' : 'Payment failed');

        return new self($response, $success, $message);
    }

    public function isSuccessful(): bool { return $this->success; }
    public function getMessage(): string { return $this->message; }
    public function getData(): array { return $this->data; }
    public function getTransactionId(): ?string { return $this->data['transid'] ?? null; }
    public function getReference(): ?string { return $this->data['reference'] ?? null; }
}

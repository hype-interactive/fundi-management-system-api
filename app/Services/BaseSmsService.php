<?php

namespace App\Services;

abstract class BaseSmsService
{
    protected $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    abstract public function sendSms(string $phoneNumber, string $message, string|null $campaignId = null): array;
    abstract public function checkDeliveryStatus(string $messageId): array;
    abstract public function validateConfig(): bool;
}

<?php

namespace App\Services;

abstract class BaseSmsService
{
    protected $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Send SMS to a single recipient
     */
    abstract public function sendSms(string $phoneNumber, string $message, ?string $campaignId = null): array;

    /**
     * Check delivery status of a message
     */
    abstract public function checkDeliveryStatus(string $messageId): array;

    /**
     * Validate provider configuration
     */
    abstract public function validateConfig(): bool;

    /**
     * Format phone number according to provider requirements
     */
    abstract protected function formatPhoneNumber(string $phoneNumber): string;

    /**
     * Get provider name
     */
    public function getProviderName(): string
    {
        return class_basename(static::class);
    }

    /**
     * Get provider configuration
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Check if provider supports a specific feature
     */
    public function supportsFeature(string $feature): bool
    {
        return method_exists($this, $feature);
    }
}

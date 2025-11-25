<?php

namespace App\Exceptions;

use Exception;

/**
 * Custom exception for Selcom-related errors with enhanced failure categorization
 */
class SelcomException extends Exception
{
    private ?string $failureReason;

    // Failure reason constants
    public const REASON_AUTHENTICATION = 'AUTHENTICATION';
    public const REASON_TIMEOUT = 'TIMEOUT';
    public const REASON_NETWORK = 'NETWORK';
    public const REASON_INSUFFICIENT_BALANCE = 'INSUFFICIENT_BALANCE';
    public const REASON_INVALID_RECIPIENT = 'INVALID_RECIPIENT';
    public const REASON_INVALID_AMOUNT = 'INVALID_AMOUNT';
    public const REASON_DUPLICATE_TRANSACTION = 'DUPLICATE_TRANSACTION';
    public const REASON_REJECTED = 'REJECTED';
    public const REASON_CONFIGURATION = 'CONFIGURATION';
    public const REASON_UNKNOWN = 'UNKNOWN';

    public function __construct(string $message, int $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->failureReason = $this->categorizeFailure($code, $message);
    }

    public function getFailureReason(): string
    {
        return $this->failureReason;
    }

    /**
     * Categorize failure based on error code and message
     */
    private function categorizeFailure(int $httpCode, string $error): string
    {
        $errorLower = strtolower($error);

        // HTTP status code based
        if (in_array($httpCode, [401, 403])) {
            return self::REASON_AUTHENTICATION;
        }

        if ($httpCode === 408) {
            return self::REASON_TIMEOUT;
        }

        // Message content based
        if (stripos($errorLower, 'timeout') !== false) {
            return self::REASON_TIMEOUT;
        }

        if (stripos($errorLower, 'insufficient') !== false
            || stripos($errorLower, 'balance') !== false) {
            return self::REASON_INSUFFICIENT_BALANCE;
        }

        if (stripos($errorLower, 'invalid recipient') !== false
            || stripos($errorLower, 'invalid phone') !== false
            || stripos($errorLower, 'invalid msisdn') !== false) {
            return self::REASON_INVALID_RECIPIENT;
        }

        if (stripos($errorLower, 'invalid amount') !== false
            || stripos($errorLower, 'amount') !== false) {
            return self::REASON_INVALID_AMOUNT;
        }

        if (stripos($errorLower, 'duplicate') !== false
            || stripos($errorLower, 'already exists') !== false) {
            return self::REASON_DUPLICATE_TRANSACTION;
        }

        if (stripos($errorLower, 'rejected') !== false
            || stripos($errorLower, 'declined') !== false) {
            return self::REASON_REJECTED;
        }

        if (stripos($errorLower, 'not configured') !== false
            || stripos($errorLower, 'configuration') !== false) {
            return self::REASON_CONFIGURATION;
        }

        if (stripos($errorLower, 'network') !== false
            || stripos($errorLower, 'connection') !== false
            || in_array($httpCode, [500, 502, 503, 504])) {
            return self::REASON_NETWORK;
        }

        return self::REASON_UNKNOWN;
    }

    /**
     * Check if error is retryable
     */
    public function isRetryable(): bool
    {
        return in_array($this->failureReason, [
            self::REASON_TIMEOUT,
            self::REASON_NETWORK,
            self::REASON_UNKNOWN,
        ]);
    }

    /**
     * Get user-friendly error message
     */
    public function getUserMessage(): string
    {
        return match($this->failureReason) {
            self::REASON_AUTHENTICATION => 'Payment service authentication failed. Please contact support.',
            self::REASON_TIMEOUT => 'Request timed out. Please try again.',
            self::REASON_NETWORK => 'Network error occurred. Please try again.',
            self::REASON_INSUFFICIENT_BALANCE => 'Insufficient balance in payment account.',
            self::REASON_INVALID_RECIPIENT => 'Invalid recipient phone number.',
            self::REASON_INVALID_AMOUNT => 'Invalid transaction amount.',
            self::REASON_DUPLICATE_TRANSACTION => 'Duplicate transaction detected.',
            self::REASON_REJECTED => 'Transaction was rejected by the payment provider.',
            self::REASON_CONFIGURATION => 'Payment system is not properly configured. Please contact support.',
            default => 'An error occurred while processing the payment. Please try again or contact support.',
        };
    }
}

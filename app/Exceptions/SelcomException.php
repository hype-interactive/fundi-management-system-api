<?php

namespace App\Exceptions;

use Exception;

/**
 * Custom exception for Selcom-related errors
 */
class SelcomException extends Exception
{
    private ?string $failureReason;

    public function __construct(string $message, int $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->failureReason = $this->guessFailureReason($code, $message);
    }

    public function getFailureReason(): ?string
    {
        return $this->failureReason;
    }

    private function guessFailureReason(int $httpCode, string $error): string
    {
        if (in_array($httpCode, [401, 403])) {
            return 'AUTHENTICATION';
        }

        if ($httpCode === 408 || stripos($error, 'timeout') !== false) {
            return 'TIMEOUT';
        }

        return 'NETWORK';
    }
}

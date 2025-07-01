<?php

// ===========================================
// File: app/Traits/HasAlerts.php
// ===========================================

namespace App\Traits;

use Illuminate\Support\Facades\Log;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;

trait HasAlerts
{
    /**
     * Show success alert
     */
    public function showSuccessAlert(string $title, string $message)
    {
        LivewireAlert::title($title)
            ->text($message)
            ->success()
            ->show();
    }

    /**
     * Show error alert
     */
    public function showErrorAlert(string $title, string $message)
    {
        LivewireAlert::title($title)
            ->text($message)
            ->error()
            ->show();
    }

    /**
     * Show warning alert
     */
    public function showWarningAlert(string $title, string $message)
    {
        LivewireAlert::title($title)
            ->text($message)
            ->warning()
            ->show();
    }

    /**
     * Show info alert
     */
    public function showInfoAlert(string $title, string $message)
    {
        LivewireAlert::title($title)
            ->text($message)
            ->info()
            ->show();
    }

    /**
     * Flash message to session for redirect scenarios
     */
    public function flashAlert(string $type, string $title, string $message)
    {
        session()->flash('alert', [
            'type' => $type,
            'title' => $title,
            'message' => $message
        ]);
    }

    /**
     * Flash success message
     */
    public function flashSuccess(string $title, string $message)
    {
        $this->flashAlert('success', $title, $message);
    }

    /**
     * Flash error message
     */
    public function flashError(string $title, string $message)
    {
        $this->flashAlert('error', $title, $message);
    }

    /**
     * Flash warning message
     */
    public function flashWarning(string $title, string $message)
    {
        $this->flashAlert('warning', $title, $message);
    }

    /**
     * Flash info message
     */
    public function flashInfo(string $title, string $message)
    {
        $this->flashAlert('info', $title, $message);
    }

    /**
     * Check and display flashed alerts (call this in mount() method)
     */
    public function displayFlashedAlerts()
    {
        // Check for new alert format
        if (session()->has('alert')) {
            $alert = session('alert');

            switch ($alert['type']) {
                case 'success':
                    $this->showSuccessAlert($alert['title'], $alert['message']);
                    break;
                case 'error':
                    $this->showErrorAlert($alert['title'], $alert['message']);
                    break;
                case 'warning':
                    $this->showWarningAlert($alert['title'], $alert['message']);
                    break;
                case 'info':
                    $this->showInfoAlert($alert['title'], $alert['message']);
                    break;
            }
        }

        // Check for legacy 'saved' flash messages (for backward compatibility)
        if (session()->has('saved')) {
            $saved = session('saved');
            if ($saved['title'] === 'Error') {
                $this->showErrorAlert($saved['title'], $saved['text']);
            } else {
                $this->showSuccessAlert($saved['title'], $saved['text']);
            }
        }

        // Check for simple flash messages
        if (session()->has('success')) {
            $this->showSuccessAlert('Success', session('success'));
        }

        if (session()->has('error')) {
            $this->showErrorAlert('Error', session('error'));
        }

        if (session()->has('message')) {
            $this->showSuccessAlert('Success', session('message'));
        }
    }

    /**
     * Show confirmation dialog and execute callback
     */
    public function showConfirmation(string $title, string $message, string $method, array $params = [])
    {
        LivewireAlert::confirm([
            'title' => $title,
            'text' => $message,
            'icon' => 'question',
            'showCancelButton' => true,
            'confirmButtonText' => 'Yes',
            'cancelButtonText' => 'Cancel',
            'onConfirmed' => [
                'method' => $method,
                'params' => $params
            ]
        ]);
    }

    /**
     * Show loading alert
     */
    public function showLoadingAlert(string $title, string $message = 'Please wait...')
    {
        LivewireAlert::title($title)
            ->text($message)
            ->info()
            ->show();
    }

    /**
     * Handle operation with alerts (success/error handling)
     */
    public function handleOperation(callable $operation, string $successTitle, string $successMessage, string $errorTitle = 'Error', string $errorMessage = 'An error occurred. Please try again.')
    {
        try {
            $result = $operation();

            if ($result) {
                $this->showSuccessAlert($successTitle, $successMessage);
                return true;
            } else {
                $this->showErrorAlert($errorTitle, $errorMessage);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Operation failed: ' . $e->getMessage());
            $this->showErrorAlert($errorTitle, $errorMessage);
            return false;
        }
    }

    /**
     * Handle operation with flash (for redirects)
     */
    public function handleOperationWithFlash(callable $operation, string $successTitle, string $successMessage, string $errorTitle = 'Error', string $errorMessage = 'An error occurred. Please try again.')
    {
        try {
            $result = $operation();

            if ($result) {
                $this->flashSuccess($successTitle, $successMessage);
                return true;
            } else {
                $this->flashError($errorTitle, $errorMessage);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Operation failed: ' . $e->getMessage());
            $this->flashError($errorTitle, $errorMessage);
            return false;
        }
    }
}

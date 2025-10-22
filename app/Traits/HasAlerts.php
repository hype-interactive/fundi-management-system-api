<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;

trait HasAlerts
{
    /**
     * Show success alert
     */
    public function showSuccessAlert(string $title, string $message, int $timer = 3000)
    {
        LivewireAlert::title($title)
            ->text($message)
            ->success()
            ->timer($timer)
            ->show();
    }

    /**
     * Show error alert
     */
    public function showErrorAlert(string $title, string $message, int $timer = 3000)
    {
        LivewireAlert::title($title)
            ->text($message)
            ->error()
            ->timer($timer)
            ->show();
    }

    /**
     * Show warning alert
     */
    public function showWarningAlert(string $title, string $message, int $timer = 3000)
    {
        LivewireAlert::title($title)
            ->text($message)
            ->warning()
            ->timer($timer)
            ->show();
    }

    /**
     * Show info alert
     */
    public function showInfoAlert(string $title, string $message, int $timer = 3000)
    {
        LivewireAlert::title($title)
            ->text($message)
            ->info()
            ->timer($timer)
            ->show();
    }

    /**
     * Show success toast notification
     */
    public function showSuccessToast(string $title, string $message = '', int $timer = 3000)
    {
        LivewireAlert::title($title)
            ->text($message)
            ->success()
            ->toast()
            ->position('top-end')
            ->timer($timer)
            ->show();
    }

    /**
     * Show error toast notification
     */
    public function showErrorToast(string $title, string $message = '', int $timer = 3000)
    {
        LivewireAlert::title($title)
            ->text($message)
            ->error()
            ->toast()
            ->position('top-end')
            ->timer($timer)
            ->show();
    }

    /**
     * Show warning toast notification
     */
    public function showWarningToast(string $title, string $message = '', int $timer = 3000)
    {
        LivewireAlert::title($title)
            ->text($message)
            ->warning()
            ->toast()
            ->position('top-end')
            ->timer($timer)
            ->show();
    }

    /**
     * Show info toast notification
     */
    public function showInfoToast(string $title, string $message = '', int $timer = 3000)
    {
        LivewireAlert::title($title)
            ->text($message)
            ->info()
            ->toast()
            ->position('top-end')
            ->timer($timer)
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
     * Show confirmation dialog using the new fluent API
     *
     * @param string $title Dialog title
     * @param string $message Dialog message/text
     * @param string $confirmMethod Method to call on confirmation
     * @param array $confirmParams Parameters to pass to confirm method
     * @param string $denyMethod Optional method to call on deny
     * @param array $denyParams Parameters to pass to deny method
     * @param string $confirmButtonText Text for confirm button (default: 'Yes, proceed')
     * @param string $cancelButtonText Text for cancel button (default: 'Cancel')
     * @param string $denyButtonText Text for deny button (optional)
     */
    public function showConfirmation(
        string $title,
        string $message,
        string $confirmMethod,
        array $confirmParams = [],
        string $denyMethod = '',
        array $denyParams = [],
        string $confirmButtonText = 'Yes, proceed',
        string $cancelButtonText = 'Cancel',
        string $denyButtonText = ''
    ) {
        $alert = LivewireAlert::title($title)
            ->text($message)
            ->question()
            ->position('center')
            ->withConfirmButton($confirmButtonText)
            ->confirmButtonColor('#3085d6')
            ->withCancelButton($cancelButtonText)
            ->cancelButtonColor('#d33')
            ->onConfirm($confirmMethod, $confirmParams);

        // Add deny button if method is provided
        if (!empty($denyMethod)) {
            $alert->withDenyButton($denyButtonText ?: 'No')
                ->denyButtonColor('#6c757d')
                ->onDeny($denyMethod, $denyParams);
        }

        $alert->show();
    }

    /**
     * Show delete confirmation dialog (preset for delete operations)
     */
    public function showDeleteConfirmation(
        string $confirmMethod,
        array $confirmParams = [],
        string $title = 'Are you sure?',
        string $message = 'This action cannot be undone.'
    ) {
        LivewireAlert::title($title)
            ->text($message)
            ->warning()
            ->position('center')
            ->withConfirmButton('Yes, delete it')
            ->confirmButtonColor('#d33')
            ->withCancelButton('Cancel')
            ->cancelButtonColor('#3085d6')
            ->onConfirm($confirmMethod, $confirmParams)
            ->show();
    }

    /**
     * Show bulk delete confirmation dialog
     */
    public function showBulkDeleteConfirmation(
        int $count,
        string $confirmMethod,
        array $confirmParams = []
    ) {
        LivewireAlert::title('Delete Multiple Items?')
            ->text("You are about to permanently delete {$count} item(s). This action cannot be undone.")
            ->warning()
            ->position('center')
            ->withConfirmButton("Yes, delete {$count} item(s)")
            ->confirmButtonColor('#d33')
            ->withCancelButton('Cancel')
            ->cancelButtonColor('#3085d6')
            ->onConfirm($confirmMethod, $confirmParams)
            ->show();
    }

    /**
     * Show simple confirm dialog using asConfirm()
     */
    public function showSimpleConfirm(
        string $title,
        string $message,
        string $confirmMethod,
        array $confirmParams = [],
        string $denyMethod = '',
        array $denyParams = []
    ) {
        $alert = LivewireAlert::title($title)
            ->text($message)
            ->asConfirm()
            ->onConfirm($confirmMethod, $confirmParams);

        if (!empty($denyMethod)) {
            $alert->onDeny($denyMethod, $denyParams);
        }

        $alert->show();
    }

    /**
     * Show loading alert (no auto-dismiss)
     */
    public function showLoadingAlert(string $title, string $message = 'Please wait...')
    {
        LivewireAlert::title($title)
            ->text($message)
            ->info()
            ->timer(0) // No auto-dismiss
            ->show();
    }

    /**
     * Show loading toast
     */
    public function showLoadingToast(string $title, string $message = 'Processing...')
    {
        LivewireAlert::title($title)
            ->text($message)
            ->info()
            ->toast()
            ->position('top-end')
            ->timer(0) // No auto-dismiss
            ->show();
    }

    /**
     * Handle operation with alerts (success/error handling)
     */
    public function handleOperation(
        callable $operation,
        string $successTitle,
        string $successMessage,
        string $errorTitle = 'Error',
        string $errorMessage = 'An error occurred. Please try again.',
        bool $useToast = false
    ) {
        try {
            $result = $operation();

            if ($result) {
                if ($useToast) {
                    $this->showSuccessToast($successTitle, $successMessage);
                } else {
                    $this->showSuccessAlert($successTitle, $successMessage);
                }
                return true;
            } else {
                if ($useToast) {
                    $this->showErrorToast($errorTitle, $errorMessage);
                } else {
                    $this->showErrorAlert($errorTitle, $errorMessage);
                }
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Operation failed: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);

            if ($useToast) {
                $this->showErrorToast($errorTitle, $errorMessage);
            } else {
                $this->showErrorAlert($errorTitle, $errorMessage);
            }
            return false;
        }
    }

    /**
     * Handle operation with flash (for redirects)
     */
    public function handleOperationWithFlash(
        callable $operation,
        string $successTitle,
        string $successMessage,
        string $errorTitle = 'Error',
        string $errorMessage = 'An error occurred. Please try again.'
    ) {
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
            Log::error('Operation failed: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            $this->flashError($errorTitle, $errorMessage);
            return false;
        }
    }

    /**
     * Show three-option dialog (Save, Delete, Cancel)
     */
    public function showThreeOptionDialog(
        string $title,
        string $message,
        string $confirmMethod,
        array $confirmParams = [],
        string $denyMethod = '',
        array $denyParams = [],
        string $dismissMethod = '',
        array $dismissParams = [],
        string $confirmText = 'Save',
        string $denyText = 'Delete',
        string $cancelText = 'Cancel'
    ) {
        $alert = LivewireAlert::title($title)
            ->text($message)
            ->question()
            ->position('center')
            ->withConfirmButton($confirmText)
            ->confirmButtonColor('#28a745')
            ->withDenyButton($denyText)
            ->denyButtonColor('#dc3545')
            ->withCancelButton($cancelText)
            ->cancelButtonColor('#6c757d')
            ->onConfirm($confirmMethod, $confirmParams);

        if (!empty($denyMethod)) {
            $alert->onDeny($denyMethod, $denyParams);
        }

        if (!empty($dismissMethod)) {
            $alert->onDismiss($dismissMethod, $dismissParams);
        }

        $alert->show();
    }
}

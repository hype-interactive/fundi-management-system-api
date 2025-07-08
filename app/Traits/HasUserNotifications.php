<?php

namespace App\Traits;

use App\Models\User;
use App\Services\UserNotificationService;
use Illuminate\Support\Facades\Log;

trait HasUserNotifications
{
    protected UserNotificationService $userNotificationService;

    /**
     * Initialize the notification service
     */
    public function bootUserNotifications(UserNotificationService $userNotificationService)
    {
        $this->userNotificationService = $userNotificationService;
    }

    /**
     * Send login credentials to a user
     */
    protected function sendUserLoginCredentials(User $user, string $password): array
    {
        return $this->userNotificationService->sendLoginCredentials($user, $password);
    }

    /**
     * Send password reset notification to a user
     */
    protected function sendUserPasswordReset(User $user, string $newPassword): array
    {
        return $this->userNotificationService->sendPasswordResetNotification($user, $newPassword);
    }

    /**
     * Send account activation notification
     */
    protected function sendUserActivationNotification(User $user): array
    {
        return $this->userNotificationService->sendAccountActivationNotification($user);
    }

    /**
     * Handle notification results and flash appropriate messages
     */
    protected function handleNotificationResults(array $results, string $actionType = 'notification'): void
    {
        $smsSuccess = $results['sms']['success'] ?? false;
        $emailSuccess = $results['email']['success'] ?? false;

        if ($smsSuccess && $emailSuccess) {
            session()->flash('message', ucfirst($actionType) . ' sent successfully via SMS and email.');
        } elseif ($smsSuccess) {
            session()->flash('message', ucfirst($actionType) . ' sent successfully via SMS. Email delivery failed.');
        } elseif ($emailSuccess) {
            session()->flash('message', ucfirst($actionType) . ' sent successfully via email. SMS delivery failed.');
        } else {
            session()->flash('error', 'Failed to send ' . $actionType . ' via both SMS and email.');
        }
    }

    /**
     * Send notification with automatic result handling
     */
    protected function sendNotificationWithFeedback(User $user, string $password, string $type = 'login_credentials'): bool
    {
        try {
            $results = match ($type) {
                'login_credentials' => $this->sendUserLoginCredentials($user, $password),
                'password_reset' => $this->sendUserPasswordReset($user, $password),
                'activation' => $this->sendUserActivationNotification($user),
                default => ['sms' => ['success' => false], 'email' => ['success' => false]]
            };

            $this->handleNotificationResults($results, $type);

            // Return true if at least one method succeeded
            return ($results['sms']['success'] ?? false) || ($results['email']['success'] ?? false);

        } catch (\Exception $e) {
            Log::error("Failed to send {$type} notification to user {$user->id}: " . $e->getMessage());
            session()->flash('error', "Failed to send {$type} notification due to system error.");
            return false;
        }
    }
}

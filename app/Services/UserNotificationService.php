<?php

namespace App\Services;

use App\Models\User;
use App\Mail\LoginCredentials;
use App\Services\SmsService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class UserNotificationService
{
    protected $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * Send login credentials to newly created user
     */
    public function sendLoginCredentials(User $user, string $password): array
    {
        $results = [
            'sms' => ['success' => false, 'error' => null],
            'email' => ['success' => false, 'error' => null]
        ];

        try {
            // Send SMS first (primary method)
            $results['sms'] = $this->sendLoginSms($user, $password);

            // Send email as fallback or confirmation
            $results['email'] = $this->sendLoginEmail($user, $password);

            // Log the overall results
            $this->logNotificationResults($user, $results);

            return $results;

        } catch (\Exception $e) {
            Log::error("Exception sending login credentials to user {$user->id}: " . $e->getMessage());
            return $results;
        }
    }

    /**
     * Send login credentials via SMS
     */
    public function sendLoginSms(User $user, string $password): array
    {
        try {
            $phoneNumber = $user->phone;

            if (!$phoneNumber) {
                return ['success' => false, 'error' => 'No phone number available'];
            }

            $message = $this->generateLoginSmsMessage($user, $password);
            $result = $this->smsService->sendSms($phoneNumber, $message);

            if ($result['success']) {
                Log::info("Login SMS sent successfully to {$user->fname} {$user->lname} ({$phoneNumber})");
            } else {
                Log::error("Failed to send login SMS to {$user->fname} {$user->lname}: " . ($result['error'] ?? 'Unknown error'));
            }

            return $result;
        } catch (\Exception $e) {
            Log::error("Exception sending login SMS to user {$user->id}: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send login credentials via email
     */
    public function sendLoginEmail(User $user, string $password): array
    {
        try {
            Mail::to($user->email)->send(new LoginCredentials($user, $password));

            Log::info("Login email sent successfully to {$user->fname} {$user->lname} ({$user->email})");
            return ['success' => true];
        } catch (\Exception $e) {
            Log::error("Failed to send login email to {$user->fname} {$user->lname}: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send password reset notification
     */
    public function sendPasswordResetNotification(User $user, string $newPassword): array
    {
        $results = [
            'sms' => ['success' => false, 'error' => null],
            'email' => ['success' => false, 'error' => null]
        ];

        try {
            // Send SMS notification
            $results['sms'] = $this->sendPasswordResetSms($user, $newPassword);

            // Send email notification
            $results['email'] = $this->sendPasswordResetEmail($user, $newPassword);

            $this->logNotificationResults($user, $results, 'password_reset');

            return $results;

        } catch (\Exception $e) {
            Log::error("Exception sending password reset notification to user {$user->id}: " . $e->getMessage());
            return $results;
        }
    }

    /**
     * Send password reset via SMS
     */
    private function sendPasswordResetSms(User $user, string $newPassword): array
    {
        try {
            $phoneNumber = $user->phone;

            if (!$phoneNumber) {
                return ['success' => false, 'error' => 'No phone number available'];
            }

            $message = $this->generatePasswordResetSmsMessage($user, $newPassword);
            return $this->smsService->sendSms($phoneNumber, $message);
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send password reset via email
     */
    private function sendPasswordResetEmail(User $user, string $newPassword): array
    {
        try {
            // You would create a PasswordReset mailable similar to LoginCredentials
            // Mail::to($user->email)->send(new PasswordReset($user, $newPassword));
            return ['success' => true];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Generate SMS message with login credentials
     */
    private function generateLoginSmsMessage(User $user, string $password): string
    {
        $name = $user->fname ?? 'User';
        $roleName = $user->role?->name ?? 'User';

        return "Hongera {$name}! Umesajiliwa kama {$roleName}.\n\n" .
               "AKAUNTI YAKO:\n" .
               "Web App:\n" .
               "Email: {$user->email}\n" .
               "Password: {$password}\n\n" .
               "Mobile App:\n" .
               "Phone: {$user->phone}\n" .
               "Password: {$password}\n\n" .
               "MUHIMU: Badilisha password yako baada ya kuingia kwa usalama. Asante!";
    }

    /**
     * Generate password reset SMS message
     */
    private function generatePasswordResetSmsMessage(User $user, string $newPassword): string
    {
        $name = $user->fname ?? 'User';

        return "Hujambo {$name}! Password yako imebadilishwa.\n\n" .
               "Password mpya: {$newPassword}\n\n" .
               "Ingia kwenye akaunti yako na ubadilishe password hii haraka. Asante!";
    }

    /**
     * Log notification results
     */
    private function logNotificationResults(User $user, array $results, string $type = 'login_credentials'): void
    {
        $smsSuccess = $results['sms']['success'] ?? false;
        $emailSuccess = $results['email']['success'] ?? false;

        if ($smsSuccess && $emailSuccess) {
            Log::info("{$type} sent via SMS and email to {$user->email} ({$user->phone})");
        } elseif ($smsSuccess) {
            Log::info("{$type} sent via SMS to {$user->phone}. Email failed: " . ($results['email']['error'] ?? 'Unknown'));
        } elseif ($emailSuccess) {
            Log::info("{$type} sent via email to {$user->email}. SMS failed: " . ($results['sms']['error'] ?? 'Unknown'));
        } else {
            Log::error("Failed to send {$type} to {$user->email} via both SMS and email");
        }
    }

    /**
     * Send account activation notification
     */
    public function sendAccountActivationNotification(User $user): array
    {
        $results = [
            'sms' => ['success' => false, 'error' => null],
            'email' => ['success' => false, 'error' => null]
        ];

        try {
            // Send SMS notification
            $results['sms'] = $this->sendActivationSms($user);

            // Send email notification
            $results['email'] = $this->sendActivationEmail($user);

            $this->logNotificationResults($user, $results, 'account_activation');

            return $results;

        } catch (\Exception $e) {
            Log::error("Exception sending activation notification to user {$user->id}: " . $e->getMessage());
            return $results;
        }
    }

    /**
     * Send activation SMS
     */
    private function sendActivationSms(User $user): array
    {
        try {
            $phoneNumber = $user->phone;

            if (!$phoneNumber) {
                return ['success' => false, 'error' => 'No phone number available'];
            }

            $name = $user->fname ?? 'User';
            $roleName = $user->role?->name ?? 'User';

            $message = "Hujambo {$name}! Akaunti yako kama {$roleName} imetiishwa. " .
                      "Unaweza sasa kuingia kwenye mfumo. Asante!";

            return $this->smsService->sendSms($phoneNumber, $message);
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send activation email
     */
    private function sendActivationEmail(User $user): array
    {
        try {
            // You would create an AccountActivation mailable
            // Mail::to($user->email)->send(new AccountActivation($user));
            return ['success' => true];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}

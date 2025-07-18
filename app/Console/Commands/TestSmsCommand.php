<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SmsService;
use App\Services\BeemSmsService;
use App\Services\NextSmsService;

class TestSmsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'sms:test
                            {phones : Comma-separated phone numbers (e.g., 255712345678,255723456789)}
                            {message? : Test message to send}
                            {--provider= : SMS provider to use (beem|nextsms)}
                            {--bulk : Send to all numbers in one batch}
                            {--config-test : Test provider configuration only}
                            {--dry-run : Simulate sending without actually sending SMS}';

    /**
     * The console command description.
     */
    protected $description = 'Test SMS sending functionality with specified phone numbers and providers';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔧 SMS Testing Command Started');
        $this->newLine();

        // Parse phone numbers
        $phonesInput = $this->argument('phones');
        $phoneNumbers = array_map('trim', explode(',', $phonesInput));

        // Validate phone numbers
        $validPhones = [];
        foreach ($phoneNumbers as $phone) {
            if ($this->validatePhoneNumber($phone)) {
                $validPhones[] = $phone;
            } else {
                $this->error("❌ Invalid phone number format: {$phone}");
            }
        }

        if (empty($validPhones)) {
            $this->error('❌ No valid phone numbers provided!');
            return 1;
        }

        $this->info("📱 Valid phone numbers: " . implode(', ', $validPhones));
        $this->newLine();

        // Get message
        $message = $this->argument('message') ?? $this->getDefaultTestMessage();
        $this->info("💬 Message: {$message}");
        $this->newLine();

        // Check if config test only
        if ($this->option('config-test')) {
            return $this->testConfiguration();
        }

        // Check if dry run
        if ($this->option('dry-run')) {
            return $this->performDryRun($validPhones, $message);
        }

        // Determine provider
        $provider = $this->option('provider') ?? config('sms.default_provider');

        if (!in_array($provider, ['monex', 'beem', 'nextsms'])) {
            $this->error("❌ Invalid provider. Use 'beem' or 'nextsms'");
            return 1;
        }

        $this->info("🚀 Using provider: " . strtoupper($provider));
        $this->newLine();

        // Confirm before sending
        if (!$this->confirmSending($validPhones, $message, $provider)) {
            $this->info('📛 SMS sending cancelled.');
            return 0;
        }

        // Send SMS
        if ($this->option('bulk')) {
            return $this->sendBulkSms($validPhones, $message, $provider);
        } else {
            return $this->sendIndividualSms($validPhones, $message, $provider);
        }
    }

    /**
     * Test provider configuration
     */
    private function testConfiguration(): int
    {
        $this->info('🔧 Testing SMS Provider Configurations...');
        $this->newLine();

        $providers = ['beem', 'nextsms'];
        $allValid = true;

        foreach ($providers as $providerName) {
            $this->line("Testing {$providerName} configuration:");

            try {
                $config = config("sms.providers.{$providerName}");

                if (empty($config)) {
                    $this->error("  ❌ No configuration found for {$providerName}");
                    $allValid = false;
                    continue;
                }

                // Create provider instance
                $provider = $this->createProvider($providerName, $config);

                if ($provider->validateConfig()) {
                    $this->info("  ✅ {$providerName} configuration is valid");
                    $this->displayProviderConfig($providerName, $config);
                } else {
                    $this->error("  ❌ {$providerName} configuration is invalid");
                    $this->displayMissingConfig($providerName);
                    $allValid = false;
                }

            } catch (\Exception $e) {
                $this->error("  ❌ Error testing {$providerName}: " . $e->getMessage());
                $allValid = false;
            }

            $this->newLine();
        }

        return $allValid ? 0 : 1;
    }

    /**
     * Perform dry run simulation
     */
    private function performDryRun(array $phoneNumbers, string $message): int
    {
        $this->info('🧪 DRY RUN MODE - No SMS will be sent');
        $this->newLine();

        $provider = $this->option('provider') ?? config('sms.default_provider');

        $this->table(['Parameter', 'Value'], [
            ['Provider', strtoupper($provider)],
            ['Recipients', count($phoneNumbers)],
            ['Phone Numbers', implode(', ', $phoneNumbers)],
            ['Message Length', strlen($message) . ' characters'],
            ['Estimated Cost', $this->estimateCost(count($phoneNumbers))],
            ['Send Method', $this->option('bulk') ? 'Bulk' : 'Individual'],
        ]);

        $this->info('✅ Dry run completed successfully!');
        return 0;
    }

    /**
     * Send SMS to individual numbers
     */
    private function sendIndividualSms(array $phoneNumbers, string $message, string $provider): int
    {
        $this->info('📤 Sending SMS individually...');
        $this->newLine();

        try {
            $smsService = $this->createSmsService($provider);
            $results = [];
            $successCount = 0;
            $failureCount = 0;

            $progressBar = $this->output->createProgressBar(count($phoneNumbers));
            $progressBar->start();

            foreach ($phoneNumbers as $index => $phone) {
                try {
                    $result = $smsService->sendSms($phone, $message, 'test_' . time() . '_' . $index);

                    $results[] = [
                        'phone' => $phone,
                        'status' => $result['success'] ? '✅ Sent' : '❌ Failed',
                        'message_id' => $result['message_id'] ?? 'N/A',
                        'error' => $result['success'] ? '' : ($result['error'] ?? 'Unknown error')
                    ];

                    if ($result['success']) {
                        $successCount++;
                    } else {
                        $failureCount++;
                    }

                    // Small delay between sends
                    usleep(500000); // 500ms

                } catch (\Exception $e) {
                    $results[] = [
                        'phone' => $phone,
                        'status' => '❌ Error',
                        'message_id' => 'N/A',
                        'error' => $e->getMessage()
                    ];
                    $failureCount++;
                }

                $progressBar->advance();
            }

            $progressBar->finish();
            $this->newLine(2);

            // Display results table
            $this->table(['Phone Number', 'Status', 'Message ID', 'Error'], $results);

            // Summary
            $this->info("📊 Summary:");
            $this->info("✅ Successful: {$successCount}");
            $this->info("❌ Failed: {$failureCount}");
            $this->info("📱 Total: " . count($phoneNumbers));

            return $failureCount === 0 ? 0 : 1;

        } catch (\Exception $e) {
            $this->error("❌ SMS Service Error: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Send SMS in bulk
     */
    private function sendBulkSms(array $phoneNumbers, string $message, string $provider): int
    {
        $this->info('📤 Sending SMS in bulk...');
        $this->newLine();

        try {
            $smsService = $this->createSmsService($provider);

            $this->info('🔄 Processing bulk send...');
            $results = $smsService->sendToMany($message, $phoneNumbers);

            $this->newLine();

            // Display detailed results
            if (!empty($results['results'])) {
                $tableData = [];
                foreach ($results['results'] as $result) {
                    $tableData[] = [
                        'phone' => $result['phone_number'],
                        'status' => $result['success'] ? '✅ Sent' : '❌ Failed',
                        'message_id' => $result['message_id'] ?? 'N/A',
                        'error' => $result['error'] ?? ''
                    ];
                }

                $this->table(['Phone Number', 'Status', 'Message ID', 'Error'], $tableData);
            }

            // Summary
            $this->info("📊 Bulk Send Summary:");
            $this->info("✅ Successful: {$results['successful']}");
            $this->info("❌ Failed: {$results['failed']}");
            $this->info("📱 Total: {$results['total_sent']}");

            return $results['failed'] === 0 ? 0 : 1;

        } catch (\Exception $e) {
            $this->error("❌ Bulk SMS Error: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Helper methods
     */
    private function validatePhoneNumber(string $phone): bool
    {
        // Remove spaces and special characters
        $cleaned = preg_replace('/[^0-9+]/', '', $phone);

        // Check if it's a valid Tanzanian number
        return preg_match('/^(\+?255|0)[67]\d{8}$/', $cleaned) ||
               preg_match('/^255[67]\d{8}$/', $cleaned);
    }

    private function getDefaultTestMessage(): string
    {
        return "Test SMS from Magic Builders system sent at " . now()->format('Y-m-d H:i:s') . ". Please ignore this message.";
    }

    private function confirmSending(array $phoneNumbers, string $message, string $provider): bool
    {
        $this->warn('⚠️  You are about to send REAL SMS messages!');
        $this->table(['Parameter', 'Value'], [
            ['Provider', strtoupper($provider)],
            ['Recipients', count($phoneNumbers)],
            ['Message Length', strlen($message) . ' characters'],
            ['Estimated Cost', $this->estimateCost(count($phoneNumbers))],
        ]);

        return $this->confirm('Do you want to proceed with sending SMS?', false);
    }

    private function estimateCost(int $count): string
    {
        // Rough estimate - adjust based on your actual rates
        $costPerSms = 50; // TSH
        $totalCost = $count * $costPerSms;
        return "~{$totalCost} TSH ({$count} SMS × {$costPerSms} TSH)";
    }

    private function createProvider(string $providerName, array $config)
    {
        switch ($providerName) {
            case 'beem':
                return new BeemSmsService($config);
            case 'nextsms':
                return new NextSmsService($config);
            default:
                throw new \Exception("Unknown provider: {$providerName}");
        }
    }

    private function createSmsService(string $provider): SmsService
    {
        // Temporarily override the default provider for testing
        config(['sms.default_provider' => $provider]);
        return app(SmsService::class);
    }

    private function displayProviderConfig(string $provider, array $config): void
    {
        switch ($provider) {
            case 'beem':
                $this->line("    API Key: " . (empty($config['api_key']) ? '❌ Missing' : '✅ Set'));
                $this->line("    API Secret: " . (empty($config['api_secret']) ? '❌ Missing' : '✅ Set'));
                $this->line("    Sender ID: " . ($config['sender_id'] ?? '❌ Missing'));
                $this->line("    Base URL: " . ($config['base_url'] ?? '❌ Missing'));
                break;
            case 'nextsms':
                $this->line("    Username: " . (empty($config['username']) ? '❌ Missing' : '✅ Set'));
                $this->line("    Password: " . (empty($config['password']) ? '❌ Missing' : '✅ Set'));
                $this->line("    Sender ID: " . ($config['sender_id'] ?? '❌ Missing'));
                $this->line("    Base URL: " . ($config['base_url'] ?? '❌ Missing'));
                break;
        }
    }

    private function displayMissingConfig(string $provider): void
    {
        $this->line("    Please check your .env file for:");
        switch ($provider) {
            case 'beem':
                $this->line("    - BEEM_API_KEY");
                $this->line("    - BEEM_API_SECRET");
                $this->line("    - BEEM_SENDER_ID");
                break;
            case 'nextsms':
                $this->line("    - NEXTSMS_USERNAME");
                $this->line("    - NEXTSMS_PASSWORD");
                $this->line("    - NEXTSMS_SENDER_ID");
                break;
        }
    }
}

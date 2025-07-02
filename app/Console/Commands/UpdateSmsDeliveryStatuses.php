<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\UpdateSmsDeliveryStatuses as UpdateSmsDeliveryStatusesJob;

class UpdateSmsDeliveryStatuses extends Command
{
    protected $signature = 'sms:update-statuses';
    protected $description = 'Update SMS delivery statuses from providers';

    public function handle()
    {
        $this->info('Updating SMS delivery statuses...');

        UpdateSmsDeliveryStatusesJob::dispatch();

        $this->info('SMS delivery status update job dispatched.');
        return 0;
    }
}

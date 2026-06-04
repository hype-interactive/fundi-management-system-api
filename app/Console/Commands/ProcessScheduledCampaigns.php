<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ProcessScheduledCampaigns extends Command
{
    protected $signature = 'sms:process-scheduled';

    protected $description = 'Process scheduled SMS campaigns that are due for sending';

    public function handle()
    {
        $this->info('Processing scheduled SMS campaigns...');

        $dueCampaigns = \App\Models\SmsCampaign::where('status', 'scheduled')
            ->where('scheduled_at', '<=', now())
            ->get();

        foreach ($dueCampaigns as $campaign) {
            \App\Jobs\SendSmsCampaign::dispatch($campaign);
            $this->line("Dispatched campaign: {$campaign->title}");
        }

        $this->info("Processed {$dueCampaigns->count()} scheduled campaigns.");

        return 0;
    }
}

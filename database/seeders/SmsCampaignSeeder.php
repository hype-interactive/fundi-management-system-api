<?php

namespace Database\Seeders;

use App\Models\SmsCampaign;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class SmsCampaignSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        SmsCampaign::factory()
            ->count(50)
            ->create();
    }
}

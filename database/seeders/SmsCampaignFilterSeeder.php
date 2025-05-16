<?php

namespace Database\Seeders;

use App\Models\SmsCampaignFilter;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SmsCampaignFilterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        SmsCampaignFilter::factory()
            ->count(50)
            ->create();
    }
}

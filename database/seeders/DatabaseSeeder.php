<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            OperatorDistrictSeeder::class,
            FundiSeeder::class,
            ProductSeeder::class,
            SiteVisitSeeder::class,
            SiteVisitPhotoSeeder::class,
            SiteVisitProductSeeder::class,
            SiteVisitFundiSeeder::class,
            BudgetAllocationSeeder::class,
            DisbursementBatchSeeder::class,
            TransactionSeeder::class,
            OperatorIncentiveSeeder::class,
            SmsCampaignSeeder::class,
            SmsTemplateSeeder::class,
            SmsCampaignFilterSeeder::class,
            SmsMessageSeeder::class,
            ActivityLogSeeder::class,
        ]);
    }
}

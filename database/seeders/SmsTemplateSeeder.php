<?php

namespace Database\Seeders;

use App\Models\SmsTemplate;
use Illuminate\Database\Seeder;

class SmsTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        SmsTemplate::factory()
            ->count(10)
            ->create();
    }
}

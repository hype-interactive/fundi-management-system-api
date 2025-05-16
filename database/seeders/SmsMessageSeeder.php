<?php

namespace Database\Seeders;

use App\Models\SmsMessage;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SmsMessageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        SmsMessage::factory()
            ->count(50)
            ->create();
    }
}

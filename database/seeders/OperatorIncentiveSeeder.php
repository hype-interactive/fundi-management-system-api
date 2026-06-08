<?php

namespace Database\Seeders;

use App\Models\OperatorIncentive;
use Illuminate\Database\Seeder;

class OperatorIncentiveSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        OperatorIncentive::factory()
            ->count(50)
            ->create();
    }
}

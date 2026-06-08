<?php

namespace Database\Seeders;

use App\Models\DisbursementBatch;
use Illuminate\Database\Seeder;

class DisbursementBatchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DisbursementBatch::factory()
            ->count(50)
            ->create();
    }
}

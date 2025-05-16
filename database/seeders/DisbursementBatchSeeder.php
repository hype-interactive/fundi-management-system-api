<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DisbursementBatch;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

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

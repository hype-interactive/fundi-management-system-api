<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BudgetAllocation;
use Database\Factories\BudgetAllocationFactory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class BudgetAllocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        BudgetAllocation::factory()
            ->count(50)
            ->create();
    }
}

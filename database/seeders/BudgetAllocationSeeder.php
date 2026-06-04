<?php

namespace Database\Seeders;

use App\Models\BudgetAllocation;
use Illuminate\Database\Seeder;

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

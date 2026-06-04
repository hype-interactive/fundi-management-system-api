<?php

namespace Database\Seeders;

use App\Models\OperatorDistrict;
use Illuminate\Database\Seeder;

class OperatorDistrictSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        OperatorDistrict::factory()
            ->count(50)
            ->create();
    }
}

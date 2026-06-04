<?php

namespace Database\Seeders;

use App\Models\Fundi;
use Illuminate\Database\Seeder;

class FundiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Fundi::factory()
            ->count(100)
            ->create();
    }
}

<?php

namespace Database\Seeders;

use App\Models\SiteVisitFundi;
use Illuminate\Database\Seeder;

class SiteVisitFundiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        SiteVisitFundi::factory()
            ->count(50)
            ->create();
    }
}

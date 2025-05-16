<?php

namespace Database\Seeders;

use App\Models\SiteVisit;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SiteVisitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        SiteVisit::factory()
            ->count(50)
            ->create();
    }
}

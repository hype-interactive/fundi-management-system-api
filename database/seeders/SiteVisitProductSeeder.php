<?php

namespace Database\Seeders;

use App\Models\SiteVisitProduct;
use Illuminate\Database\Seeder;

class SiteVisitProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        SiteVisitProduct::factory()
            ->count(50)
            ->create();
    }
}

<?php

namespace Database\Seeders;

use App\Models\SiteVisitPhoto;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SiteVisitPhotoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        SiteVisitPhoto::factory()
            ->count(50)
            ->create();
    }
}

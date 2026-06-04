<?php

namespace App\Console\Commands;

use App\Http\Controllers\GeoJsonController;
use Illuminate\Console\Command;

class ProcessGeoJson extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geojson:process';

    /**
     * The console command description.l
     *
     * @var string
     */
    protected $description = 'Process GeoJSON data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $geoJsonController = new GeoJsonController;
        $geoJsonController->parseGeoJson();
        $this->info('GeoJSON data processed successfully.');
    }
}

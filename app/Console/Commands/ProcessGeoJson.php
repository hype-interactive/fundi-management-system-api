<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\GeoJsonController;

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
        $geoJsonController = new GeoJsonController();
        $geoJsonController->parseGeoJson();
        $this->info('GeoJSON data processed successfully.');
    }
}

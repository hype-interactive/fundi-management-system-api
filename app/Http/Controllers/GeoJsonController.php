<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GeoJsonController extends Controller
{
    public function parseGeoJson()
    {
        try {
            // Path to the GeoJSON files
            $districtsFilePath = base_path('database/data/districts.json');
            $wardFilePath = base_path('database/data/wards.json');

            // Check if the files exist
            if (!file_exists($districtsFilePath)) {
                abort(404, 'Districts GeoJSON file not found');
            }

            if (!file_exists($wardFilePath)) {
                abort(404, 'Wards GeoJSON file not found');
            }

            // Read the file content
            $geoJsonDistrictContent = file_get_contents($districtsFilePath);
            $geoJsonWardContent = file_get_contents($wardFilePath);

            // Decode the GeoJSON content
            $geoDistrictJson = json_decode($geoJsonDistrictContent, true);
            $geoWardJson = json_decode($geoJsonWardContent, true);

            // Check for JSON decoding errors
            if (json_last_error() !== JSON_ERROR_NONE) {
                abort(400, 'Error decoding GeoJSON: ' . json_last_error_msg());
            }

            // Check if the GeoJSON contains features for districts
            if (!isset($geoDistrictJson['features'])) {
                abort(400, 'Invalid GeoJSON format for districts');
            }

            // Process district data
            foreach ($geoDistrictJson['features'] as $feature) {
                $district = $feature['properties']['District'] ?? null;
                $region = str_replace(' Region', '', $feature['properties']['region'] ?? '');

                // Check if the region exists, and insert it if it doesn't
                $regionId = DB::table('regions')->where('name', $region)->value('id');

                if (!$regionId) {
                    // Insert the new region and get its ID
                    $regionId = DB::table('regions')->insertGetId([
                        'name' => $region,
                    ]);
                }

                // Insert the district with the region ID
                if ($district) {
                    DB::table('districts')->insert([
                        'name' => $district,
                        'region_id' => $regionId,
                    ]);
                }
            }

            // Check if the GeoJSON contains features for wards
            if (!isset($geoWardJson['features'])) {
                abort(400, 'Invalid GeoJSON format for wards');
            }

            // Process ward data
            foreach ($geoWardJson['features'] as $feature) {
                $ward = $feature['properties']['Ward'] ?? null;
                $district = $feature['properties']['District'] ?? null;

                // Get the district ID
                $districtId = DB::table('districts')->where('name', $district)->value('id');

                if (!$districtId) {
                    // Handle missing district ID if necessary
                    logger("District not found for ward: $ward (District: $district)");
                    continue; // Skip this ward if the district ID is not found
                }

                // Insert the ward with the district ID
                if ($ward) {
                    DB::table('wards')->insert([
                        'name' => $ward,
                        'district_id' => $districtId,
                    ]);
                }
            }

            return response()->json(['message' => 'GeoJSON data processed successfully']);

        } catch (\Exception $e) {
            // Catch any exceptions and return a 500 error with the exception message
            return response()->json(['error' => 'An error occurred while processing the GeoJSON data: ' . $e->getMessage()], 500);
        }
    }
}

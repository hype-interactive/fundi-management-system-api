<?php

namespace App\Traits;

use App\Models\Ward;
use App\Models\District;
use App\Models\Region;

trait LocationTrait
{
    /**
     * Create or update a district by name and optional ID.
     */
    public function createDistrict(string $name, int|null $id = null): void
    {
        if (District::where('name', $name)->exists()) {
            return;
        }

        District::updateOrCreate(
            ['id' => $id ?? 0],
            [
                'name' => $name,
                'region_id' => Region::where('name', 'Dar es Salaam')->first()?->id ?? 2, // Default to Dar es Salaam
            ]
        );
    }

    /**
     * Create or update a ward by name and district.
     */
    public function createWard(string $name, int $districtId, int|null $id = null): void
    {
        if (Ward::where('name', $name)->where('district_id', $districtId)->exists()) {
            return;
        }

        Ward::updateOrCreate(
            ['id' => $id ?? 0],
            [
                'name' => $name,
                'district_id' => $districtId,
            ]
        );
    }

    /**
     * Retrieve all districts or those in a specific region.
     */
    public static function getDistricts(int|null $regionId = null)
    {
        return $regionId
            ? District::where('region_id', $regionId)->get()
            : District::all();
    }

    /**
     * Retrieve all wards or those in a specific district.
     */
    public static function getWards(?int $districtId = null)
    {
        return $districtId
            ? Ward::where('district_id', $districtId)->get()
            : Ward::all();
    }

    /**
     * Get the district ID from a ward ID.
     */
    public function getDistrictIdFromWard(int $wardId): ?int
    {
        return Ward::find($wardId)?->district_id;
    }
}

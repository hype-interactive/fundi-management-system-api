<?php

namespace App\Traits;

use App\Models\District;
use App\Models\Region;
use App\Models\Ward;
use Illuminate\Support\Collection;

trait LocationTrait
{
    /**
     * Check if current user is admin or root
     */
    protected function isAdminOrRoot(): bool
    {
        $user = currentUser();

        return $user && in_array($user->role->name, ['admin', 'root']);
    }

    /**
     * Get all regions
     */
    public function getAllRegions(): Collection
    {
        return Region::orderBy('name')->get();
    }

    /**
     * Get accessible regions based on user permissions
     */
    public function getAccessibleRegions(): Collection
    {
        if ($this->isAdminOrRoot()) {
            return $this->getAllRegions();
        }

        // For non-admin users, get regions that contain their accessible districts
        $userDistrictIds = $this->getUserAccessibleDistrictIds();

        return Region::whereHas('districts', function ($query) use ($userDistrictIds) {
            $query->whereIn('id', $userDistrictIds);
        })->orderBy('name')->get();
    }

    /**
     * Get all districts without restrictions
     */
    public function getAllDistricts(): Collection
    {
        return District::with('region')->orderBy('name')->get();
    }

    /**
     * Get districts by region ID
     */
    public function getDistrictsByRegion(?int $regionId = null): Collection
    {
        if (! $regionId) {
            return collect();
        }

        return District::where('region_id', $regionId)
            ->with('region')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get user's accessible districts
     */
    public function getUserAccessibleDistricts(): Collection
    {
        if ($this->isAdminOrRoot()) {
            return $this->getAllDistricts();
        }

        $user = currentUser();
        if (! $user) {
            return collect();
        }

        // Get districts from user's districts relationship
        $userDistricts = $user->districts()->with('region')->orderBy('name')->get();

        // If user has no districts assigned, fallback to primary district
        if ($userDistricts->isEmpty() && $user->primary_district) {
            return collect([$user->primary_district->load('region')]);
        }

        return $userDistricts;
    }

    /**
     * Get user's accessible district IDs
     */
    public function getUserAccessibleDistrictIds(): Collection
    {
        return $this->getUserAccessibleDistricts()->pluck('id');
    }

    /**
     * Get user's accessible districts within a specific region
     */
    public function getUserDistrictsByRegion(?int $regionId = null): Collection
    {
        if (! $regionId) {
            return collect();
        }

        $userDistricts = $this->getUserAccessibleDistricts();

        return $userDistricts->where('region_id', $regionId);
    }

    /**
     * Get districts based on user permissions and optional region filter
     * This is the main method that should be used by components
     */
    public function getDistricts(?int $regionId = null): Collection
    {
        if ($this->isAdminOrRoot()) {
            return $regionId ? $this->getDistrictsByRegion($regionId) : $this->getAllDistricts();
        }

        return $regionId ? $this->getUserDistrictsByRegion($regionId) : $this->getUserAccessibleDistricts();
    }

    /**
     * Get all wards for a specific district
     */
    public function getWards(?int $districtId = null): Collection
    {
        if (! $districtId) {
            return collect();
        }

        // For non-admin users, verify they have access to this district
        if (! $this->isAdminOrRoot()) {
            $accessibleDistrictIds = $this->getUserAccessibleDistrictIds();
            if (! $accessibleDistrictIds->contains($districtId)) {
                return collect();
            }
        }

        return Ward::where('district_id', $districtId)
            ->with('district.region')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get all wards within user's accessible districts
     */
    public function getUserAccessibleWards(): Collection
    {
        $districtIds = $this->getUserAccessibleDistrictIds();

        return Ward::whereIn('district_id', $districtIds)
            ->with('district.region')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get wards by region (for admin users) or user's accessible wards within region
     */
    public function getWardsByRegion(?int $regionId = null): Collection
    {
        if (! $regionId) {
            return collect();
        }

        if ($this->isAdminOrRoot()) {
            return Ward::whereHas('district', function ($query) use ($regionId) {
                $query->where('region_id', $regionId);
            })->with('district.region')->orderBy('name')->get();
        }

        // For non-admin users, get their accessible wards within the region
        $userDistricts = $this->getUserDistrictsByRegion($regionId);
        $districtIds = $userDistricts->pluck('id');

        return Ward::whereIn('district_id', $districtIds)
            ->with('district.region')
            ->orderBy('name')
            ->get();
    }

    /**
     * Check if user has access to a specific district
     */
    public function hasDistrictAccess(int $districtId): bool
    {
        if ($this->isAdminOrRoot()) {
            return true;
        }

        return $this->getUserAccessibleDistrictIds()->contains($districtId);
    }

    /**
     * Check if user has access to a specific ward
     */
    public function hasWardAccess(int $wardId): bool
    {
        if ($this->isAdminOrRoot()) {
            return true;
        }

        $ward = Ward::find($wardId);
        if (! $ward) {
            return false;
        }

        return $this->hasDistrictAccess($ward->district_id);
    }

    /**
     * Check if user has access to a specific region
     */
    public function hasRegionAccess(int $regionId): bool
    {
        if ($this->isAdminOrRoot()) {
            return true;
        }

        $accessibleRegionIds = $this->getAccessibleRegions()->pluck('id');

        return $accessibleRegionIds->contains($regionId);
    }

    /**
     * Get user's primary region (based on primary district)
     */
    public function getUserPrimaryRegion(): ?Region
    {
        $user = currentUser();
        if (! $user || ! $user->primary_district) {
            return null;
        }

        return $user->primary_district->region;
    }

    /**
     * Get user's primary district
     */
    public function getUserPrimaryDistrict(): ?District
    {
        $user = currentUser();

        return $user?->primary_district;
    }

    /**
     * Apply location-based query filters
     * This method can be used to filter any query based on user permissions and selected filters
     */
    public function applyLocationFiltersToQuery($query, ?int $regionId = null, ?int $districtId = null, ?int $wardId = null)
    {
        // For non-admin users, restrict to their accessible districts
        if (! $this->isAdminOrRoot()) {
            $userDistrictIds = $this->getUserAccessibleDistrictIds();
            $query->whereHas('ward', function ($wardQuery) use ($userDistrictIds) {
                $wardQuery->whereIn('district_id', $userDistrictIds);
            });
        }

        // Apply region filter
        if ($regionId) {
            $query->whereHas('ward.district', function ($districtQuery) use ($regionId) {
                $districtQuery->where('region_id', $regionId);
            });
        }

        // Apply district filter
        if ($districtId) {
            $query->whereHas('ward', function ($wardQuery) use ($districtId) {
                $wardQuery->where('district_id', $districtId);
            });
        }

        // Apply ward filter
        if ($wardId) {
            $query->where('ward_id', $wardId);
        }

        return $query;
    }

    /**
     * Get location breadcrumb for display purposes
     */
    public function getLocationBreadcrumb(?int $wardId = null, ?int $districtId = null, ?int $regionId = null): string
    {
        $parts = [];

        if ($wardId) {
            $ward = Ward::with('district.region')->find($wardId);
            if ($ward) {
                $parts[] = $ward->name;
                $parts[] = $ward->district->name;
                $parts[] = $ward->district->region->name;
            }
        } elseif ($districtId) {
            $district = District::with('region')->find($districtId);
            if ($district) {
                $parts[] = $district->name;
                $parts[] = $district->region->name;
            }
        } elseif ($regionId) {
            $region = Region::find($regionId);
            if ($region) {
                $parts[] = $region->name;
            }
        }

        return implode(', ', $parts);
    }
}

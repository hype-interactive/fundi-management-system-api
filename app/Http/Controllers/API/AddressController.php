<?php

namespace App\Http\Controllers\API;

use App\Models\Region;
use App\Models\District;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\WardMinimalResource;
use App\Http\Resources\RegionMinimalResource;
use App\Http\Resources\DistrictMinimalResource;

class AddressController extends BaseAPIController
{
    /**
     * Get all the Regions
     *
     * @return JsonResponse
     */
    public function getRegions(): JsonResponse
    {
        try {
            $regions = Region::orderBy('name')->get();

            return $this->successResponse(
                RegionMinimalResource::collection($regions),
                'Regions retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to retrieve regions',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Get all the Districts of a Region
     *
     * @param int $region_id
     * @return JsonResponse
     */
    public function getDistricts(int $region_id): JsonResponse
    {
        try {
            $region = Region::find($region_id);

            if (!$region) {
                return $this->errorResponse(
                    'Region not found',
                    ['region_id' => 'The specified region does not exist'],
                    404
                );
            }

            $districts = $region->districts()->orderBy('name')->get();

            return $this->successResponse(
                DistrictMinimalResource::collection($districts),
                'Districts retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to retrieve districts',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Get all the Wards of a District
     *
     * @param int $district_id
     * @return JsonResponse
     */
    public function getWards(int $district_id): JsonResponse
    {
        try {
            $district = District::find($district_id);

            if (!$district) {
                return $this->errorResponse(
                    'District not found',
                    ['district_id' => 'The specified district does not exist'],
                    404
                );
            }

            // Check if operator has access to this district (for operators only)
            $operator = $this->getOperator();
            if ($operator && $operator->role && $operator->role->name !== 'admin' && $operator->role->name !== 'root') {
                $operatorDistricts = $this->getOperatorDistricts();

                // Fix: Check if operatorDistricts is a collection and not empty
                if (!$operatorDistricts || !$operatorDistricts->contains($district_id)) {
                    return $this->errorResponse(
                        'Access denied',
                        ['district_id' => 'You do not have access to this district'],
                        403
                    );
                }
            }

            $wards = $district->wards()->orderBy('name')->get();

            return $this->successResponse(
                WardMinimalResource::collection($wards),
                'Wards retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to retrieve wards',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Get all locations with hierarchical structure
     * For admin users: all locations
     * For operators: only their assigned districts and wards
     *
     * @return JsonResponse
     */
    public function getLocations(): JsonResponse
    {
        try {
            $operator = $this->getOperator();
            $operatorDistricts = collect(); // Initialize as empty collection

            // Get operator districts if operator exists
            if ($operator) {
                $operatorDistricts = $this->getOperatorDistricts();
            }

            // Build query based on user role
            $regionsQuery = Region::with(['districts.wards']);

            // If operator (not admin/root), filter by their districts
            if ($operator &&
                $operator->role->name !== 'admin' &&
                $operator->role->name !== 'root' &&
                !$operatorDistricts->isEmpty()) {

                $regionsQuery->whereHas('districts', function ($query) use ($operatorDistricts) {
                    $query->whereIn('id', $operatorDistricts->toArray());
                });
            }

            $regions = $regionsQuery->orderBy('name')->get();
            $locations = [];

            foreach ($regions as $region) {
                $districts = $region->districts;

                // Filter districts for operators
                if ($operator &&
                    $operator->role->name !== 'admin' &&
                    $operator->role->name !== 'root' &&
                    !$operatorDistricts->isEmpty()) {

                    $districts = $districts->whereIn('id', $operatorDistricts->toArray());
                }

                $districts_data = [];

                foreach ($districts as $district) {
                    $wards = $district->wards->sortBy('name');
                    $wards_data = [];

                    foreach ($wards as $ward) {
                        $wards_data[] = [
                            'id' => $ward->id,
                            'name' => $ward->name,
                        ];
                    }

                    $districts_data[] = [
                        'id' => $district->id,
                        'name' => $district->name,
                        'wards' => $wards_data,
                        'wards_count' => count($wards_data),
                    ];
                }

                // Only include regions that have districts (for operators)
                if (!empty($districts_data)) {
                    $locations[] = [
                        'id' => $region->id,
                        'name' => $region->name,
                        'districts' => $districts_data,
                        'districts_count' => count($districts_data),
                    ];
                }
            }

            return $this->successResponse(
                $locations,
                'Locations retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to retrieve locations',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Get operator's assigned districts only
     * Useful for mobile apps to get operator's working areas
     *
     * @return JsonResponse
     */
    public function getOperatorAssignedDistricts(): JsonResponse
    {
        try {
            $operator = $this->getOperator();

            if (!$operator) {
                return $this->errorResponse(
                    'Authentication required',
                    null,
                    401
                );
            }

            if (!$this->isOperatorActive()) {
                return $this->errorResponse(
                    'Account is inactive',
                    null,
                    403
                );
            }

            $operator = $this->getOperatorWithRelations(['districts.region', 'districts.wards']);

            if (!$operator || !$operator->districts) {
                return $this->successResponse(
                    [],
                    'No districts assigned to this operator'
                );
            }

            $districts = $operator->districts;

            return $this->successResponse(
                DistrictMinimalResource::collection($districts),
                'Operator districts retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to retrieve operator districts',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Get wards within operator's assigned districts
     * Useful for mobile dropdowns when creating fundis or site visits
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getOperatorAssignedWards(Request $request): JsonResponse
    {
        try {
            $operator = $this->getOperator();

            if (!$operator) {
                return $this->errorResponse(
                    'Authentication required',
                    null,
                    401
                );
            }

            if (!$this->isOperatorActive()) {
                return $this->errorResponse(
                    'Account is inactive',
                    null,
                    403
                );
            }

            $operatorDistricts = $this->getOperatorDistricts();

            if (!$operatorDistricts || $operatorDistricts->isEmpty()) {
                return $this->successResponse(
                    [],
                    'No wards available - no districts assigned'
                );
            }

            $wardsQuery = \App\Models\Ward::with(['district.region'])
                ->whereHas('district', function ($query) use ($operatorDistricts) {
                    $query->whereIn('id', $operatorDistricts->toArray());
                })
                ->orderBy('name');

            // Optional district filter
            if ($request->has('district_id')) {
                $districtId = $request->input('district_id');

                // Verify operator has access to this district
                if (!$operatorDistricts->contains($districtId)) {
                    return $this->errorResponse(
                        'Access denied to specified district',
                        ['district_id' => 'You do not have access to this district'],
                        403
                    );
                }

                $wardsQuery->where('district_id', $districtId);
            }

            // Optional search
            if ($request->has('search')) {
                $search = $request->input('search');
                $wardsQuery->where('name', 'like', "%{$search}%");
            }

            $wards = $wardsQuery->get();

            return $this->successResponse(
                WardMinimalResource::collection($wards),
                'Operator wards retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to retrieve operator wards',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Search locations (regions, districts, wards) by name
     * Respects operator district restrictions
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function searchLocations(Request $request): JsonResponse
    {
        $request->validate([
            'search' => 'required|string|min:2|max:100',
            'type' => 'nullable|in:region,district,ward',
        ]);

        try {
            $search = $request->input('search');
            $type = $request->input('type');
            $operator = $this->getOperator();
            $operatorDistricts = collect(); // Initialize as empty collection
            $results = [];

            // Get operator districts if operator exists
            if ($operator) {
                $operatorDistricts = $this->getOperatorDistricts();
            }

            // Search regions (only if admin or contains operator's districts)
            if (!$type || $type === 'region') {
                $regionsQuery = Region::where('name', 'like', "%{$search}%");

                if ($operator &&
                    $operator->role->name !== 'admin' &&
                    $operator->role->name !== 'root' &&
                    !$operatorDistricts->isEmpty()) {

                    $regionsQuery->whereHas('districts', function ($query) use ($operatorDistricts) {
                        $query->whereIn('id', $operatorDistricts->toArray());
                    });
                }

                $regions = $regionsQuery->limit(10)->get();
                foreach ($regions as $region) {
                    $results[] = [
                        'id' => $region->id,
                        'name' => $region->name,
                        'type' => 'region',
                        'full_name' => $region->name,
                    ];
                }
            }

            // Search districts
            if (!$type || $type === 'district') {
                $districtsQuery = District::with('region')
                    ->where('name', 'like', "%{$search}%");

                if ($operator &&
                    $operator->role->name !== 'admin' &&
                    $operator->role->name !== 'root' &&
                    !$operatorDistricts->isEmpty()) {

                    $districtsQuery->whereIn('id', $operatorDistricts->toArray());
                }

                $districts = $districtsQuery->limit(10)->get();
                foreach ($districts as $district) {
                    $results[] = [
                        'id' => $district->id,
                        'name' => $district->name,
                        'type' => 'district',
                        'full_name' => $district->name . ', ' . $district->region->name,
                        'region_id' => $district->region_id,
                        'name' => $district->region->name,
                    ];
                }
            }

            // Search wards
            if (!$type || $type === 'ward') {
                $wardsQuery = \App\Models\Ward::with(['district.region'])
                    ->where('name', 'like', "%{$search}%");

                if ($operator &&
                    $operator->role->name !== 'admin' &&
                    $operator->role->name !== 'root' &&
                    !$operatorDistricts->isEmpty()) {

                    $wardsQuery->whereHas('district', function ($query) use ($operatorDistricts) {
                        $query->whereIn('id', $operatorDistricts->toArray());
                    });
                }

                $wards = $wardsQuery->limit(10)->get();
                foreach ($wards as $ward) {
                    $results[] = [
                        'id' => $ward->id,
                        'name' => $ward->name,
                        'type' => 'ward',
                        'full_name' => $ward->name . ', ' . $ward->district->name . ', ' . $ward->district->region->name,
                        'district_id' => $ward->district_id,
                        'name' => $ward->district->name,
                        'region_id' => $ward->district->region_id,
                        'name' => $ward->district->region->name,
                    ];
                }
            }

            return $this->successResponse(
                $results,
                'Location search completed successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to search locations',
                ['error' => $e->getMessage()],
                500
            );
        }
    }
}

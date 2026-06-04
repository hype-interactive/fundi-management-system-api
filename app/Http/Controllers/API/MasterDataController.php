<?php

namespace App\Http\Controllers\API;

use App\Http\Resources\ProductResource;
use App\Http\Resources\WardMinimalResource;
use App\Models\Product;
use App\Models\Ward;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MasterDataController extends BaseAPIController
{
    public function wards(): JsonResponse
    {
        $wards = Ward::with(['district.region'])
            ->whereHas('district', function ($query) {
                $query->whereIn('id', $this->getOperatorDistricts());
            })
            ->orderBy('name')
            ->get();

        return $this->successResponse(
            WardMinimalResource::collection($wards),
            'Wards retrieved successfully'
        );
    }

    public function products(Request $request): JsonResponse
    {
        $query = Product::where('visibility', 'public');

        // Optional availability filter
        if ($request->has('availability')) {
            $query->where('availability', $request->availability);
        } else {
            // Default to available only
            $query->where('availability', 'available');
        }

        $products = $query->orderBy('created_at', 'asc')->get();

        return $this->successResponse(
            ProductResource::collection($products),
            'Products retrieved successfully'
        );
    }
}

<?php

namespace App\Http\Controllers\API;

use App\Models\Ward;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\ProductResource;
use App\Http\Resources\WardMinimalResource;

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

    public function products(): JsonResponse
    {
        $products = Product::where('is_active', true)
            ->orderBy('name')
            ->get();

        return $this->successResponse(
            ProductResource::collection($products),
            'Products retrieved successfully'
        );
    }
}

<?php

namespace App\Http\Controllers\API;

use App\Models\SiteVisit;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\SiteVisitResource;
use App\Http\Resources\SiteVisitListResource;

class SiteVisitController extends BaseAPIController
{
    public function index(Request $request): JsonResponse
    {
        $query = SiteVisit::with(['ward.district', 'photos'])
            ->where('operator_id', $this->getOperator()->id)
            ->withCount(['photos', 'fundis', 'products']);

        // Filters
        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->ward_id) {
            $query->where('ward_id', $request->ward_id);
        }

        if ($request->date_from) {
            $query->whereDate('visit_date', '>=', $request->date_from);
        }

        if ($request->date_to) {
            $query->whereDate('visit_date', '<=', $request->date_to);
        }

        $siteVisits = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return $this->successResponse([
            'site_visits' => SiteVisitListResource::collection($siteVisits->items()),
            'pagination' => [
                'current_page' => $siteVisits->currentPage(),
                'last_page' => $siteVisits->lastPage(),
                'per_page' => $siteVisits->perPage(),
                'total' => $siteVisits->total(),
            ],
        ]);
    }

    public function show(SiteVisit $siteVisit): JsonResponse
    {
        // Check ownership
        if ($siteVisit->operator_id !== $this->getOperator()->id) {
            return $this->errorResponse('Unauthorized access', null, 403);
        }

        $siteVisit->load([
            'ward.district.region',
            'operator',
            'approver',
            'photos',
            'products',
            'fundis'
        ]);

        return $this->successResponse(
            new SiteVisitResource($siteVisit),
            'Site visit details retrieved successfully'
        );
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'ward_id' => [
                'required',
                'exists:wards,id',
                Rule::exists('wards', 'id')->where(function ($query) {
                    $query->whereHas('district', function ($q) {
                        $q->whereIn('id', $this->getOperatorDistricts());
                    });
                }),
            ],
            'site_name' => 'required|string|max:255',
            'site_description' => 'required|string',
            'visit_date' => 'required|date|before_or_equal:today',
            'gps_latitude' => 'nullable|numeric|between:-90,90',
            'gps_longitude' => 'nullable|numeric|between:-180,180',
            'photos' => 'required|array|min:1',
            'photos.*' => 'image|max:5120', // 5MB max per photo
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.notes' => 'nullable|string',
            'fundis' => 'required|array|min:1',
            'fundis.*.fundi_id' => 'required|exists:fundis,id',
            'fundis.*.notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Create site visit
            $siteVisit = SiteVisit::create([
                'operator_id' => $this->getOperator()->id,
                'ward_id' => $request->ward_id,
                'site_name' => $request->site_name,
                'site_description' => $request->site_description,
                'visit_date' => $request->visit_date,
                'gps_latitude' => $request->gps_latitude,
                'gps_longitude' => $request->gps_longitude,
                'status' => 'pending',
            ]);

            // Upload and attach photos
            foreach ($request->file('photos') as $photo) {
                $path = $photo->store('site-visits/photos', 'public');
                $siteVisit->photos()->create(['photo_path' => $path]);
            }

            // Attach products
            foreach ($request->products as $product) {
                $siteVisit->products()->attach($product['product_id'], [
                    'quantity' => $product['quantity'],
                    'notes' => $product['notes'] ?? null,
                ]);
            }

            // Attach fundis
            foreach ($request->fundis as $fundi) {
                $siteVisit->fundis()->attach($fundi['fundi_id'], [
                    'notes' => $fundi['notes'] ?? null,
                ]);
            }

            $siteVisit->load(['ward.district', 'photos', 'products', 'fundis']);

            DB::commit();

            return $this->successResponse(
                new SiteVisitResource($siteVisit),
                'Site visit created successfully',
                201
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to create site visit: ' . $e->getMessage());
        }
    }
}

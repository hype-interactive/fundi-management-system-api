<?php

namespace App\Http\Controllers\API;

use App\Models\Ward;
use App\Models\SiteVisit;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\SiteVisitResource;
use App\Http\Resources\SiteVisitListResource;

class SiteVisitController extends BaseAPIController
{
    /**
     * Display a listing of site visits.
     *
     * This method retrieves a paginated list of site visits for the authenticated operator,
     * with optional filters for status, ward, and date range.
     *
     * @param Request $request
     * @return JsonResponse
     */
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

    /**
     * Show details of a specific site visit.
     *
     * This method retrieves detailed information about a specific site visit,
     * including the ward, operator, approver, photos, products, and fundis involved.
     *
     * @param SiteVisit $siteVisit
     * @return JsonResponse
     */
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

    /**
     * Store a new site visit.
     *
     * This method allows operators to create a new site visit record,
     * including details about the ward, site name, description, visit date,
     * GPS coordinates, photos, products, and fundis involved.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Exception
     * @throws \Throwable
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'ward_id' => [
                'required',
                'exists:wards,id',
                function ($attribute, $value, $fail) {
                    $operatorDistricts = $this->getOperatorDistricts();

                    $wardExists = Ward::where('id', $value)
                        ->whereHas('district', function ($query) use ($operatorDistricts) {
                            $query->whereIn('id', $operatorDistricts);
                        })
                        ->exists();

                    if (!$wardExists) {
                        $fail('The selected ward is not available in your operating districts.');
                    }
                },
            ],
            'site_name' => 'required|string|max:255',
            'site_description' => 'required|string',
            'visit_date' => 'required|date|before_or_equal:today',
            'gps_latitude' => 'nullable|numeric|between:-90,90',
            'gps_longitude' => 'nullable|numeric|between:-180,180',
            'photos' => 'required|array|min:1',
            'photos.*.photo_url' => 'required|url|max:500',
            'photos.*.photo_public_id' => 'required|string|max:255',
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

            // Store Cloudinary photo URLs and public IDs
            $photoCount = 0;
            foreach ($request->photos as $photoData) {
                $photo = $siteVisit->photos()->create([
                    'photo_url' => $photoData['photo_url'],
                    'photo_public_id' => $photoData['photo_public_id'],
                ]);

                Log::info('Photo attached to site visit', [
                    'site_visit_id' => $siteVisit->id,
                    'photo_id' => $photo->id,
                    'photo_url' => $photoData['photo_url'],
                    'photo_public_id' => $photoData['photo_public_id'],
                ]);

                $photoCount++;
            }

            Log::info("Successfully stored {$photoCount} photos for site visit {$siteVisit->id}");

            // Attach products
            $productCount = 0;
            foreach ($request->products as $product) {
                $siteVisit->products()->attach($product['product_id'], [
                    'quantity' => $product['quantity'],
                    'notes' => $product['notes'] ?? null,
                ]);

                $productCount++;
            }

            // Attach fundis
            $fundiCount = 0;
            foreach ($request->fundis as $fundi) {
                $siteVisit->fundis()->attach($fundi['fundi_id'], [
                    'notes' => $fundi['notes'] ?? null,
                ]);

                $fundiCount++;
            }

            $siteVisit->load(['ward.district', 'photos', 'products', 'fundis']);

            DB::commit();

            Log::info('Site visit creation completed successfully', [
                'site_visit_id' => $siteVisit->id,
                'photos_count' => $photoCount,
                'products_count' => $productCount,
                'fundis_count' => $fundiCount,
            ]);

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

    /**
     * Update an existing site visit.
     *
     * This method allows operators to update details of a site visit,
     *
     * @param Request $request
     * @param SiteVisit $siteVisit
     * @return JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Exception
     * @throws \Throwable
     */
    public function update(Request $request, SiteVisit $siteVisit): JsonResponse
    {
        // Check ownership
        if ($siteVisit->operator_id !== $this->getOperator()->id) {
            return $this->errorResponse('Unauthorized access', null, 403);
        }

        // Prevent updates to approved site visits (optional business rule)
        if ($siteVisit->status === 'approved') {
            return $this->errorResponse('Cannot update approved site visits', null, 422);
        }

        $request->validate([
            'ward_id' => [
                'sometimes',
                'required',
                'exists:wards,id',
                function ($attribute, $value, $fail) {
                    $operatorDistricts = $this->getOperatorDistricts();

                    $wardExists = Ward::where('id', $value)
                        ->whereHas('district', function ($query) use ($operatorDistricts) {
                            $query->whereIn('id', $operatorDistricts);
                        })
                        ->exists();

                    if (!$wardExists) {
                        $fail('The selected ward is not available in your operating districts.');
                    }
                },
            ],
            'site_name' => 'sometimes|required|string|max:255',
            'site_description' => 'sometimes|required|string',
            'visit_date' => 'sometimes|required|date|before_or_equal:today',
            'gps_latitude' => 'nullable|numeric|between:-90,90',
            'gps_longitude' => 'nullable|numeric|between:-180,180',
            'photos' => 'sometimes|array|min:1',
            'photos.*' => 'image|max:5120', // 5MB max per photo
            'remove_photos' => 'sometimes|array',
            'remove_photos.*' => 'exists:site_visit_photos,id',
            'products' => 'sometimes|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.notes' => 'nullable|string',
            'fundis' => 'sometimes|array|min:1',
            'fundis.*.fundi_id' => 'required|exists:fundis,id',
            'fundis.*.notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Update basic site visit information
            $updateData = $request->only([
                'ward_id',
                'site_name',
                'site_description',
                'visit_date',
                'gps_latitude',
                'gps_longitude'
            ]);

            // Remove null values to avoid overwriting with nulls
            $updateData = array_filter($updateData, function ($value) {
                return $value !== null;
            });

            $siteVisit->update($updateData);

            // Handle photo removal
            if ($request->has('remove_photos')) {
                $photosToRemove = $siteVisit->photos()
                    ->whereIn('id', $request->remove_photos)
                    ->get();

                foreach ($photosToRemove as $photo) {
                    // Delete file from storage
                    if ($photo->photo_path && Storage::disk('public')->exists($photo->photo_path)) {
                        Storage::disk('public')->delete($photo->photo_path);
                    }
                    $photo->delete();
                }
            }

            // Handle new photos
            if ($request->hasFile('photos')) {
                foreach ($request->file('photos') as $photo) {
                    $path = $photo->store('site-visits/photos', 'public');
                    $siteVisit->photos()->create(['photo_path' => $path]);
                }
            }

            // Update products if provided
            if ($request->has('products')) {
                // Detach all current products
                $siteVisit->products()->detach();

                // Attach new products
                foreach ($request->products as $product) {
                    $siteVisit->products()->attach($product['product_id'], [
                        'quantity' => $product['quantity'],
                        'notes' => $product['notes'] ?? null,
                    ]);
                }
            }

            // Update fundis if provided
            if ($request->has('fundis')) {
                // Detach all current fundis
                $siteVisit->fundis()->detach();

                // Attach new fundis
                foreach ($request->fundis as $fundi) {
                    $siteVisit->fundis()->attach($fundi['fundi_id'], [
                        'notes' => $fundi['notes'] ?? null,
                    ]);
                }
            }

            // Reload relationships
            $siteVisit->load([
                'ward.district.region',
                'operator',
                'approver',
                'photos',
                'products',
                'fundis'
            ]);

            DB::commit();

            return $this->successResponse(
                new SiteVisitResource($siteVisit),
                'Site visit updated successfully'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to update site visit: ' . $e->getMessage());
        }
    }
}

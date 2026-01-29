<?php

namespace App\Http\Controllers\API;

use App\Models\Ward;
use App\Models\Fundi;
use App\Traits\CacheManagement;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Resources\FundiResource;
use App\Services\CloudinaryImageService;
use App\Http\Resources\FundiMinimalResource;

class FundiController extends BaseAPIController
{
    use CacheManagement;
    
    protected CloudinaryImageService $cloudinaryService;

    public function __construct(CloudinaryImageService $cloudinaryService)
    {
        $this->cloudinaryService = $cloudinaryService;
    }

    /**
     * List all fundis with caching
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        // Validate all filter inputs to prevent cache poisoning
        $validated = $request->validate([
            'status' => 'nullable|string|in:active,inactive',
            'approval_status' => 'nullable|string|in:approved,rejected,pending',
            'ward_id' => 'nullable|integer|exists:wards,id',
            'search' => 'nullable|string',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1',
            'sort_by' => 'nullable|string|in:created_at,fname,lname,approved_at',
            'sort_direction' => 'nullable|string|in:asc,desc',
        ]);

        // Generate unique cache key based on all validated filters
        $cacheKey = $this->generateCacheKey('fundis.index', [
            'operator_id' => $this->getOperator()->id,
            'filters' => $validated,
        ]);

        // Cache only the data array, not the JsonResponse
        $cachedData = $this->rememberWithTags(
            $cacheKey,
            $this->getCacheTTL('short'),
            fn() => $this->buildFundisListData($validated),
            [self::CACHE_TAGS['fundis']]
        );

        return $this->successResponse($cachedData);
    }

    /**
     * Build fundis list data array for caching
     *
     * @param array $filters
     * @return array
     */
    private function buildFundisListData(array $filters): array
    {
        $query = Fundi::with(['ward.district.region', 'creator'])
            ->whereHas('ward.district', function ($q) {
                $q->whereIn('id', $this->getOperatorDistricts());
            });

        // Apply filters
        $this->applyFilters($query, $filters);

        // Add sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDirection = $filters['sort_direction'] ?? 'desc';
        $query->orderBy($sortBy, $sortDirection);

        $perPage = $filters['per_page'] ?? 15;
        $fundis = $query->paginate($perPage);

        return [
            'fundis' => FundiMinimalResource::collection($fundis->items())->resolve(),
            'pagination' => [
                'current_page' => $fundis->currentPage(),
                'last_page' => $fundis->lastPage(),
                'per_page' => $fundis->perPage(),
                'total' => $fundis->total(),
                'from' => $fundis->firstItem(),
                'to' => $fundis->lastItem(),
            ],
        ];
    }

    /**
     * Apply filters to query
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     * @return void
     */
    private function applyFilters($query, array $filters): void
    {
        // Status filter
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Approval status filter
        if (isset($filters['approval_status'])) {
            switch (strtolower($filters['approval_status'])) {
                case 'approved':
                    $query->approved();
                    break;
                case 'rejected':
                    $query->rejected();
                    break;
                case 'pending':
                    $query->pending();
                    break;
            }
        }

        // Ward filter
        if (isset($filters['ward_id'])) {
            $query->where('ward_id', $filters['ward_id']);
        }

        // Search filter
        if (isset($filters['search']) && !empty($filters['search'])) {
            $this->applySearchFilter($query, $filters['search']);
        }
    }

    /**
     * Apply search filter including phone number handling
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $searchTerm
     * @return void
     */
    private function applySearchFilter($query, string $searchTerm): void
    {
        $search = '%' . $searchTerm . '%';
        
        $query->where(function ($q) use ($search, $searchTerm) {
            $q->where('fname', 'like', $search)
                ->orWhere('mname', 'like', $search)
                ->orWhere('lname', 'like', $search)
                ->orWhere('unique_id', 'like', $search)
                ->orWhere('nida_number', 'like', $search)
                ->orWhereRaw(
                    "CONCAT(COALESCE(fname, ''), ' ', COALESCE(mname, ''), ' ', COALESCE(lname, '')) LIKE ?",
                    [$search]
                );
            
            // Handle phone search
            if (preg_match('/^[0-9\s\-\+]+$/', $searchTerm)) {
                $formattedPhone = $this->formatSearchStringForPhoneSearch($searchTerm);
                
                $q->orWhereRaw("REPLACE(REPLACE(primary_phone, ' ', ''), '-', '') LIKE ?", [$formattedPhone])
                  ->orWhereRaw("REPLACE(REPLACE(transaction_phone, ' ', ''), '-', '') LIKE ?", [$formattedPhone]);
            }
        });
    }

    /**
     * Show single fundi with caching
     *
     * @param string $unique_id
     * @return JsonResponse
     */
    public function show(string $unique_id): JsonResponse
    {
        // Validate the unique_id format
        if (!preg_match('/^FND\d{6}$/', $unique_id)) {
            return $this->errorResponse('Invalid fundi ID format', null, 400);
        }

        $cacheKey = $this->generateCacheKey('fundi.show', [
            'unique_id' => $unique_id,
            'operator_id' => $this->getOperator()->id,
        ]);

        $cachedData = $this->rememberWithTags(
            $cacheKey,
            $this->getCacheTTL('medium'),
            fn() => $this->buildFundiDetailData($unique_id),
            [self::CACHE_TAGS['fundis']]
        );

        if (isset($cachedData['error'])) {
            return $this->errorResponse(
                $cachedData['message'],
                null,
                $cachedData['status']
            );
        }

        return $this->successResponse(
            $cachedData,
            'Fundi details retrieved successfully'
        );
    }

    /**
     * Build fundi detail data for caching
     *
     * @param string $unique_id
     * @return array
     */
    private function buildFundiDetailData(string $unique_id): array
    {
        $fundi = Fundi::where('unique_id', $unique_id)->first();
        
        if (!$fundi) {
            return [
                'error' => true,
                'message' => 'Fundi not found',
                'status' => 404
            ];
        }

        // Check authorization
        if (!$this->getOperatorDistricts()->contains($fundi->ward->district_id)) {
            return [
                'error' => true,
                'message' => 'Unauthorized access',
                'status' => 403
            ];
        }

        // Load relationships and aggregates
        $fundi->load([
            'ward.district.region',
            'creator',
            'approver',
            'rejector',
            'transactions' => function ($query) {
                $query->latest()->limit(10);
            }
        ])
        ->loadCount('transactions')
        ->loadSum('transactions', 'amount');

        return (new FundiResource($fundi))->resolve();
    }

    /**
     * Create new fundi
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        // Validate input
        $validated = $this->validateFundiData($request);

        // Check for duplicates
        if ($this->isDuplicateFundi($validated)) {
            return $this->errorResponse(
                'A fundi with the same name and transaction phone or ward already exists.',
                null,
                422
            );
        }

        DB::beginTransaction();
        try {
            $fundi = $this->createFundi($validated, $request);

            DB::commit();

            // Clear caches after successful creation
            $this->clearFundiCaches();

            return $this->successResponse(
                new FundiResource($fundi),
                'Fundi registered successfully',
                201
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to register fundi', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->errorResponse(
                'Failed to register fundi. Please try again.',
                null,
                500
            );
        }
    }

    /**
     * Update fundi
     *
     * @param Request $request
     * @param Fundi $fundi
     * @return JsonResponse
     */
    public function update(Request $request, Fundi $fundi): JsonResponse
    {
        // Authorization check
        if (!$this->authorizeOperatorAccess($fundi)) {
            return $this->errorResponse('Unauthorized access', null, 403);
        }

        // Validate input
        $validated = $this->validateFundiUpdateData($request, $fundi);

        DB::beginTransaction();
        try {
            // Business rule check inside transaction to prevent race conditions
            if ($fundi->approved_at && $fundi->approval_status === 'Approved') {
                DB::rollBack();
                return $this->errorResponse(
                    'Cannot update approved fundi',
                    null,
                    422
                );
            }

            $this->updateFundiData($validated, $request, $fundi);

            DB::commit();

            // Clear caches for this specific fundi
            $this->clearFundiCaches($fundi->unique_id);

            return $this->successResponse(
                new FundiResource($fundi),
                'Fundi updated successfully'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update fundi', [
                'fundi_id' => $fundi->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->errorResponse(
                'Failed to update fundi. Please try again.',
                null,
                500
            );
        }
    }

    /**
     * Update fundi data
     *
     * @param array $validated
     * @param Request $request
     * @param Fundi $fundi
     * @return void
     */
    private function updateFundiData(array $validated, Request $request, Fundi $fundi): void
    {
        $data = collect($validated)->only([
            'fname',
            'mname',
            'lname',
            'gender',
            'nida_number',
            'primary_phone',
            'transaction_phone',
            'ranking',
            'ward_id',
            'status',
        ])->toArray();

        // Handle profile photo update
        if ($request->hasFile('profile_photo') || 
            ($request->filled('profile_photo_url') && $request->filled('profile_photo_public_id'))) {
            $this->handleProfilePhoto($request, $data, $fundi);
        }

        // Update the model
        $fundi->update($data);

        // Reload relationships for response
        $fundi->load([
            'ward.district.region',
            'creator',
            'approver',
        ]);
    }

    /**
     * Approve fundi
     *
     * @param Request $request
     * @param Fundi $fundi
     * @return JsonResponse
     */
    public function approve(Request $request, Fundi $fundi): JsonResponse
    {
        if (!$this->authorizeOperatorAccess($fundi)) {
            return $this->errorResponse('Unauthorized access', null, 403);
        }

        DB::beginTransaction();
        try {
            // Check approval status inside transaction
            if ($fundi->approval_status === 'Approved') {
                DB::rollBack();
                return $this->errorResponse('Fundi is already approved', null, 422);
            }

            $fundi->update([
                'approved_at' => now(),
                'approved_by' => $this->getOperator()->id,
                'rejected_at' => null,
                'rejected_by' => null,
                'rejection_reason' => null,
            ]);

            $fundi->load(['ward.district.region', 'creator', 'approver']);

            DB::commit();

            // Clear caches
            $this->clearFundiCaches($fundi->unique_id);

            return $this->successResponse(
                new FundiResource($fundi),
                'Fundi approved successfully'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to approve fundi', [
                'fundi_id' => $fundi->id,
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse(
                'Failed to approve fundi. Please try again.',
                null,
                500
            );
        }
    }

    /**
     * Reject fundi
     *
     * @param Request $request
     * @param Fundi $fundi
     * @return JsonResponse
     */
    public function reject(Request $request, Fundi $fundi): JsonResponse
    {
        if (!$this->authorizeOperatorAccess($fundi)) {
            return $this->errorResponse('Unauthorized access', null, 403);
        }

        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        DB::beginTransaction();
        try {
            // Check rejection status inside transaction
            if ($fundi->approval_status === 'Rejected') {
                DB::rollBack();
                return $this->errorResponse('Fundi is already rejected', null, 422);
            }

            $fundi->update([
                'rejected_at' => now(),
                'rejected_by' => $this->getOperator()->id,
                'rejection_reason' => $validated['rejection_reason'],
                'approved_at' => null,
                'approved_by' => null,
            ]);

            $fundi->load(['ward.district.region', 'creator', 'rejector']);

            DB::commit();

            // Clear caches
            $this->clearFundiCaches($fundi->unique_id);

            return $this->successResponse(
                new FundiResource($fundi),
                'Fundi rejected successfully'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to reject fundi', [
                'fundi_id' => $fundi->id,
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse(
                'Failed to reject fundi. Please try again.',
                null,
                500
            );
        }
    }

    /**
     * Search fundis for site visit assignment
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function searchForSiteVisit(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'query' => 'required|string|min:2|max:100',
            'ward_id' => 'nullable|integer|exists:wards,id',
        ]);

        // Check ward authorization if provided
        if (isset($validated['ward_id'])) {
            if (!$this->isWardInOperatorDistricts($validated['ward_id'])) {
                return $this->errorResponse(
                    'Unauthorized access to this ward',
                    null,
                    403
                );
            }
        }

        $cacheKey = $this->generateCacheKey('fundis.search', [
            'search' => $validated['query'],
            'ward_id' => $validated['ward_id'] ?? null,
            'operator_id' => $this->getOperator()->id,
        ]);

        $cachedData = $this->rememberWithTags(
            $cacheKey,
            $this->getCacheTTL('short'),
            fn() => $this->buildSearchResultsData($validated),
            [self::CACHE_TAGS['fundis']]
        );

        return $this->successResponse(
            $cachedData,
            'Search results retrieved successfully'
        );
    }

    /**
     * Build search results data for caching
     *
     * @param array $validated
     * @return array
     */
    private function buildSearchResultsData(array $validated): array
    {
        $query = Fundi::where('status', 'active')
            // ->approved()
            // ->whereHas('ward.district', function ($q) {
            //     $q->whereIn('id', $this->getOperatorDistricts());
            // })
            ->with(['ward.district.region']);

        if (isset($validated['ward_id'])) {
            $query->where('ward_id', $validated['ward_id']);
        }

        $this->applySearchFilter($query, $validated['query']);

        $fundis = $query->limit(20)->get();

        return FundiMinimalResource::collection($fundis)->resolve();
    }

    /**
     * Soft delete fundi
     *
     * @param Fundi $fundi
     * @return JsonResponse
     */
    public function destroy(Fundi $fundi): JsonResponse
    {
        if (!$this->authorizeOperatorAccess($fundi)) {
            return $this->errorResponse('Unauthorized access', null, 403);
        }

        DB::beginTransaction();
        try {
            $fundi->delete();

            DB::commit();

            // Clear caches
            $this->clearFundiCaches($fundi->unique_id);

            return $this->successResponse(
                null,
                'Fundi deleted successfully'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete fundi', [
                'fundi_id' => $fundi->id,
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse(
                'Failed to delete fundi. Please try again.',
                null,
                500
            );
        }
    }

    /**
     * Restore soft deleted fundi
     *
     * @param int $id
     * @return JsonResponse
     */
    public function restore(int $id): JsonResponse
    {
        $fundi = Fundi::onlyTrashed()->findOrFail($id);

        if (!$this->authorizeOperatorAccess($fundi)) {
            return $this->errorResponse('Unauthorized access', null, 403);
        }

        DB::beginTransaction();
        try {
            $fundi->restore();

            DB::commit();

            // Clear caches
            $this->clearFundiCaches($fundi->unique_id);

            return $this->successResponse(
                new FundiResource($fundi),
                'Fundi restored successfully'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to restore fundi', [
                'fundi_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse(
                'Failed to restore fundi. Please try again.',
                null,
                500
            );
        }
    }

    // ==================== VALIDATION METHODS ====================

    /**
     * Validate fundi creation data
     *
     * @param Request $request
     * @return array
     */
    private function validateFundiData(Request $request): array
    {
        return $request->validate([
            'fname' => 'required|string|max:255',
            'mname' => 'nullable|string|max:255',
            'lname' => 'required|string|max:255',
            'gender' => 'required|in:male,female',
            'nida_number' => 'nullable|string|max:20|unique:fundis,nida_number',
            'primary_phone' => [
                'required',
                'string',
                'max:15',
                'regex:/^\+?[0-9\s\-]+$/',
                'unique:fundis,primary_phone'
            ],
            'transaction_phone' => [
                'required',
                'string',
                'max:15',
                'regex:/^\+?[0-9\s\-]+$/',
                'unique:fundis,transaction_phone'
            ],
            'ward_id' => [
                'required',
                'integer',
                'exists:wards,id',
                function ($attribute, $value, $fail) {
                    if (!$this->isWardInOperatorDistricts($value)) {
                        $fail('The selected ward is not available in your operating districts.');
                    }
                },
            ],
            'ranking' => 'nullable|string|in:fundi_kuu,fundi_msaidizi',
            'profile_photo' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
            'profile_photo_url' => 'nullable|string|url',
            'profile_photo_public_id' => 'nullable|string',
        ]);
    }

    /**
     * Validate fundi update data
     *
     * @param Request $request
     * @param Fundi $fundi
     * @return array
     */
    private function validateFundiUpdateData(Request $request, Fundi $fundi): array
    {
        return $request->validate([
            'fname' => 'required|string|max:255',
            'mname' => 'nullable|string|max:255',
            'lname' => 'required|string|max:255',
            'gender' => 'required|in:male,female',
            'nida_number' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('fundis', 'nida_number')->ignore($fundi->id),
            ],
            'primary_phone' => [
                'required',
                'string',
                'max:15',
                'regex:/^\+?[0-9\s\-]+$/',
                Rule::unique('fundis', 'primary_phone')->ignore($fundi->id),
            ],
            'transaction_phone' => [
                'required',
                'string',
                'max:15',
                'regex:/^\+?[0-9\s\-]+$/',
                Rule::unique('fundis', 'transaction_phone')->ignore($fundi->id),
            ],
            'ward_id' => [
                'required',
                'integer',
                'exists:wards,id',
                function ($attribute, $value, $fail) {
                    if (!$this->isWardInOperatorDistricts($value)) {
                        $fail('The selected ward is not available in your operating districts.');
                    }
                },
            ],
            'ranking' => 'nullable|string|in:fundi_kuu,fundi_msaidizi',
            'status' => 'nullable|string|in:active,inactive',
            'profile_photo' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
            'profile_photo_url' => 'nullable|string|url',
            'profile_photo_public_id' => 'nullable|string',
        ]);
    }

    // ==================== HELPER METHODS ====================

    /**
     * Format search string for phone search
     *
     * @param string $searchTerm
     * @return string
     */
    private function formatSearchStringForPhoneSearch(string $searchTerm): string
    {
        $digitsOnly = preg_replace('/\D/', '', $searchTerm);
        $digitsOnly = ltrim($digitsOnly, '0');
        return '%+255' . $digitsOnly . '%';
    }

    /**
     * Check if operator has access to fundi
     *
     * @param Fundi $fundi
     * @return bool
     */
    private function authorizeOperatorAccess(Fundi $fundi): bool
    {
        return $this->getOperatorDistricts()->contains($fundi->ward->district_id);
    }

    /**
     * Check for duplicate fundi
     *
     * @param array $validated
     * @return bool
     */
    private function isDuplicateFundi(array $validated): bool
    {
        return Fundi::where('fname', $validated['fname'])
            ->where('lname', $validated['lname'])
            ->where(function ($query) use ($validated) {
                $query->where('transaction_phone', $validated['transaction_phone'])
                    ->orWhere('ward_id', $validated['ward_id']);
            })
            ->exists();
    }

    /**
     * Check if ward is in operator districts
     *
     * @param int $wardId
     * @return bool
     */
    private function isWardInOperatorDistricts(int $wardId): bool
    {
        return Ward::where('id', $wardId)
            ->whereHas('district', function ($query) {
                $query->whereIn('id', $this->getOperatorDistricts());
            })
            ->exists();
    }

    /**
     * Create fundi with all data
     *
     * @param array $validated
     * @param Request $request
     * @return Fundi
     */
    private function createFundi(array $validated, Request $request): Fundi
    {
        $data = collect($validated)->except('profile_photo')->toArray();
        $data['unique_id'] = $this->generateUniqueId();
        $data['created_by'] = $this->getOperator()->id;
        $data['status'] = 'active';

        // Handle profile photo
        if ($request->hasFile('profile_photo') || 
            ($request->filled('profile_photo_url') && $request->filled('profile_photo_public_id'))) {
            $this->handleProfilePhoto($request, $data);
        }

        $fundi = Fundi::create($data);
        $fundi->load(['ward.district.region', 'creator']);

        return $fundi;
    }

    /**
     * Handle profile photo upload/assignment
     *
     * @param Request $request
     * @param array $data
     * @param Fundi|null $fundi
     * @return void
     */
    private function handleProfilePhoto(Request $request, array &$data, ?Fundi $fundi = null): void
    {
        if ($request->filled('profile_photo_url') && $request->filled('profile_photo_public_id')) {
            // Delete old photo if updating and ID changed
            if ($fundi && 
                $fundi->profile_photo_public_id && 
                $fundi->profile_photo_public_id !== $request->profile_photo_public_id) {
                try {
                    $this->cloudinaryService->delete($fundi->profile_photo_public_id);
                } catch (\Exception $e) {
                    Log::warning('Failed to delete old profile photo', [
                        'public_id' => $fundi->profile_photo_public_id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $data['profile_photo'] = $request->profile_photo_url;
            $data['profile_photo_url'] = $request->profile_photo_url;
            $data['profile_photo_public_id'] = $request->profile_photo_public_id;
            
        } elseif ($request->hasFile('profile_photo')) {
            try {
                $method = $fundi ? 'update' : 'upload';
                $uploadResult = $this->cloudinaryService->$method(
                    $request->file('profile_photo'),
                    $fundi ? $fundi->profile_photo_public_id : null,
                    'FundiApp/Fundis'
                );

                if ($uploadResult) {
                    $data['profile_photo'] = $uploadResult['url'];
                    $data['profile_photo_url'] = $uploadResult['url'];
                    $data['profile_photo_public_id'] = $uploadResult['public_id'];
                }
            } catch (\Exception $e) {
                Log::error('Failed to upload profile photo', [
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        }
    }

    /**
     * Generate unique ID for fundi
     *
     * @return string
     */
    private function generateUniqueId(): string
    {
        do {
            $id = 'FND' . str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
        } while (Fundi::where('unique_id', $id)->exists());

        return $id;
    }
}
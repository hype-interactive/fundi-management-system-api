<?php

namespace App\Http\Controllers\API;

use App\Http\Resources\FundiMinimalResource;
use App\Http\Resources\FundiResource;
use App\Models\Fundi;
use App\Models\Ward;
use App\Services\CloudinaryImageService;
use App\Traits\CacheManagement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

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
     */
    public function index(Request $request): JsonResponse
    {
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

        $cacheKey = $this->generateCacheKey('fundis.index', [
            'operator_id' => $this->getOperator()->id,
            'filters' => $validated,
        ]);

        $cachedData = $this->rememberWithTags(
            $cacheKey,
            $this->getCacheTTL('short'),
            fn () => $this->buildFundisListData($validated),
            [self::CACHE_TAGS['fundis']]
        );

        return $this->successResponse($cachedData);
    }

    /**
     * Build fundis list data array for caching
     */
    private function buildFundisListData(array $filters): array
    {
        $query = Fundi::with(['ward.district.region', 'creator']);

        if (! isRoot() && ! isAdministrator()) {
            $query->whereHas('ward.district', function ($q) {
                $q->whereIn('id', $this->getOperatorDistricts());
            });
        }

        $this->applyFilters($query, $filters);

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
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     */
    private function applyFilters($query, array $filters): void
    {
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

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

        if (isset($filters['ward_id'])) {
            $query->where('ward_id', $filters['ward_id']);
        }

        if (isset($filters['search']) && ! empty($filters['search'])) {
            $this->applySearchFilter($query, $filters['search']);
        }
    }

    /**
     * Apply search filter including phone number handling
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     */
    private function applySearchFilter($query, string $searchTerm): void
    {
        $search = '%'.$searchTerm.'%';

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

            if (preg_match('/^[0-9\s\-\+]+$/', $searchTerm)) {
                $formattedPhone = $this->formatSearchStringForPhoneSearch($searchTerm);

                $q->orWhereRaw("REPLACE(REPLACE(primary_phone, ' ', ''), '-', '') LIKE ?", [$formattedPhone])
                    ->orWhereRaw("REPLACE(REPLACE(transaction_phone, ' ', ''), '-', '') LIKE ?", [$formattedPhone]);
            }
        });
    }

    /**
     * Show single fundi with caching
     */
    public function show(Request $request, string $unique_id): JsonResponse
    {
        if (! preg_match('/^(FND\d{6,}|F\d{12})$/', $unique_id)) {
            return $this->errorResponse('Invalid fundi ID format', null, 400);
        }

        // Not operator-scoped: buildFundiDetailData() applies no district/authorization
        // filtering, so the response is identical for every operator. Keying on unique_id
        // alone keeps this in sync with clearFundiCaches()'s invalidation key.
        $cacheKey = $this->generateCacheKey('fundi.show', [
            'unique_id' => $unique_id,
        ]);

        $includeTransactions = $request->boolean('include_transactions');

        $cachedData = $this->rememberWithTags(
            $cacheKey,
            $this->getCacheTTL('medium'),
            fn () => $this->buildFundiDetailData($unique_id, $includeTransactions),
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
     */
    private function buildFundiDetailData(string $unique_id, bool $includeTransactions = false): array
    {
        $fundi = Fundi::where('unique_id', $unique_id)->first();

        if (! $fundi) {
            return [
                'error' => true,
                'message' => 'Fundi not found',
                'status' => 404,
            ];
        }

        $fundi->load([
            'ward.district.region',
            'creator',
            'approver',
            'rejector',
        ]);

        if ($includeTransactions) {
            $fundi->load(['transactions' => function ($query) {
                $query->latest()->limit(10);
            }])
                ->loadCount('transactions')
                ->loadSum('transactions', 'amount');
        }

        return (new FundiResource($fundi))->resolve();
    }

    /**
     * Create new fundi
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'fname' => 'required|string|max:255',
            'mname' => 'nullable|string|max:255',
            'lname' => 'required|string|max:255',
            'gender' => 'required|in:male,female',
            'nida_number' => 'nullable|string|max:20',
            'primary_phone' => [
                'required',
                'string',
                'max:15',
                'regex:/^\+?[0-9\s\-]+$/',
            ],
            'transaction_phone' => [
                'required',
                'string',
                'max:15',
                'regex:/^\+?[0-9\s\-]+$/',
            ],
            'ward_id' => [
                'required',
                'integer',
                'exists:wards,id',
                function ($attribute, $value, $fail) {
                    if (! $this->isWardInOperatorDistricts($value)) {
                        $fail('The selected ward is not available in your operating districts.');
                    }
                },
            ],
            'ranking' => 'nullable|string|in:fundi_kuu,fundi_msaidizi',
            'profile_photo' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
            'profile_photo_url' => 'nullable|string|url',
            'profile_photo_public_id' => 'nullable|string',
        ]);

        // Check for duplicates
        $duplicateErrors = $this->checkForDuplicateFundi($request);
        if (! empty($duplicateErrors)) {
            return $this->errorResponse(json_encode($duplicateErrors), null, 422);
        }

        DB::beginTransaction();
        try {
            $data = $request->except('profile_photo');
            $data['unique_id'] = $this->generateUniqueId();
            $data['created_by'] = $this->getOperator()->id;
            $data['status'] = 'active';

            // Handle profile photo
            if ($request->filled('profile_photo_url') && $request->filled('profile_photo_public_id')) {
                $data['profile_photo'] = $request->profile_photo_url;
                $data['profile_photo_public_id'] = $request->profile_photo_public_id;
            } elseif ($request->hasFile('profile_photo')) {
                $uploadResult = $this->cloudinaryService->upload(
                    $request->file('profile_photo'),
                    'FundiApp/Fundis'
                );

                if ($uploadResult) {
                    $data['profile_photo'] = $uploadResult['url'];
                    $data['profile_photo_public_id'] = $uploadResult['public_id'];
                }
            }

            $fundi = Fundi::create($data);
            $fundi->load(['ward.district.region', 'creator']);

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
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse('Failed to register fundi. Please try again.', null, 500);
        }
    }

    /**
     * Update fundi
     */
    public function update(Request $request, Fundi $fundi): JsonResponse
    {
        if (! $this->authorizeOperatorAccess($fundi)) {
            return $this->errorResponse('Unauthorized access', null, 403);
        }

        $isApproved = $fundi->approved_at &&
            (! $fundi->rejected_at || $fundi->approved_at > $fundi->rejected_at);

        if ($isApproved) {
            $allowedFields = ['fname', 'mname', 'lname', 'profile_photo_url', 'profile_photo_public_id'];
            $data = $request->only($allowedFields);

            $validator = Validator::make($data, [
                'fname' => 'sometimes|string|max:255',
                'mname' => 'sometimes|nullable|string|max:255',
                'lname' => 'sometimes|string|max:255',
                'profile_photo_url' => 'sometimes|nullable|url',
                'profile_photo_public_id' => 'sometimes|nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors()->first(), 422);
            }

            $fundi->update($data);
            activity('fundi_update', 'Updated approved fundi (restricted fields)', $fundi);

            $this->clearFundiCaches($fundi->unique_id);

            return $this->successResponse(
                new FundiResource($fundi),
                'Fundi updated successfully'
            );
        }

        $validated = $this->validateFundiUpdateData($request, $fundi);

        // Check for duplicates (excluding current fundi)
        $duplicateErrors = $this->checkForDuplicateFundi($request, $fundi->id);
        if (! empty($duplicateErrors)) {
            return $this->errorResponse(json_encode($duplicateErrors), null, 422);
        }

        DB::beginTransaction();
        try {
            $this->updateFundiData($validated, $request, $fundi);

            DB::commit();

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
                'trace' => $e->getTraceAsString(),
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

        if (
            $request->hasFile('profile_photo') ||
            ($request->filled('profile_photo_url') && $request->filled('profile_photo_public_id'))
        ) {
            $this->handleProfilePhoto($request, $data, $fundi);
        }

        $fundi->update($data);

        $fundi->load([
            'ward.district.region',
            'creator',
            'approver',
        ]);
    }

    /**
     * Approve fundi
     */
    public function approve(Request $request, Fundi $fundi): JsonResponse
    {
        if (! $this->authorizeOperatorAccess($fundi)) {
            return $this->errorResponse('Unauthorized access', null, 403);
        }

        DB::beginTransaction();
        try {
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

            $this->clearFundiCaches($fundi->unique_id);

            return $this->successResponse(
                new FundiResource($fundi),
                'Fundi approved successfully'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to approve fundi', [
                'fundi_id' => $fundi->id,
                'error' => $e->getMessage(),
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
     */
    public function reject(Request $request, Fundi $fundi): JsonResponse
    {
        if (! $this->authorizeOperatorAccess($fundi)) {
            return $this->errorResponse('Unauthorized access', null, 403);
        }

        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        DB::beginTransaction();
        try {
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

            $this->clearFundiCaches($fundi->unique_id);

            return $this->successResponse(
                new FundiResource($fundi),
                'Fundi rejected successfully'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to reject fundi', [
                'fundi_id' => $fundi->id,
                'error' => $e->getMessage(),
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
     */
    public function searchForSiteVisit(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'query' => 'required|string|min:2|max:100',
            'ward_id' => 'nullable|integer|exists:wards,id',
        ]);

        if (isset($validated['ward_id'])) {
            if (! $this->isWardInOperatorDistricts($validated['ward_id'])) {
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
            fn () => $this->buildSearchResultsData($validated),
            [self::CACHE_TAGS['fundis']]
        );

        return $this->successResponse(
            $cachedData,
            'Search results retrieved successfully'
        );
    }

    /**
     * Build search results data for caching
     */
    private function buildSearchResultsData(array $validated): array
    {
        $query = Fundi::where('status', 'active')
            ->with(['ward.district.region']);

        if (! isRoot() && ! isAdministrator()) {
            $districtIds = $this->getOperatorDistricts();
            $query->whereHas('ward.district', fn ($q) => $q->whereIn('id', $districtIds));
        }

        if (isset($validated['ward_id'])) {
            $query->where('ward_id', $validated['ward_id']);
        }

        $this->applySearchFilter($query, $validated['query']);

        $fundis = $query->limit(20)->get();

        return FundiMinimalResource::collection($fundis)->resolve();
    }

    /**
     * Soft delete fundi
     */
    public function destroy(Fundi $fundi): JsonResponse
    {
        if (! $this->authorizeOperatorAccess($fundi)) {
            return $this->errorResponse('Unauthorized access', null, 403);
        }

        DB::beginTransaction();
        try {
            $fundi->delete();

            DB::commit();

            $this->clearFundiCaches($fundi->unique_id);

            return $this->successResponse(
                null,
                'Fundi deleted successfully'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete fundi', [
                'fundi_id' => $fundi->id,
                'error' => $e->getMessage(),
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
     */
    public function restore(int $id): JsonResponse
    {
        $fundi = Fundi::onlyTrashed()->findOrFail($id);

        if (! $this->authorizeOperatorAccess($fundi)) {
            return $this->errorResponse('Unauthorized access', null, 403);
        }

        DB::beginTransaction();
        try {
            $fundi->restore();

            DB::commit();

            $this->clearFundiCaches($fundi->unique_id);

            return $this->successResponse(
                new FundiResource($fundi),
                'Fundi restored successfully'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to restore fundi', [
                'fundi_id' => $id,
                'error' => $e->getMessage(),
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
     */
    private function validateFundiData(Request $request): array
    {
        return $request->validate([
            'fname' => 'required|string|max:255',
            'mname' => 'nullable|string|max:255',
            'lname' => 'required|string|max:255',
            'gender' => 'required|in:male,female',
            'nida_number' => 'nullable|string|max:20',
            'primary_phone' => [
                'required',
                'string',
                'max:15',
                'regex:/^\+?[0-9\s\-]+$/',
            ],
            'transaction_phone' => [
                'required',
                'string',
                'max:15',
                'regex:/^\+?[0-9\s\-]+$/',
            ],
            'ward_id' => [
                'required',
                'integer',
                'exists:wards,id',
                function ($attribute, $value, $fail) {
                    if (! $this->isWardInOperatorDistricts($value)) {
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
     */
    private function validateFundiUpdateData(Request $request, Fundi $fundi): array
    {
        return $request->validate([
            'fname' => 'required|string|max:255',
            'mname' => 'nullable|string|max:255',
            'lname' => 'required|string|max:255',
            'gender' => 'required|in:male,female',
            'nida_number' => 'nullable|string|max:20',
            'primary_phone' => [
                'required',
                'string',
                'max:15',
                'regex:/^\+?[0-9\s\-]+$/',
            ],
            'transaction_phone' => [
                'required',
                'string',
                'max:15',
                'regex:/^\+?[0-9\s\-]+$/',
            ],
            'ward_id' => [
                'required',
                'integer',
                'exists:wards,id',
                function ($attribute, $value, $fail) {
                    if (! $this->isWardInOperatorDistricts($value)) {
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
     */
    private function formatSearchStringForPhoneSearch(string $searchTerm): string
    {
        $digitsOnly = preg_replace('/\D/', '', $searchTerm);
        $digitsOnly = ltrim($digitsOnly, '0');

        return '%+255'.$digitsOnly.'%';
    }

    /**
     * Check if operator has access to fundi
     */
    private function authorizeOperatorAccess(Fundi $fundi): bool
    {
        $operator = $this->getOperator();

        return $operator && $operator->can('update', $fundi);
    }

    /**
     * Check for duplicate fundi based on business rules
     * Returns array of error messages if duplicates found, empty array otherwise
     *
     * Rules:
     * 1. Primary phone must be entirely unique
     * 2. Transaction phone must be entirely unique
     * 3. NIDA must be unique (if provided)
     * 4. Combination of fname + lname + ward_id must be unique
     *
     * @param  int|null  $excludeId  - ID to exclude for updates
     */
    private function checkForDuplicateFundi(Request $request, ?int $excludeId = null): array
    {
        $errors = [];

        // Rule 1: Primary phone must be entirely unique
        $primaryPhoneExists = Fundi::where('primary_phone', $request->primary_phone)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->exists();

        if ($primaryPhoneExists) {
            $errors['primary_phone'] = 'This primary phone number is already registered.';
        }

        // Rule 2: Transaction phone must be entirely unique
        $transactionPhoneExists = Fundi::where('transaction_phone', $request->transaction_phone)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->exists();

        if ($transactionPhoneExists) {
            $errors['transaction_phone'] = 'This transaction phone number is already registered.';
        }

        // Rule 3: NIDA must be unique (if provided)
        if ($request->filled('nida_number')) {
            $nidaExists = Fundi::where('nida_number', $request->nida_number)
                ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
                ->exists();

            if ($nidaExists) {
                $errors['nida_number'] = 'This NIDA number is already registered.';
            }
        }

        // Rule 4: fname + lname + ward_id must be unique
        $nameCombinationExists = Fundi::where('fname', $request->fname)
            ->where('lname', $request->lname)
            ->where('ward_id', $request->ward_id)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->exists();

        if ($nameCombinationExists) {
            $errors['fname'] = 'A fundi with this name already exists in the selected ward.';
        }

        return $errors;
    }

    /**
     * Check if ward is in operator districts
     */
    private function isWardInOperatorDistricts(int $wardId): bool
    {
        if (isRoot() || isAdministrator()) {
            return true;
        }

        return Ward::where('id', $wardId)
            ->whereHas('district', function ($query) {
                $query->whereIn('id', $this->getOperatorDistricts());
            })
            ->exists();
    }

    /**
     * Create fundi with all data
     */
    private function createFundi(array $validated, Request $request): Fundi
    {
        $data = collect($validated)->except('profile_photo')->toArray();
        $data['unique_id'] = $this->generateUniqueId();
        $data['created_by'] = $this->getOperator()->id;
        $data['status'] = 'active';

        if (
            $request->hasFile('profile_photo') ||
            ($request->filled('profile_photo_url') && $request->filled('profile_photo_public_id'))
        ) {
            $this->handleProfilePhoto($request, $data);
        }

        $fundi = Fundi::create($data);
        $fundi->load(['ward.district.region', 'creator']);

        return $fundi;
    }

    /**
     * Handle profile photo upload/assignment
     */
    private function handleProfilePhoto(Request $request, array &$data, ?Fundi $fundi = null): void
    {
        if ($request->filled('profile_photo_url') && $request->filled('profile_photo_public_id')) {
            if (
                $fundi &&
                $fundi->profile_photo_public_id &&
                $fundi->profile_photo_public_id !== $request->profile_photo_public_id
            ) {
                try {
                    $this->cloudinaryService->delete($fundi->profile_photo_public_id);
                } catch (\Exception $e) {
                    Log::warning('Failed to delete old profile photo', [
                        'public_id' => $fundi->profile_photo_public_id,
                        'error' => $e->getMessage(),
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
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        }
    }

    /**
     * Generate unique ID for fundi — delegates to the single shared generator on Fundi.
     */
    private function generateUniqueId(): string
    {
        return Fundi::generateUniqueId();
    }
}

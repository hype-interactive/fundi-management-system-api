<?php

namespace App\Http\Controllers\API;

use App\Models\Ward;
use App\Models\Fundi;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Resources\FundiResource;
use Illuminate\Support\Facades\Storage;
use App\Services\CloudinaryImageService;
use App\Http\Resources\FundiMinimalResource;

class FundiController extends BaseAPIController
{
    protected $cloudinaryService;

    public function __construct(CloudinaryImageService $cloudinaryService)
    {
        $this->cloudinaryService = $cloudinaryService;
    }

    public function index(Request $request): JsonResponse
    {
        $query = Fundi::with(['ward.district.region', 'creator'])
            ->whereHas('ward.district', function ($q) {
                $q->whereIn('id', $this->getOperatorDistricts());
            });

        // Status filter (active/inactive)
        if ($request->status) {
            $query->where('status', $request->status);
        }

        // Approval status filter using the model scopes
        if ($request->approval_status) {
            switch (strtolower($request->approval_status)) {
                case 'approved':
                    $query->approved();
                    break;
                case 'rejected':
                    $query->rejected();
                    break;
                case 'pending':
                    $query->pending();
                    break;
                default:
                    Log::warning('Invalid approval status filter provided', [
                        'status' => $request->approval_status,
                        'user_id' => $this->getOperator()->id,
                    ]);
                    break;
            }
        }

        // Ward filter
        if ($request->ward_id) {
            $query->where('ward_id', $request->ward_id);
        }

        // Search filter
        if ($request->search) {
            $search = '%' . $request->search . '%';
            $query->where(function ($q) use ($search) {
                $q->where('fname', 'like', $search)
                    ->orWhere('mname', 'like', $search)  // Added middle name
                    ->orWhere('lname', 'like', $search)
                    ->orWhere('unique_id', 'like', $search)
                    ->orWhere('primary_phone', 'like', $search)
                    ->orWhere('nida_number', 'like', $search)
                    // Search full name as well
                    ->orWhereRaw("CONCAT(COALESCE(fname, ''), ' ', COALESCE(mname, ''), ' ', COALESCE(lname, '')) LIKE ?", [$search]);
            });
        }

        // Add sorting for better user experience
        $query->orderBy('created_at', 'desc');

        $fundis = $query->paginate($request->per_page ?? 15);

        return $this->successResponse([
            'fundis' => FundiMinimalResource::collection($fundis->items()),
            'pagination' => [
                'current_page' => $fundis->currentPage(),
                'last_page' => $fundis->lastPage(),
                'per_page' => $fundis->perPage(),
                'total' => $fundis->total(),
                // 'from' => $fundis->firstItem(),
                // 'to' => $fundis->lastItem(),
            ],
        ]);
    }

    public function show(Fundi $fundi): JsonResponse
    {
        // Check if operator has access to this fundi
        if (!$this->getOperatorDistricts()->contains($fundi->ward->district_id)) {
            return $this->errorResponse('Unauthorized access', null, 403);
        }

        $fundi->load(['ward.district.region', 'creator', 'approver', 'transactions'])
            ->loadCount('transactions')
            ->loadSum('transactions', 'amount');

        return $this->successResponse(
            new FundiResource($fundi),
            'Fundi details retrieved successfully'
        );
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'fname' => 'required|string|max:255',
            'mname' => 'nullable|string|max:255',
            'lname' => 'required|string|max:255',
            'gender' => 'required|in:male,female',
            'nida_number' => 'required|string|unique:fundis,nida_number',
            'primary_phone' => 'required|string|max:15',
            'transaction_phone' => 'required|string|max:15',
            'ward_id' => [
                'required',
                'integer',
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
            'ranking' => 'nullable|string|in:fundi_kuu,fundi_msaidizi',
            'profile_photo' => 'nullable|image|max:2048',
            'profile_photo_url' => 'nullable|string|url', // For Cloudinary URL from Flutter
            'profile_photo_public_id' => 'nullable|string', // For Cloudinary public_id from Flutter
        ]);

        DB::beginTransaction();
        try {
            $data = $request->except('profile_photo');
            $data['unique_id'] = $this->generateUniqueId();
            $data['created_by'] = $this->getOperator()->id;
            $data['status'] = 'active';

            // Handle profile photo
            $profilePhotoUrl = null;
            $profilePhotoPublicId = null;

            // Priority 1: Use Cloudinary URL and public_id from Flutter (direct upload)
            if ($request->filled('profile_photo_url') && $request->filled('profile_photo_public_id')) {
                $profilePhotoUrl = $request->profile_photo_url;
                $profilePhotoPublicId = $request->profile_photo_public_id;
            }
            // Priority 2: Upload file to Cloudinary if provided
            elseif ($request->hasFile('profile_photo')) {
                $uploadResult = $this->cloudinaryService->upload(
                    $request->file('profile_photo'),
                    'FundiApp/Fundis'
                );

                if ($uploadResult) {
                    $profilePhotoUrl = $uploadResult['url'];
                    $profilePhotoPublicId = $uploadResult['public_id'];
                }
            }

            // Save profile photo data if available
            if ($profilePhotoUrl) {
                $data['profile_photo'] = $profilePhotoUrl;
                $data['profile_photo_public_id'] = $profilePhotoPublicId;
            }

            $fundi = Fundi::create($data);
            $fundi->load(['ward.district.region', 'creator']);

            DB::commit();

            return $this->successResponse(
                new FundiResource($fundi),
                'Fundi registered successfully',
                201
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to register fundi: ' . $e->getMessage());
        }
    }

    public function update(Request $request, Fundi $fundi): JsonResponse
    {
        // Check if operator has access to this fundi
        if (!$this->getOperatorDistricts()->contains($fundi->ward->district_id)) {
            return $this->errorResponse('Unauthorized access', null, 403);
        }

        // Prevent updating approved fundis (optional business rule)
        if ($fundi->approved_at) {
            return $this->errorResponse('Cannot update approved fundi', null, 422);
        }

        $request->validate([
            'fname' => 'sometimes|required|string|max:255',
            'mname' => 'nullable|string|max:255',
            'lname' => 'sometimes|required|string|max:255',
            'gender' => 'sometimes|required|in:male,female',
            'nida_number' => [
                'sometimes',
                'required',
                'string',
                Rule::unique('fundis', 'nida_number')->ignore($fundi->id)
            ],
            'primary_phone' => 'sometimes|required|string|max:15',
            'transaction_phone' => 'sometimes|required|string|max:15',
            'ward_id' => [
                'sometimes',
                'required',
                'integer',
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
            'ranking' => 'nullable|string|in:fundi_kuu,fundi_msaidizi',
            'profile_photo' => 'nullable|image|max:2048',
            'profile_photo_url' => 'nullable|string|url', // For Cloudinary URL from Flutter
            'profile_photo_public_id' => 'nullable|string', // For Cloudinary public_id from Flutter
            'status' => 'sometimes|required|in:active,inactive',
        ]);

        DB::beginTransaction();
        try {
            $data = $request->except('profile_photo');

            // Handle profile photo update
            $profilePhotoUrl = $fundi->profile_photo_url;
            $profilePhotoPublicId = $fundi->profile_photo_public_id;

            // Priority 1: Use new Cloudinary URL and public_id from Flutter
            if ($request->filled('profile_photo_url') && $request->filled('profile_photo_public_id')) {
                // If there's a new image, delete the old one
                if ($fundi->profile_photo_public_id &&
                    $fundi->profile_photo_public_id !== $request->profile_photo_public_id) {
                    $this->cloudinaryService->delete($fundi->profile_photo_public_id);
                }

                $profilePhotoUrl = $request->profile_photo_url;
                $profilePhotoPublicId = $request->profile_photo_public_id;
            }
            // Priority 2: Upload new file to Cloudinary if provided
            elseif ($request->hasFile('profile_photo')) {
                $uploadResult = $this->cloudinaryService->update(
                    $request->file('profile_photo'),
                    $fundi->profile_photo_public_id, // This will delete the old image
                    'FundiApp/Fundis'
                );

                if ($uploadResult) {
                    $profilePhotoUrl = $uploadResult['url'];
                    $profilePhotoPublicId = $uploadResult['public_id'];
                }
            }

            // Save profile photo data if available
            if ($profilePhotoUrl) {
                $data['profile_photo'] = $profilePhotoUrl;
                $data['profile_photo_public_id'] = $profilePhotoPublicId;
            } else {
                // If no new photo is provided, keep the existing one
                $data['profile_photo'] = $fundi->profile_photo;
                $data['profile_photo_public_id'] = $fundi->profile_photo_public_id;
            }

            $fundi->update($data);
            $fundi->load(['ward.district.region', 'creator']);

            DB::commit();

            return $this->successResponse(
                new FundiResource($fundi),
                'Fundi updated successfully'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to update fundi: ' . $e->getMessage());
        }
    }

    public function approve(Request $request, Fundi $fundi): JsonResponse
    {
        // Check if operator has access to this fundi
        if (!$this->getOperatorDistricts()->contains($fundi->ward->district_id)) {
            return $this->errorResponse('Unauthorized access', null, 403);
        }

        if ($fundi->approved_at) {
            return $this->errorResponse('Fundi is already approved', null, 422);
        }

        if ($fundi->rejected_at) {
            return $this->errorResponse('Cannot approve rejected fundi', null, 422);
        }

        DB::beginTransaction();
        try {
            $fundi->update([
                'approved_at' => now(),
                'approved_by' => $this->getOperator()->id,
                'rejected_at' => null,
                'rejected_by' => null,
                'rejection_reason' => null,
            ]);

            $fundi->load(['ward.district.region', 'creator', 'approver']);

            DB::commit();

            return $this->successResponse(
                new FundiResource($fundi),
                'Fundi approved successfully'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to approve fundi: ' . $e->getMessage());
        }
    }

    public function reject(Request $request, Fundi $fundi): JsonResponse
    {
        // Check if operator has access to this fundi
        if (!$this->getOperatorDistricts()->contains($fundi->ward->district_id)) {
            return $this->errorResponse('Unauthorized access', null, 403);
        }

        if ($fundi->rejected_at) {
            return $this->errorResponse('Fundi is already rejected', null, 422);
        }

        $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        DB::beginTransaction();
        try {
            $fundi->update([
                'rejected_at' => now(),
                'rejected_by' => $this->getOperator()->id,
                'rejection_reason' => $request->rejection_reason,
                'approved_at' => null,
                'approved_by' => null,
            ]);

            $fundi->load(['ward.district.region', 'creator', 'rejector']);

            DB::commit();

            return $this->successResponse(
                new FundiResource($fundi),
                'Fundi rejected successfully'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to reject fundi: ' . $e->getMessage());
        }
    }

    public function destroy(Fundi $fundi): JsonResponse
    {
        // Check if operator has access to this fundi
        if (!$this->getOperatorDistricts()->contains($fundi->ward->district_id)) {
            return $this->errorResponse('Unauthorized access', null, 403);
        }

        // Prevent deletion if fundi has transactions
        if ($fundi->transactions()->count() > 0) {
            return $this->errorResponse('Cannot delete fundi with existing transactions', null, 422);
        }

        DB::beginTransaction();
        try {
            // Delete profile photo from Cloudinary if exists
            if ($fundi->profile_photo_public_id) {
                $this->cloudinaryService->delete($fundi->profile_photo_public_id);
            }

            $fundi->delete(); // This will soft delete due to SoftDeletes trait

            DB::commit();

            return $this->successResponse(
                null,
                'Fundi deleted successfully'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to delete fundi: ' . $e->getMessage());
        }
    }

    public function restore($id): JsonResponse
    {
        $fundi = Fundi::onlyTrashed()->findOrFail($id);

        // Check if operator has access to this fundi
        if (!$this->getOperatorDistricts()->contains($fundi->ward->district_id)) {
            return $this->errorResponse('Unauthorized access', null, 403);
        }

        $fundi->restore();
        $fundi->load(['ward.district.region', 'creator']);

        return $this->successResponse(
            new FundiResource($fundi),
            'Fundi restored successfully'
        );
    }

    public function searchForSiteVisit(Request $request): JsonResponse
    {
        $request->validate([
            'search' => 'required|string|min:2',
            'ward_id' => 'nullable|exists:wards,id',
        ]);

        $query = Fundi::where('status', 'active')
            ->whereNotNull('approved_at')
            ->whereHas('ward.district', function ($q) {
                $q->whereIn('id', $this->getOperatorDistricts());
            });

        if ($request->ward_id) {
            $query->where('ward_id', $request->ward_id);
        }

        $search = '%' . $request->search . '%';
        $query->where(function ($q) use ($search) {
            $q->where('fname', 'like', $search)
                ->orWhere('lname', 'like', $search)
                ->orWhere('unique_id', 'like', $search)
                ->orWhere('primary_phone', 'like', $search);
        });

        $fundis = $query->limit(20)->get();

        return $this->successResponse(
            FundiMinimalResource::collection($fundis),
            'Search results retrieved successfully'
        );
    }

    private function generateUniqueId(): string
    {
        do {
            $id = 'FND' . str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
        } while (Fundi::where('unique_id', $id)->exists());

        return $id;
    }
}

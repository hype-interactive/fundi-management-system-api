<?php

namespace App\Http\Controllers\API;

use App\Models\Ward;
use App\Models\Fundi;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\FundiResource;
use App\Http\Resources\FundiMinimalResource;

class FundiController extends BaseAPIController
{
    public function index(Request $request): JsonResponse
    {
        $query = Fundi::with(['ward.district.region', 'creator'])
            ->whereHas('ward.district', function ($q) {
                $q->whereIn('id', $this->getOperatorDistricts());
            });

        // Filters
        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->approval_status) {
            switch ($request->approval_status) {
                case 'approved':
                    $query->whereNotNull('approved_at');
                    break;
                case 'rejected':
                    $query->whereNotNull('rejected_at');
                    break;
                case 'pending':
                    $query->whereNull('approved_at')->whereNull('rejected_at');
                    break;
            }
        }

        if ($request->ward_id) {
            $query->where('ward_id', $request->ward_id);
        }

        if ($request->search) {
            $search = '%' . $request->search . '%';
            $query->where(function ($q) use ($search) {
                $q->where('fname', 'like', $search)
                    ->orWhere('lname', 'like', $search)
                    ->orWhere('unique_id', 'like', $search)
                    ->orWhere('primary_phone', 'like', $search)
                    ->orWhere('nida_number', 'like', $search);
            });
        }

        $fundis = $query->paginate($request->per_page ?? 15);

        return $this->successResponse([
            'fundis' => FundiMinimalResource::collection($fundis->items()),
            'pagination' => [
                'current_page' => $fundis->currentPage(),
                'last_page' => $fundis->lastPage(),
                'per_page' => $fundis->perPage(),
                'total' => $fundis->total(),
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
        ]);

        DB::beginTransaction();
        try {
            $data = $request->except('profile_photo');
            $data['unique_id'] = $this->generateUniqueId();
            $data['created_by'] = $this->getOperator()->id;
            $data['status'] = 'active';

            // Handle profile photo upload
            if ($request->hasFile('profile_photo')) {
                $data['profile_photo'] = $request->file('profile_photo')
                    ->store('fundis/profiles', 'public');
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

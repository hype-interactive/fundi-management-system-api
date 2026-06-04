<?php

namespace App\Http\Controllers\API;

use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends BaseAPIController
{
    public function index(Request $request): JsonResponse
    {
        $query = Transaction::with(['fundi', 'disbursementBatch'])
            ->whereHas('fundi.ward.district', function ($q) {
                $q->whereIn('id', $this->getOperatorDistricts());
            });

        // Filters
        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->fundi_id) {
            $query->where('fundi_id', $request->fundi_id);
        }

        if ($request->date_from) {
            $query->whereDate('processed_at', '>=', $request->date_from);
        }

        if ($request->date_to) {
            $query->whereDate('processed_at', '<=', $request->date_to);
        }

        $transactions = $query->orderBy('processed_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return $this->successResponse([
            'transactions' => TransactionResource::collection($transactions->items()),
            'pagination' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
            ],
            'summary' => [
                'total_amount' => formatCurrency($transactions->sum('amount')),
                'successful_count' => $transactions->where('status', 'completed')->count(),
                'failed_count' => $transactions->where('status', 'failed')->count(),
            ],
        ]);
    }

    public function show(Transaction $transaction): JsonResponse
    {
        // Check if operator has access to this transaction
        if (! $this->getOperatorDistricts()->contains($transaction->fundi->ward->district_id)) {
            return $this->errorResponse('Unauthorized access', null, 403);
        }

        $transaction->load(['fundi', 'disbursementBatch']);

        return $this->successResponse(
            new TransactionResource($transaction),
            'Transaction details retrieved successfully'
        );
    }
}

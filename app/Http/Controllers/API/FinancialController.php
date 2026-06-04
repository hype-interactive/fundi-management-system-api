<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Fundi;
use App\Models\Transaction;
use App\Services\Financial\FinancialManagementService;
use App\Services\Payment\Selcom\FinancialSelcomService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinancialController extends Controller
{
    private FinancialManagementService $financialService;

    private FinancialSelcomService $selcomService;

    public function __construct(
        FinancialManagementService $financialService,
        FinancialSelcomService $selcomService
    ) {
        $this->financialService = $financialService;
        $this->selcomService = $selcomService;
    }

    /**
     * Get financial dashboard data
     */
    public function dashboard(): JsonResponse
    {
        try {
            $summary = $this->financialService->getDashboardSummary();

            return response()->json([
                'success' => true,
                'data' => $summary,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load dashboard data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create budget allocation
     */
    public function createBudget(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'total_amount' => 'required|numeric|min:1000|max:1000000000',
        ]);

        try {
            $budget = $this->financialService->createBudgetAllocation($validated);

            return response()->json([
                'success' => true,
                'message' => 'Budget allocation created successfully',
                'data' => $budget->load('creator'),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create budget allocation',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Create disbursement batch
     */
    public function createDisbursement(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'budget_allocation_id' => 'required|exists:budget_allocations,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'disbursement_date' => 'nullable|date|after_or_equal:today',
            'recipients' => 'required|array|min:1|max:1000',
            'recipients.*.fundi_id' => 'required|exists:fundis,id',
            'recipients.*.amount' => 'required|numeric|min:100|max:10000000',
        ]);

        try {
            // Calculate total amount
            $totalAmount = array_sum(array_column($validated['recipients'], 'amount'));
            $validated['total_amount'] = $totalAmount;

            // Validate fundis are eligible
            $fundiIds = array_column($validated['recipients'], 'fundi_id');
            $ineligibleFundis = [];

            foreach ($fundiIds as $fundiId) {
                $fundi = Fundi::find($fundiId);
                if ($fundi) {
                    $validation = $this->selcomService->validateFundiForDisbursement($fundi);
                    if (! $validation['valid']) {
                        $ineligibleFundis[] = [
                            'fundi_id' => $fundiId,
                            'name' => $fundi->full_name,
                            'issues' => array_keys(array_filter($validation['validation'], fn ($v) => ! $v)),
                        ];
                    }
                }
            }

            if (! empty($ineligibleFundis)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Some fundis are not eligible for disbursement',
                    'ineligible_fundis' => $ineligibleFundis,
                ], 422);
            }

            $batch = $this->financialService->processDisbursementBatch(
                $validated['budget_allocation_id'],
                $validated
            );

            return response()->json([
                'success' => true,
                'message' => 'Disbursement batch created and processing started',
                'data' => $batch->load(['budgetAllocation', 'creator', 'transactions.fundi']),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create disbursement batch',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Validate fundi for disbursement
     */
    public function validateFundi(Fundi $fundi): JsonResponse
    {
        try {
            $validation = $this->selcomService->validateFundiForDisbursement($fundi);

            return response()->json([
                'success' => true,
                'data' => [
                    'fundi' => $fundi,
                    'validation' => $validation,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to validate fundi',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get eligible fundis for disbursement
     */
    public function getEligibleFundis(Request $request): JsonResponse
    {
        try {
            $search = $request->get('search', '');
            $limit = min($request->get('limit', 50), 100);

            $query = Fundi::whereNotNull('approved_at')
                ->whereNotNull('primary_phone');

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->whereRaw("concat(fname, ' ', lname) like ?", ["%{$search}%"])
                        ->orWhere('primary_phone', 'like', "%{$search}%");
                });
            }

            $fundis = $query->limit($limit)->get();

            // Validate each fundi
            $eligibleFundis = [];
            foreach ($fundis as $fundi) {
                $validation = $this->selcomService->validateFundiForDisbursement($fundi);
                if ($validation['valid']) {
                    $eligibleFundis[] = [
                        'id' => $fundi->id,
                        'name' => $fundi->full_name,
                        'phone' => $fundi->primary_phone,
                        'ward' => $fundi->ward->name ?? 'N/A',
                        'wallet_type' => $validation['wallet_type'],
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'data' => $eligibleFundis,
                'total' => count($eligibleFundis),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get eligible fundis',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Retry failed transaction
     */
    public function retryTransaction(Transaction $transaction): JsonResponse
    {
        try {
            $this->financialService->retryTransaction($transaction);

            return response()->json([
                'success' => true,
                'message' => 'Transaction retry initiated',
                'data' => $transaction->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retry transaction',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Query transaction status from Selcom
     */
    public function queryTransactionStatus(Transaction $transaction): JsonResponse
    {
        try {
            $response = $this->selcomService->queryTransactionStatus(
                $transaction->transaction_reference
            );

            return response()->json([
                'success' => true,
                'message' => 'Transaction status queried successfully',
                'data' => [
                    'transaction' => $transaction,
                    'selcom_response' => $response,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to query transaction status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\Financial\FinancialManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SelcomCallbackController extends Controller
{
    private FinancialManagementService $financialService;

    public function __construct(FinancialManagementService $financialService)
    {
        $this->financialService = $financialService;
    }

    /**
     * Handle payment callback from Selcom
     */
    public function handlePaymentCallback(Request $request): JsonResponse
    {
        try {
            // Verify callback signature (optional but recommended)
            if (! $this->verifyCallbackSignature($request)) {
                Log::warning('Invalid Selcom callback signature', [
                    'ip' => $request->ip(),
                    'payload' => $request->all(),
                ]);

                return response()->json(['error' => 'Invalid signature'], 401);
            }

            $callbackData = $request->all();

            Log::info('Selcom payment callback received', $callbackData);

            // Process the callback
            $this->financialService->handlePaymentCallback($callbackData);

            return response()->json([
                'status' => 'success',
                'message' => 'Callback processed successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Selcom callback processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'callback_data' => $request->all(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Callback processing failed',
            ], 500);
        }
    }

    /**
     * Handle name lookup callback
     */
    public function handleNameLookupCallback(Request $request): JsonResponse
    {
        try {
            $callbackData = $request->all();

            Log::info('Selcom name lookup callback received', $callbackData);

            // You can store name lookup results if needed
            // This could be used for verification purposes

            return response()->json([
                'status' => 'success',
                'message' => 'Name lookup callback processed',
            ]);

        } catch (\Exception $e) {
            Log::error('Name lookup callback processing failed', [
                'error' => $e->getMessage(),
                'callback_data' => $request->all(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Callback processing failed',
            ], 500);
        }
    }

    /**
     * Verify callback signature (implement based on Selcom's signature method)
     */
    private function verifyCallbackSignature(Request $request): bool
    {
        // For now, return true. Implement actual signature verification
        // based on Selcom's documentation
        return true;

        /*
        $signature = $request->header('X-Selcom-Signature');
        $timestamp = $request->header('Timestamp');
        $payload = $request->getContent();

        if (!$signature || !$timestamp || !$payload) {
            return false;
        }

        // Verify timestamp is within acceptable range (5 minutes)
        if (abs(time() - strtotime($timestamp)) > 300) {
            return false;
        }

        // Verify HMAC signature
        $expectedSignature = hash_hmac('sha256', $payload, config('selcom.api_secret'));

        return hash_equals($expectedSignature, $signature);
        */
    }
}

<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\District;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class BaseAPIController extends Controller
{
    protected function successResponse($data = null, string $message = 'Success', int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    protected function errorResponse(string $message = 'Error', $errors = null, int $status = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }

    /**
     * Get the authenticated operator as a User model instance
     * This ensures we have access to Eloquent methods like load(), createToken(), etc.
     */
    protected function getOperator()
    {
        $userId = Auth::id();
        if (! $userId) {
            return null;
        }

        // Return User model instance instead of generic Auth::user()
        return User::find($userId);
    }

    /**
     * Get the districts IDs for the authenticated operator.
     * Root and administrators see all districts.
     */
    protected function getOperatorDistricts()
    {
        if (isRoot() || isAdministrator()) {
            return District::pluck('id');
        }

        $operator = $this->getOperator();
        if (! $operator) {
            return collect();
        }

        return $operator->districts->pluck('id');
    }

    /**
     * Check if the authenticated user is active
     */
    protected function isOperatorActive(): bool
    {
        $operator = $this->getOperator();
        if (! $operator) {
            return false;
        }

        return ! isset($operator->status) || $operator->status === 'active';
    }

    /**
     * Get authenticated operator with specific relationships loaded
     */
    protected function getOperatorWithRelations(array $relations = []): ?User
    {
        $operator = $this->getOperator();
        if (! $operator) {
            return null;
        }

        if (! empty($relations)) {
            $operator->load($relations);
        }

        return $operator;
    }
}

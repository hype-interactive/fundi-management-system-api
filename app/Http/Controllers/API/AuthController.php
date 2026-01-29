<?php

namespace App\Http\Controllers\API;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use App\Http\Resources\OperatorProfileResource;

class AuthController extends BaseAPIController
{
    public function login(Request $request): JsonResponse
    {
        Log::info('Login endpoint accessed', [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
        
        // Rate limiting for login attempts
        $key = 'login.' . $request->ip();
        // if (RateLimiter::tooManyAttempts($key, 5)) {
        //     $seconds = RateLimiter::availableIn($key);
        //     Log::warning('Too many login attempts', [
        //         'ip' => $request->ip(),
        //         'phone_number' => $request->input('phone_number'),
        //         'user_agent' => $request->userAgent(),
        //     ]);

        //     return $this->errorResponse(
        //         'Too many login attempts. Please try again in ' . ceil($seconds / 60) . ' minutes.',
        //         null,
        //         429
        //     );
        // }

        $request->validate([
            'phone_number' => 'required|string|max:20|regex:/^[\+]?[0-9\s\-\(\)]+$/',
            'password' => 'required|string',
        ], [
            'phone_number.required' => 'Phone number is required',
            'phone_number.regex' => 'Please enter a valid phone number. It should start with a + followed by country code and digits.',
            'password.required' => 'Password is required',
        ]);

        $credentials = [
            'phone_number' => $request->input('phone_number'),
            'password' => $request->input('password')
        ];

        // Log the login attempt (without password)
        Log::info('Login attempt', [
            'phone_number' => $credentials['phone_number'],
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // Find user by phone number first
        $user = User::where('phone', $credentials['phone_number'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            // Increment rate limiter on failed attempts
            RateLimiter::hit($key, 300); // 5 minutes decay

            // Log failed login attempt
            Log::warning('Failed login attempt', [
                'phone_number' => $credentials['phone_number'],
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return $this->errorResponse('Invalid credentials', null, 401);
        }

        // Check if user account is active/enabled
        if (isset($user->status) && $user->status !== 'active') {
            Log::warning('Login attempt on inactive account', [
                'user_id' => $user->id,
                'phone_number' => $user->phone_number,
                'status' => $user->status ?? 'unknown',
                'ip' => $request->ip(),
            ]);

            return $this->errorResponse('Account is not active', null, 403);
        }

        // Clear rate limiter on successful login
        RateLimiter::clear($key);

        try {
            // Log in the user
            Auth::login($user);

            // Create token with expiration using the User model instance
            $token = $user->createToken('mobile-app', [], now()->addDays(30))->accessToken;

            // Update last login timestamp if the field exists
            if ($user->getConnection()->getSchemaBuilder()->hasColumn($user->getTable(), 'last_login_at')) {
                $user->update(['last_login_at' => Carbon::now()]);
            }

            // Log successful login
            Log::info('Successful login', [
                'user_id' => $user->id,
                'phone_number' => $user->phone_number,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            // Load relationships using the User model instance
            $userWithRelations = $user->load(['role', 'districts.region']);

            return $this->successResponse([
                'user' => new OperatorProfileResource($userWithRelations),
                'token' => $token,
                'expires_at' => now()->addDays(30)->toISOString(),
            ], 'Login successful');

        } catch (\Exception $e) {
            // Log token creation failure
            Log::error('Token creation failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
            ]);

            Auth::logout();
            return $this->errorResponse('Login failed. Please try again.', $e->getMessage(), 500);
        }
    }

    public function profile(): JsonResponse
    {
        try {
            // Get the authenticated user as User model instance
            $userId = Auth::id();
            if (!$userId) {
                return $this->errorResponse('User not authenticated', null, 401);
            }

            // Fetch user using User model to get access to Eloquent methods
            $user = User::find($userId);

            if (!$user) {
                return $this->errorResponse('User not found', null, 404);
            }

            // Check if user account is still active
            if (isset($user->status) && $user->status !== 'active') {
                Log::warning('Profile access attempt on inactive account', [
                    'user_id' => $user->id,
                    'status' => $user->status ?? 'unknown',
                ]);

                return $this->errorResponse('Account is not active', null, 403);
            }

            // Load relationships with error handling
            $user->load(['role', 'districts.region']);

            // Load counts with proper error handling
            $user->loadCount([
                'createdFundis',
                'siteVisits',
                'createdFundis as pending_fundis_count' => function ($query) {
                    $query->whereNull('approved_at');
                },
                'siteVisits as pending_site_visits_count' => function ($query) {
                    $query->where('status', 'pending');
                },
            ]);

            return $this->successResponse(
                new OperatorProfileResource($user),
                'Profile retrieved successfully'
            );

        } catch (\Exception $e) {
            Log::error('Profile retrieval failed', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse('Failed to retrieve profile', null, 500);
        }
    }

    public function logout(): JsonResponse
    {
        try {
            $userId = Auth::id();

            if (!$userId) {
                return $this->errorResponse('User not authenticated', null, 401);
            }

            // Get user as User model instance
            $user = User::find($userId);

            if (!$user) {
                return $this->errorResponse('User not found', null, 401);
            }

            // Revoke current token
            $token = $user->token();
            if ($token) {
                $token->revoke();
            }

            // Log successful logout
            Log::info('User logged out', [
                'user_id' => $user->id,
                'phone_number' => $user->phone_number,
                'ip' => request()->ip(),
            ]);

            // Logout from Auth
            Auth::logout();

            return $this->successResponse(null, 'Logged out successfully');

        } catch (\Exception $e) {
            Log::error('Logout failed', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            // Even if logout fails, return success to avoid confusion
            return $this->successResponse(null, 'Logged out successfully');
        }
    }

    /**
     * Revoke all tokens for the current user (logout from all devices)
     */
    public function logoutAll(): JsonResponse
    {
        try {
            $userId = Auth::id();

            if (!$userId) {
                return $this->errorResponse('User not authenticated', null, 401);
            }

            // Get user as User model instance
            $user = User::find($userId);

            if (!$user) {
                return $this->errorResponse('User not found', null, 401);
            }

            // Revoke all tokens for this user
            $user->tokens()->delete();

            Log::info('User logged out from all devices', [
                'user_id' => $user->id,
                'phone_number' => $user->phone_number,
                'ip' => request()->ip(),
            ]);

            // Logout from Auth
            Auth::logout();

            return $this->successResponse(null, 'Logged out from all devices successfully');

        } catch (\Exception $e) {
            Log::error('Logout all failed', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Logout failed', null, 500);
        }
    }

    /**
     * Refresh the current token
     */
    public function refresh(): JsonResponse
    {
        try {
            $userId = Auth::id();

            if (!$userId) {
                return $this->errorResponse('User not authenticated', null, 401);
            }

            // Get user as User model instance
            $user = User::find($userId);

            if (!$user) {
                return $this->errorResponse('User not found', null, 401);
            }

            // Check if user account is still active
            if (isset($user->status) && $user->status !== 'active') {
                return $this->errorResponse('Account is not active', null, 403);
            }

            // Revoke current token
            $currentToken = $user->token();
            if ($currentToken) {
                $currentToken->revoke();
            }

            // Create new token
            $newToken = $user->createToken('mobile-app', [], now()->addDays(30))->accessToken;

            Log::info('Token refreshed', [
                'user_id' => $user->id,
                'ip' => request()->ip(),
            ]);

            return $this->successResponse([
                'token' => $newToken,
                'expires_at' => now()->addDays(30)->toISOString(),
            ], 'Token refreshed successfully');

        } catch (\Exception $e) {
            Log::error('Token refresh failed', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Token refresh failed', null, 500);
        }
    }
}

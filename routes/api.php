<?php

use App\Http\Controllers\API\AddressController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\DashboardController;
use App\Http\Controllers\API\FundiController;
use App\Http\Controllers\API\MasterDataController;
use App\Http\Controllers\API\SelcomCallbackController;
use App\Http\Controllers\API\SiteVisitController;
use App\Http\Controllers\API\TransactionController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::prefix('v1')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);

    // Public location endpoints (for registration, etc.)
    Route::prefix('public/locations')->group(function () {
        Route::get('/regions', [AddressController::class, 'getRegions'])
            ->name('api.public.regions');
        Route::get('/regions/{region_id}/districts', [AddressController::class, 'getDistricts'])
            ->name('api.public.districts')
            ->where('region_id', '[0-9]+');
    });
});

// Protected routes
Route::prefix('v1')->middleware(['auth:api'])->group(function () {

    // Authentication
    Route::prefix('auth')->group(function () {
        Route::get('/profile', [AuthController::class, 'profile']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });

    // Fundi Management
    Route::prefix('fundis')->group(function () {
        Route::get('/', [FundiController::class, 'index']);
        Route::post('/', [FundiController::class, 'store']);
        Route::get('/search', [FundiController::class, 'searchForSiteVisit']);
        Route::get('/{unique_id}', [FundiController::class, 'show']);
        Route::put('/{fundi}', [FundiController::class, 'update']);
        Route::delete('/{fundi}', [FundiController::class, 'destroy']);

        // Approval/Rejection routes
        Route::patch('/{fundi}/approve', [FundiController::class, 'approve']);
        Route::patch('/{fundi}/reject', [FundiController::class, 'reject']);

        // Optional: Restore soft deleted fundi (if needed)
        Route::patch('/{fundi}/restore', [FundiController::class, 'restore']);
    });

    // Site Visits
    Route::prefix('site-visits')->group(function () {
        Route::get('/', [SiteVisitController::class, 'index']);
        Route::post('/', [SiteVisitController::class, 'store']);
        Route::get('/{siteVisit}', [SiteVisitController::class, 'show']);
        Route::put('/{siteVisit}', [SiteVisitController::class, 'update']);
        Route::delete('/{siteVisit}', [SiteVisitController::class, 'destroy']);
    });

    // Master Data (existing)
    Route::prefix('master-data')->group(function () {
        Route::get('/wards', [MasterDataController::class, 'wards']);
        Route::get('/products', [MasterDataController::class, 'products']);

        // New location endpoints
        Route::get('/regions', [AddressController::class, 'getRegions'])
            ->name('api.master-data.regions');
        Route::get('/regions/{region_id}/districts', [AddressController::class, 'getDistricts'])
            ->name('api.master-data.districts')
            ->where('region_id', '[0-9]+');
        Route::get('/districts/{district_id}/wards', [AddressController::class, 'getWards'])
            ->name('api.master-data.wards')
            ->where('district_id', '[0-9]+');
        Route::get('/locations', [AddressController::class, 'getLocations'])
            ->name('api.master-data.locations');
        Route::get('/locations/search', [AddressController::class, 'searchLocations'])
            ->name('api.master-data.locations.search');
    });

    // Operator-specific location endpoints
    Route::prefix('operator')->name('api.operator.')->group(function () {
        Route::get('/districts', [AddressController::class, 'getOperatorAssignedDistricts'])
            ->name('districts');
        Route::get('/wards', [AddressController::class, 'getOperatorAssignedWards'])
            ->name('wards');
    });

    // Transaction History
    Route::prefix('transactions')->group(function () {
        Route::get('/', [TransactionController::class, 'index']);
        Route::get('/{transaction}', [TransactionController::class, 'show']);
    });

    // Dashboard/Stats
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Notifications (commented out as in original)
    // Route::prefix('notifications')->group(function () {
    //     Route::get('/', [NotificationController::class, 'index']);
    //     Route::put('/{notification}/read', [NotificationController::class, 'markAsRead']);
    //     Route::put('/mark-all-read', [NotificationController::class, 'markAllAsRead']);
    // });
});

// Public Selcom Callback Routes (no authentication)
Route::prefix('selcom')->group(function () {
    Route::post('/payment-callback', [SelcomCallbackController::class, 'handlePaymentCallback']);
    Route::post('/name-lookup-callback', [SelcomCallbackController::class, 'handleNameLookupCallback']);
});

// Health check route
Route::get('health', function () {
    return response()->json([
        'success' => true,
        'message' => 'Fundi Management API is running smoothly',
        'status' => 200,
    ]);
});

/* ================ FALLBACK ROUTE ================ */
// Catch-all route for undefined routes
Route::fallback(function () {
    return response()->json([
        'success' => false,
        'message' => 'Unauthorized access, please contact support',
        'errors' => [
            'Unauthorized' => 'You do not have permission to access this resource.',
        ],
    ], 401);
});

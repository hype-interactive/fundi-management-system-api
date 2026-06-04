<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FundiController;
use App\Http\Controllers\IdCardController;
use App\Http\Controllers\LegacyDataController;
use App\Http\Controllers\OperatorController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SiteVisitController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/dashboard', function () {
        return view('pages.dashboard');
    });

    Route::get('/fundis', function () {
        return view('pages.fundis');
    })->name('fundis');

    Route::get('/fundis/{fundi}', [FundiController::class, 'show'])->name('fundi');

    Route::get('/unapproved_fundis', function () {
        return view('pages.unapproved-fundis');
    })->name('unapproved-fundis');

    // routes/web.php

    // Add this route in your fundis route group
    Route::get('/duplicate_fundis', function () {
        return view('pages.fundi-duplicates');
    })->name('fundis.duplicates');

    Route::get('/site-visits', function () {
        return view('pages.site-visits');
    })->name('site-visits');

    Route::get('/site-visit/{siteVisit}', [SiteVisitController::class, 'show'])->name('site-visit');

    Route::get('/financials', function () {
        return view('pages.under-construction');
    })->name('financials');

    Route::get('/messaging', function () {
        return view('pages.messaging');
    })->name('messaging');

    // Routes under management
    Route::get('/administrators', function () {
        return view('pages.admins');
    })->name('administrators');

    Route::get('/operators', function () {
        return view('pages.operators');
    })->name('operators');

    Route::get('/locations', function () {
        return view('pages.location-manager');
    })->name('locations.manage');

    Route::get('/operators/{operator}', [OperatorController::class, 'show'])->name('operator');

    Route::get('/financials', function () {
        return view('pages.financials');
    })->name('financials');

    // Financial sub-routes
    Route::get('/financials/budgets', function () {
        return view('pages.financial.budgets');
    })->name('financial.budgets');

    Route::get('/financials/disbursements', function () {
        return view('pages.financial.disbursements');
    })->name('financial.disbursements');

    Route::get('/financials/transactions', function () {
        return view('pages.financial.transactions');
    })->name('financial.transactions');

    Route::get('/financials/reports', function () {
        return view('pages.financial.reports');
    })->name('financial.reports');

    Route::get('/seminars', function () {
        return view('pages.seminars');
    })->name('seminars');

    /* Legacy fundis management routes
    * These routes handle the import, export, and management of legacy fundis data.
    * They include features like bulk operations, manual linking/unlinking, and progress tracking.
    */

    Route::prefix('legacy-data')->name('legacy-fundis.')->group(function () {
        // Main legacy fundis page
        Route::get('/', [LegacyDataController::class, 'index'])
            ->name('index');

        // Import legacy data (now with queue support)
        Route::post('/import', [LegacyDataController::class, 'import'])
            ->name('import');

        // Check import progress
        Route::get('/import-progress/{job_id}', [LegacyDataController::class, 'importProgress'])
            ->name('import-progress');

        // Cancel import
        Route::post('/cancel-import', [LegacyDataController::class, 'cancelImport'])
            ->name('cancel-import');

        // Get statistics
        Route::get('/statistics', [LegacyDataController::class, 'statistics'])
            ->name('statistics');

        // Manual link/unlink operations
        Route::post('/link', [LegacyDataController::class, 'link'])
            ->name('link');

        Route::post('/unlink', [LegacyDataController::class, 'unlink'])
            ->name('unlink');

        // Get potential matches for a legacy fundi
        Route::get('/potential-matches/{id}', [LegacyDataController::class, 'potentialMatches'])
            ->name('potential-matches');

        // Bulk operations
        Route::post('/bulk-auto-match', [LegacyDataController::class, 'bulkAutoMatch'])
            ->name('bulk-auto-match');

        // Export functionality
        Route::get('/export-analysis', [LegacyDataController::class, 'exportAnalysis'])
            ->name('export-analysis');
    });

    Route::get('/products', function () {
        return view('pages.products');
    })->name('products');

    Route::get('/logs', function () {
        return view('pages.activity-logs');
    })->name('logs');

    // Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    // Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    // Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Public fundi registration (no auth required)
Route::get('/register-fundi', App\Livewire\PublicFundiRegistration::class)
    ->name('fundi.public.register');

// app download
Route::get('/app', function () {
    return view('pages.app-download');
})->name('');

// ID - Generator

Route::get('/id-card', [IdCardController::class, 'generate'])->name('fundi_id_download');

// file download
// Route::get('/download-apk', function () {
//     return response()->download(public_path('apk/app-release.apk'), 'fundi-app.apk');
// })->name('download-apk');

Route::get('/download-apk', [App\Http\Controllers\APKController::class, 'download'])->name('download-apk');

require __DIR__.'/auth.php';

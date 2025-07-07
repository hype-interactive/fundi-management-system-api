<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FundiController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\OperatorController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SiteVisitController;
use App\Http\Controllers\LegacyDataController;

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
        return  view('pages.unapproved-fundis');
    })->name('unapproved-fundis');

    Route::get('/site-visits', function () {
        return view('pages.site-visits');
    })->name('site-visits');

    Route::get('/site-visit/{siteVisit}', [SiteVisitController::class, 'show'])->name('site-visit');

    Route::get('/financials', function () {
        return view('pages.under-construction');
    })->name('financials');

    Route::get('/messaging', function () {
        return view('pages.under-construction');
    })->name('messaging');

    // Routes under management
    Route::get('/administrators', function () {
        return view('pages.admins');
    })->name('administrators');

    Route::get('/operators', function () {
        return view('pages.operators');
    })->name('operators');

    Route::get('/operators/{operator}', [OperatorController::class, 'show'])->name('operator');

    // Route::get('/legacy-data', function () {
    //     return view('pages.legacy-fundis');
    // })->name('legacy-data');

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

    Route::get('/logs', function () {
        return view('pages.activity-logs');
    })->name('logs');

    // Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    // Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    // Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';

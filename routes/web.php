<?php

use App\Livewire\Products;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FundiController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\OperatorController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SiteVisitController;

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
        return view('pages.messaging');
    })->name('messaging');

    // Routes under management
    Route::get('/administrators', function () {
        return view('pages.admins');
    })->name('administrators');

    Route::get('/operators', function () {
        return view('pages.operators');
    })->name('operators');

    Route::get('/operators/{operator}', [OperatorController::class, 'show'])->name('operator');

    Route::get('/legacy-data', function () {
        return view('pages.under-construction');
    })->name('legacy-data');

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

//app download
Route::get('/app', function () {
    return view('pages.app-download');
})->name('');

//file download
Route::get('/download-apk', function () {
    return response()->download(public_path('apk/app-release.apk'), 'fundi-app.apk');
})->name('download-apk');

require __DIR__.'/auth.php';

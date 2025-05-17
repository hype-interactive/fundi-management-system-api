<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('/', function () {
        return view('pages.dashboard');
    })->name('dashboard');

    Route::get('/dashboard', function () {
        return view('pages.dashboard');
    });

    Route::get('/fundis', function () {
        return view('pages.under-construction');
    })->name('fundis');

    Route::get('/site-visits', function () {
        return view('pages.under-construction');
    })->name('site-visits');

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
        return view('pages.under-construction');
    })->name('operators');

    Route::get('/legacy-data', function () {
        return view('pages.under-construction');
    })->name('legacy-data');

    Route::get('/logs', function () {
        return view('pages.under-construction');
    })->name('logs');

    // Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    // Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    // Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

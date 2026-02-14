<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PostureChunkController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\AdminController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| Public Routes (Guest Access)
|--------------------------------------------------------------------------
*/

// 1. The Entrance
Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
});

// 2. Social Login (Google)
Route::get('auth/google', [SocialAuthController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('auth/google/callback', [SocialAuthController::class, 'handleGoogleCallback']);

// 3. Temporary Maintenance
Route::get('/force-migrate', function () {
    \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
    return 'Database Migration Completed Successfully!';
});

/*
|--------------------------------------------------------------------------
| Protected Routes (Must be Logged In & Verified)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])->group(function () {

    // --- MAIN DASHBOARD ---
    Route::get('/dashboard', [PostureChunkController::class, 'index'])->name('dashboard');

    // --- THE CAMERA PAGE ---
    Route::get('/camera', function () {
        return Inertia::render('PostureCamera');
    })->name('camera');

    // --- POSTURE DATA API ---
    Route::post('/posture-chunks', [PostureChunkController::class, 'store'])->name('posture-chunks.store');
    Route::delete('/posture-chunks/{id}', [PostureChunkController::class, 'destroy'])->name('posture-chunks.destroy');

    // --- USER PROFILE ---
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    /*
    |----------------------------------------------------------------------
    | Admin Routes
    |----------------------------------------------------------------------
    */
        Route::prefix('admin')
        ->middleware('can:access-admin') // [ADD THIS LINE]
        ->group(function () {
            Route::get('/', [AdminController::class, 'index'])->name('admin.dashboard');
            Route::get('/users/{user}', [AdminController::class, 'show'])->name('admin.users.show');
        });
});

require __DIR__.'/auth.php';
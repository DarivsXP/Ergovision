<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PostureChunkController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\AdminController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\DashboardController;

/*
|--------------------------------------------------------------------------
| Public Routes (Guest Access)
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
});

Route::get('auth/google', [SocialAuthController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('auth/google/callback', [SocialAuthController::class, 'handleGoogleCallback']);

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
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // --- DELETE SESSION ---
    Route::delete('/posture-chunks/{id}', [PostureChunkController::class, 'destroySession'])
    ->name('posture-chunks.destroy');

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

    // --- onboarding ---
    Route::get('/onboarding', [OnboardingController::class, 'index'])->name('onboarding.index');
    Route::post('/onboarding', [OnboardingController::class, 'store'])->name('onboarding.store');

    /*
    |----------------------------------------------------------------------
    | Admin Routes
    |----------------------------------------------------------------------
    */
    Route::prefix('admin')
        ->name('admin.') // This ensures all child routes start with 'admin.'
        ->middleware('can:access-admin')
        ->group(function () {
            // dashboard -> mapped to admin.dashboard
            Route::get('/', [AdminController::class, 'index'])->name('dashboard');
            
            // [FIXED] Added the missing users index route
            Route::get('/users', [AdminController::class, 'users'])->name('users.index');
            
            // users/{user} -> mapped to admin.users.show
            Route::get('/users/{user}', [AdminController::class, 'show'])->name('users.show');

            Route::get('/users/{user}/edit', [AdminController::class, 'edit'])->name('users.edit');
            Route::patch('/users/{user}', [AdminController::class, 'update'])->name('users.update');
            Route::delete('/users/{user}', [AdminController::class, 'destroy'])->name('users.destroy');
        });
});

require __DIR__.'/auth.php';
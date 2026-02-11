<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PostureChunkController;
use App\Http\Controllers\Auth\SocialAuthController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| Public Routes (Guest Access)
|--------------------------------------------------------------------------
*/

// 1. The Entrance
// If logged in -> Go to Dashboard
// If guest -> Go to Login
Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
});

// 2. Social Login (Google)
Route::get('auth/google', [SocialAuthController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('auth/google/callback', [SocialAuthController::class, 'handleGoogleCallback']);

// 3. Temporary Maintenance (Delete this after deployment works!)
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
    // This loads your Posture Data and shows the main UI
    Route::get('/dashboard', [PostureChunkController::class, 'index'])->name('dashboard');

    // --- THE CAMERA PAGE ---
    // The actual AI detection interface
    Route::get('/camera', function () {
        return Inertia::render('PostureCamera');
    })->name('camera');

    // --- POSTURE DATA API ---
    // Saving and Deleting chunks of posture data
    Route::post('/posture-chunks', [PostureChunkController::class, 'store'])->name('posture-chunks.store');
    Route::delete('/posture-chunks/{id}', [PostureChunkController::class, 'destroy'])->name('posture-chunks.destroy');

    // --- LEGACY / OTHER ---
    // Keep this if you still have a "PostureApp.vue" file, otherwise delete it.
    Route::get('/posture-app', function () {
        return Inertia::render('PostureApp');
    })->name('posture-app');

    // --- USER PROFILE ---
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
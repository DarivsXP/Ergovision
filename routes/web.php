<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PostureChunkController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\DashboardController;
use App\Http\Middleware\CheckOnboarding;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\StressTestController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

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

// --- LEGAL PAGES ---
Route::get('/privacy-policy', function () {
    return Inertia::render('PrivacyPolicy');
})->name('privacy');

Route::get('/terms-of-service', function () {
    return Inertia::render('TermsOfService');
})->name('terms');

/*
|--------------------------------------------------------------------------
| Semi-Protected Routes (Must be Logged In, but before Onboarding)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/onboarding', [OnboardingController::class, 'create'])->name('onboarding.create');
    Route::post('/onboarding', [OnboardingController::class, 'store'])->name('onboarding.store');
});

/*
|--------------------------------------------------------------------------
| Fully Protected Routes (Logged In + Verified + Onboarded)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified', CheckOnboarding::class])->group(function () {

    // --- MAIN DASHBOARD ---
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // --- THE CAMERA PAGE ---
    Route::get('/camera', function () {
        return Inertia::render('PostureCamera');
    })->name('camera');

    // --- POSTURE DATA API ---
    Route::post('/posture-chunks', [PostureChunkController::class, 'store'])->name('posture-chunks.store');
    Route::delete('/posture-chunks/{id}/session', [PostureChunkController::class, 'destroySession'])->name('posture-chunks.destroy-session');
    Route::delete('/posture-chunks/{id}', [PostureChunkController::class, 'destroy'])->name('posture-chunks.destroy');

    // --- USER PROFILE ---
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // --- FEEDBACK & ONBOARDING ---
    Route::post('/feedback', [FeedbackController::class, 'store'])->name('feedback.store');
    Route::get('/feedback/history', [FeedbackController::class, 'index'])->name('feedback.history');

    // [FIXED]: forceFill completely bypasses the Mass Assignment 500 Error!
    Route::post('/tour/complete', function (\Illuminate\Http\Request $request) {
        $request->user()->forceFill(['has_seen_tour' => true])->save();
        return response()->json(['status' => 'success']);
    })->name('tour.complete');

    /*
    |----------------------------------------------------------------------
    | Admin Routes
    |----------------------------------------------------------------------
    */
    Route::prefix('admin')
        ->name('admin.')
        ->middleware('can:access-admin')
        ->group(function () {
            Route::get('/', [AdminController::class, 'index'])->name('dashboard');
            Route::get('/users', [AdminController::class, 'users'])->name('users.index');
            Route::get('/users/{user}', [AdminController::class, 'show'])->name('users.show');
            Route::get('/users/{user}/edit', [AdminController::class, 'edit'])->name('users.edit');
            Route::patch('/users/{user}', [AdminController::class, 'update'])->name('users.update');
            Route::delete('/users/{user}', [AdminController::class, 'destroy'])->name('users.destroy');
            Route::get('/export/telemetry', [AdminController::class, 'exportTelemetry'])->name('export.telemetry');
            Route::get('/export/feedback', [AdminController::class, 'exportFeedback'])->name('export.feedback');
            Route::get('/stress-test', [StressTestController::class, 'index'])
                ->middleware('throttle:stress-test')
                ->name('stress-test');
            Route::post('/stress-test/telemetry', [StressTestController::class, 'runTelemetryBatch'])
                ->middleware('throttle:stress-test')
                ->name('stress-test.telemetry');
            Route::post('/stress-test/site-visits', [StressTestController::class, 'runSiteVisits'])
                ->middleware('throttle:stress-test')
                ->name('stress-test.site-visits');
        });
});

require __DIR__.'/auth.php';
<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PostureChunkController;
use Inertia\Inertia;
use App\Http\Controllers\Auth\SocialAuthController;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

// Route::get('/dashboard', function () {
//     return Inertia::render('Dashboard');
// })->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/dashboard', [PostureChunkController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::post('/posture-chunks', [PostureChunkController::class, 'store'])
    ->middleware(['auth', 'verified'])
    ->name('posture-chunks.store');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/posture-app', function () {
    return Inertia::render('PostureApp');
})->middleware(['auth', 'verified']);

Route::get('/camera', function () {
    return Inertia::render('PostureCamera');
})->middleware(['auth', 'verified'])->name('camera');

Route::get('auth/google', [SocialAuthController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('auth/google/callback', [SocialAuthController::class, 'handleGoogleCallback']);

Route::delete('/posture-chunks/{id}', [PostureChunkController::class, 'destroy'])
    ->middleware(['auth', 'verified'])
    ->name('posture-chunks.destroy');
    
    

// Route::post('/posture-chunks', [PostureChunkController::class, 'store'])->name('chunks.store');
Route::get('/force-migrate', function () {
    \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
    return 'Database Migration Completed Successfully!';
});

require __DIR__.'/auth.php';

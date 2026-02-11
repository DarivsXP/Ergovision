<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    // 1. Redirect user to Google
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    // 2. Handle the callback from Google
    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();

            // Find existing user OR create a new one
            $user = User::updateOrCreate([
                'email' => $googleUser->getEmail(),
            ], [
                'name' => $googleUser->getName(),
                'google_id' => $googleUser->getId(),
                // FIX: Generate a random password so the database doesn't crash.
                // We only set this if the user doesn't have one (creating new).
                // If updating, Laravel is smart enough to ignore this if we don't pass it, 
                // but updateOrCreate merges arrays. 
                // A safer way is to use a default random password for new users.
                'password' => Hash::make(Str::random(24)), 
                'email_verified_at' => now(), // Auto-verify Google users
            ]);

            // Log the user in
            // 'true' = Remember Me (Helps keep the session alive)
            Auth::login($user, true);

            // Redirect to dashboard
            return redirect()->route('dashboard');

        } catch (\Exception $e) {
            // DEBUGGING: If it fails, let's see why. 
            // Once fixed, you can remove the 'dd' and use the redirect line below.
            // dd($e->getMessage()); 
            
            return redirect()->route('login')->with('error', 'Google Login Failed: ' . $e->getMessage());
        }
    }
}
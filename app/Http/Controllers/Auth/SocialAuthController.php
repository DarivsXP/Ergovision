<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
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

            // Find user or create a new one
            $user = User::updateOrCreate([
                'email' => $googleUser->getEmail(),
            ], [
                'name' => $googleUser->getName(),
                'google_id' => $googleUser->getId(),
                'password' => null, // No password for social login
                // 'avatar' => $googleUser->getAvatar(), // If you added avatar column
            ]);

            // Log the user in
            Auth::login($user);

            // Redirect to dashboard
            return redirect()->route('dashboard');

        } catch (\Exception $e) {
            return redirect('/login')->with('error', 'Google Login Failed');
        }
    }
}
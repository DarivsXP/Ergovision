<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class OnboardingController extends Controller
{
    public function index() {
        return Inertia::render('Onboarding/RoleSelection');
    }

    public function store(Request $request) {
        $validated = $request->validate([
            'user_type' => 'required|in:student,worker,other'
        ]);

        $request->user()->update($validated);

        return redirect()->route('dashboard')->with('message', 'Profile updated!');
    }
}

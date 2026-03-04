<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class OnboardingController extends Controller
{
    public function create()
    {
        $user = auth()->user();

        // If they already have all their data, send them straight to the dashboard
        if ($user->is_onboarded && $user->age && $user->occupation) {
            return redirect()->route('dashboard');
        }

        // Updated path to match your new folder structure
        return Inertia::render('Onboarding/OnBoarding');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'occupation' => 'required|string|max:255',
            'age' => 'required|integer|min:13|max:100',
            'daily_sitting_hours' => 'required|integer|min:0|max:13',
            'has_musculoskeletal_issues' => 'required|boolean',
            'musculoskeletal_details' => 'nullable|string|max:1000',
        ]);

        $user = auth()->user();
        
        $user->update([
            'occupation' => $validated['occupation'],
            'age' => $validated['age'],
            'daily_sitting_hours' => $validated['daily_sitting_hours'],
            'has_musculoskeletal_issues' => $validated['has_musculoskeletal_issues'],
            'musculoskeletal_details' => $validated['has_musculoskeletal_issues'] ? $validated['musculoskeletal_details'] : null,
            'is_onboarded' => true,
        ]);

        return redirect()->route('dashboard');
    }
}
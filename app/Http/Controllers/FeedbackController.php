<?php

namespace App\Http\Controllers;

use App\Models\SessionFeedback;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'fatigue_level' => 'required|integer|min:1|max:5',
            'accuracy_rating' => 'required|integer|min:1|max:5',
            'comments' => 'nullable|string|max:1000',
        ]);

        // Save the feedback attached to the currently logged-in user
        SessionFeedback::create([
            'user_id' => auth()->id(),
            'fatigue_level' => $validated['fatigue_level'],
            'accuracy_rating' => $validated['accuracy_rating'],
            'comments' => $validated['comments'],
        ]);

        // Redirect back to the dashboard with a success flash message
        return redirect()->route('dashboard')->with('message', 'Telemetry securely logged. Thank you!');
    }
}
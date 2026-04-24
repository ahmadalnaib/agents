<?php

namespace App\Http\Controllers;

use App\Ai\Agents\SalesCoach;
use Illuminate\Http\Request;

class AgentController extends Controller
{
    //
    public function handle(Request $request)
    {
        $validated = $request->validate([
            'message' => 'required|string',
        ]);

        $response = SalesCoach::make(auth()->user())->forUser(auth()->user())
                    ->prompt($validated['message']);

        return response()->json([
            'message' => (string) $response,
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiRecommendationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $recommendations = $request->user()->aiRecommendations()
            ->latest()
            ->limit(20)
            ->get(['id', 'summary', 'ingredients', 'meal_ideas', 'context', 'created_at']);

        return response()->json(['recommendations' => $recommendations]);
    }
}

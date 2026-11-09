<?php

namespace App\Http\Controllers;

use App\Support\CookingAssistant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CookingChatController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, CookingAssistant $assistant): JsonResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
            'history' => ['sometimes', 'array', 'max:10'],
            'history.*' => ['array:role,content'],
            'history.*.role' => ['required', 'in:user,assistant'],
            'history.*.content' => ['required', 'string', 'max:4000'],
        ]);

        if (! CookingAssistant::configured()) {
            return response()->json(['message' => 'The cooking assistant is not connected yet. Please explore the recipe collection while AI setup is completed.'], 503);
        }

        $reply = $assistant->reply($data['message'], $data['history'] ?? []);

        if ($reply === null) {
            return response()->json(['message' => 'The cooking assistant could not respond right now. Please try again shortly.'], 503);
        }

        return response()->json(['reply' => $reply]);
    }
}

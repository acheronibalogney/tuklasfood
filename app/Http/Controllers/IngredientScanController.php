<?php

namespace App\Http\Controllers;

use App\Support\IngredientScanner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class IngredientScanController extends Controller
{
    public function __invoke(Request $request, IngredientScanner $scanner): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'images' => ['required', 'array', 'min:1', 'max:4'],
            'images.*' => ['required', 'array:mime_type,data'],
            'images.*.mime_type' => ['required', 'in:image/jpeg,image/png,image/webp'],
            'images.*.data' => ['required', 'string', 'max:1250000'],
            'context' => ['nullable', 'string', 'max:500'],
        ]);

        $validator->after(function ($validator) use ($request): void {
            $totalBytes = 0;

            foreach ($request->input('images', []) as $index => $image) {
                if (! is_array($image) || ! isset($image['data'], $image['mime_type'])) {
                    continue;
                }

                $bytes = base64_decode($image['data'], true);
                $imageInfo = $bytes === false ? false : @getimagesizefromstring($bytes);

                if ($bytes === false || $imageInfo === false || ($imageInfo['mime'] ?? null) !== $image['mime_type']) {
                    $validator->errors()->add("images.$index.data", 'Each image must be a valid JPEG, PNG, or WebP photo.');

                    continue;
                }

                $totalBytes += strlen($bytes);

                if ($imageInfo[0] > 4096 || $imageInfo[1] > 4096) {
                    $validator->errors()->add("images.$index.data", 'Each photo must be 4096 pixels or smaller in each dimension.');
                }
            }

            if ($totalBytes > 3_000_000) {
                $validator->errors()->add('images', 'Please choose smaller photos; the combined upload must be under 3 MB.');
            }
        });

        $data = $validator->validate();

        if (! IngredientScanner::configured()) {
            return response()->json([
                'message' => 'The ingredient scanner is not configured. Set AI_API_KEY in the Laravel .env file and restart the server.',
            ], 503);
        }

        $result = $scanner->scan($data['images'], $data['context'] ?? null);

        if ($result === null) {
            return response()->json(['message' => $scanner->failureMessage()], 503);
        }

        $recommendation = $request->user()->aiRecommendations()->create([
            'summary' => $result['summary'],
            'ingredients' => $result['ingredients'],
            'meal_ideas' => $result['meal_ideas'],
            'context' => $data['context'] ?? null,
        ]);

        return response()->json($result + ['saved_recommendation_id' => $recommendation->id]);
    }
}

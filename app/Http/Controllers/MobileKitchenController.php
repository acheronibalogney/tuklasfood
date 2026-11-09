<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use App\Models\Ingredient;
use App\Models\Recipe;
use App\Support\UnitConverter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class MobileKitchenController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'user' => $user->only(['id', 'name', 'email', 'role']),
            'recipes' => Recipe::where('is_published', true)->with(['ingredients.ingredient', 'steps', 'equipment'])->orderBy('title')->get()->append('total_minutes'),
            'ingredients' => Ingredient::orderBy('name')->get(['id', 'name', 'base_unit', 'piece_weight_g']),
            'pantry' => $user->inventory()->with('ingredient')->orderBy('expiration_date')->get(),
            'equipment' => Equipment::orderBy('name')->get(['id', 'name']),
            'owned_equipment' => $user->equipment()->pluck('equipment.id'),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $item = $request->user()->inventory()->create($this->inventoryData($request));

        return response()->json(['item' => $item->load('ingredient')], 201);
    }

    public function update(Request $request, int $item): JsonResponse
    {
        $inventory = $request->user()->inventory()->findOrFail($item);
        $inventory->update($this->inventoryData($request));

        return response()->json(['item' => $inventory->load('ingredient')]);
    }

    public function destroy(Request $request, int $item): JsonResponse
    {
        $request->user()->inventory()->findOrFail($item)->delete();

        return response()->json(['message' => 'Pantry item removed.']);
    }

    public function equipment(Request $request): JsonResponse
    {
        $data = $request->validate([
            'equipment' => ['present', 'array', 'max:100'],
            'equipment.*' => ['integer', 'distinct', 'exists:equipment,id'],
        ]);
        $request->user()->equipment()->sync($data['equipment']);

        return response()->json(['message' => 'Kitchen tools saved.']);
    }

    /** @return array<string, mixed> */
    private function inventoryData(Request $request): array
    {
        $data = $request->validate([
            'ingredient_id' => ['required', 'integer', 'exists:ingredients,id'],
            'quantity' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'unit' => ['required', Rule::in(UnitConverter::units())],
            'purchase_date' => ['nullable', 'date_format:Y-m-d'],
            'expiration_date' => ['nullable', 'date_format:Y-m-d', Rule::when($request->filled('purchase_date'), 'after_or_equal:purchase_date')],
            'storage_location' => ['nullable', 'string', 'max:255'],
        ]);
        try {
            $data['quantity_base'] = UnitConverter::toBase(Ingredient::findOrFail($data['ingredient_id']), (float) $data['quantity'], $data['unit']);
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['unit' => [$exception->getMessage()]]);
        }
        if ($data['quantity_base'] > 999999999.999) {
            throw ValidationException::withMessages(['quantity' => ['This quantity is too large.']]);
        }

        return $data;
    }
}

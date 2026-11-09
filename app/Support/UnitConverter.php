<?php

namespace App\Support;

use App\Models\Ingredient;
use InvalidArgumentException;

class UnitConverter
{
    private const FACTORS = [
        'g' => ['g', 1], 'kg' => ['g', 1000],
        'ml' => ['ml', 1], 'l' => ['ml', 1000],
        'tsp' => ['ml', 5], 'tbsp' => ['ml', 15], 'cup' => ['ml', 240],
        'pc' => ['pc', 1],
    ];

    public static function units(): array
    {
        return array_keys(self::FACTORS);
    }

    public static function toBase(Ingredient $ingredient, float $quantity, string $unit): float
    {
        if (! isset(self::FACTORS[$unit])) {
            throw new InvalidArgumentException("Unknown unit: {$unit}");
        }

        [$family, $factor] = self::FACTORS[$unit];
        $amount = $quantity * $factor;
        $base = $ingredient->base_unit;

        if ($family === $base) {
            return $amount;
        }

        $weight = (float) $ingredient->piece_weight_g;

        if ($weight > 0 && $family === 'pc' && $base === 'g') {
            return $amount * $weight;
        }

        if ($weight > 0 && $family === 'g' && $base === 'pc') {
            return $amount / $weight;
        }

        throw new InvalidArgumentException(
            "Cannot convert {$unit} to {$base} for {$ingredient->name}."
        );
    }
}
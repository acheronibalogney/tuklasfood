<?php

namespace Tests\Unit;

use App\Models\Ingredient;
use App\Support\UnitConverter;
use InvalidArgumentException;
use Tests\TestCase;

class UnitConverterTest extends TestCase
{
    private function ingredient(string $base, ?float $pieceWeight = null): Ingredient
    {
        return new Ingredient(['name' => 'Test', 'base_unit' => $base, 'piece_weight_g' => $pieceWeight]);
    }

    public function test_kg_to_g(): void
    {
        $this->assertSame(1500.0, UnitConverter::toBase($this->ingredient('g'), 1.5, 'kg'));
    }

    public function test_tbsp_to_ml(): void
    {
        $this->assertSame(45.0, UnitConverter::toBase($this->ingredient('ml'), 3, 'tbsp'));
    }

    public function test_pieces_to_grams_uses_piece_weight(): void
    {
        $this->assertSame(600.0, UnitConverter::toBase($this->ingredient('g', 200), 3, 'pc'));
    }

    public function test_grams_to_pieces(): void
    {
        $this->assertEqualsWithDelta(2.0, UnitConverter::toBase($this->ingredient('pc', 5), 10, 'g'), 0.001);
    }

    public function test_incompatible_units_throw(): void
    {
        $this->expectException(InvalidArgumentException::class);
        UnitConverter::toBase($this->ingredient('g'), 1, 'cup');
    }
}

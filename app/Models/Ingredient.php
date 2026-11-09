<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ingredient extends Model
{
    protected $fillable = ['ingredient_category_id', 'parent_id', 'name', 'base_unit', 'piece_weight_g'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(IngredientCategory::class, 'ingredient_category_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class, 'parent_id');
    }

    public function cuts(): HasMany
    {
        return $this->hasMany(Ingredient::class, 'parent_id');
    }
}

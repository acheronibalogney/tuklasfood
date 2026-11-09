<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Recipe extends Model
{
    protected $fillable = ['title', 'slug', 'description', 'cuisine', 'servings', 'prep_minutes', 'cook_minutes', 'difficulty', 'is_published'];

    protected $casts = ['is_published' => 'boolean'];

    public function steps(): HasMany
    {
        return $this->hasMany(RecipeStep::class)->orderBy('position');
    }

    public function ingredients(): HasMany
    {
        return $this->hasMany(RecipeIngredient::class);
    }

    public function equipment(): BelongsToMany
    {
        return $this->belongsToMany(Equipment::class, 'recipe_equipment')->withPivot('group_no');
    }

    public function getTotalMinutesAttribute(): int
    {
        return $this->prep_minutes + $this->cook_minutes;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserIngredient extends Model
{
    protected $fillable = ['user_id', 'ingredient_id', 'quantity', 'unit', 'quantity_base', 'purchase_date', 'expiration_date', 'storage_location'];

    protected $casts = ['purchase_date' => 'date', 'expiration_date' => 'date'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }
}

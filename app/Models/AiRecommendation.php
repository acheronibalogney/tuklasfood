<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiRecommendation extends Model
{
    protected $fillable = ['user_id', 'summary', 'ingredients', 'meal_ideas', 'context'];

    protected function casts(): array
    {
        return [
            'ingredients' => 'array',
            'meal_ideas' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @param Builder<AiRecommendation> $query */
    public function scopeLatestForUser(Builder $query, User $user): Builder
    {
        return $query->whereBelongsTo($user)->latest()->limit(20);
    }
}

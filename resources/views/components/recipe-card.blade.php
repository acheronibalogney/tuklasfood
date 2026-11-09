@props(['recipe'])
@php($kind = str_contains($recipe->slug, 'itlog') || str_contains($recipe->slug, 'talong') ? 'egg' : (str_contains($recipe->slug, 'rice') || str_contains($recipe->slug, 'caldo') ? 'rice' : 'chicken'))
<article class="recipe-card" data-recipe data-title="{{ strtolower($recipe->title) }}" data-difficulty="{{ $recipe->difficulty }}" data-minutes="{{ $recipe->total_minutes }}">
    <button class="recipe-art art-{{ $kind }}" type="button" data-open="recipe-{{ $recipe->id }}" aria-label="View {{ $recipe->title }} recipe"><span class="recipe-tag">{{ ucfirst($recipe->difficulty) }} to make</span><x-food-art :kind="$kind" :art-id="'card-'.$recipe->id" /><span class="recipe-arrow" aria-hidden="true">↗</span></button>
    <div class="recipe-copy"><span class="eyebrow">{{ $recipe->cuisine }} comfort food</span><h3><button type="button" data-open="recipe-{{ $recipe->id }}">{{ $recipe->title }}</button></h3><p>{{ $recipe->description }}</p><div class="recipe-meta"><span>◷ {{ $recipe->total_minutes }} minutes</span><span>{{ $recipe->servings }} servings</span></div></div>
</article>

@props(['recipe'])
<dialog id="recipe-{{ $recipe->id }}" class="recipe-dialog" aria-labelledby="recipe-title-{{ $recipe->id }}">
    <div class="dialog-top"><span class="eyebrow">From the TuklasFood kitchen</span><form method="dialog"><button class="dialog-close" aria-label="Close recipe">×</button></form></div>
    <h2 id="recipe-title-{{ $recipe->id }}">{{ $recipe->title }}</h2><p>{{ $recipe->description }}</p>
    <div class="detail-meta"><span>{{ $recipe->prep_minutes }} min prep</span><span>{{ $recipe->cook_minutes }} min cooking</span><span>Serves {{ $recipe->servings }}</span></div>
    <div class="recipe-detail-grid"><section><h3>What you’ll need</h3><ul class="ingredient-list">@foreach($recipe->ingredients as $item)<li><span>{{ $item->ingredient->name }} @if($item->is_optional)<small>(optional)</small>@endif</span><b>{{ (float) $item->quantity }} {{ $item->unit }}</b></li>@endforeach</ul><h3>Your equipment</h3><ul class="equipment-list">@foreach($recipe->equipment->groupBy('pivot.group_no') as $group)<li>{{ $group->pluck('name')->join(' or ') }}</li>@endforeach</ul></section><section><h3>Let’s get cooking</h3><ol class="recipe-steps">@foreach($recipe->steps as $step)<li>{{ $step->instruction }}</li>@endforeach</ol></section></div>
</dialog>

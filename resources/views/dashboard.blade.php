<x-food-page title="My kitchen — TuklasFood">
    <div class="container kitchen-layout">
        <aside class="kitchen-sidebar"><span class="eyebrow">YOUR SPACE</span><a class="sidebar-active" href="#collection">↗ Recipe collection</a><a href="{{ route('profile.show') }}">○ Account settings</a><a href="{{ route('home') }}">← Back to home</a><div class="sidebar-note"><span>✳</span><h3>A little inspiration<br>goes a long way.</h3><p>Pick a recipe. Put on your favorite playlist. Let’s make something good.</p></div></aside>
        <section class="kitchen-main" id="collection"><div class="section-heading"><div><span class="eyebrow">WELCOME TO YOUR KITCHEN</span><h1>Hey, {{ auth()->user()->name }}.<br><span>What’s on the menu?</span></h1></div></div>
            <div class="kitchen-stats"><div><b>{{ $recipes->count() }}</b><span>Recipes to explore</span></div><div><b>{{ $pantryCount }}</b><span>Pantry ingredients</span></div><div><b>{{ $equipmentCount }}</b><span>Kitchen tools</span></div></div>
            <div class="section-heading compact"><h2>Your recipe collection</h2></div>
            <div class="recipe-tools"><div class="recipe-filters" role="group" aria-label="Filter recipes"><button class="active" data-filter="all" aria-pressed="true">The whole menu</button><button data-filter="quick" aria-pressed="false">30 min or less</button><button data-filter="easy" aria-pressed="false">Easy favorites</button></div><label class="recipe-search"><span aria-hidden="true">⌕</span><input type="search" data-recipe-search placeholder="Find your craving…" aria-label="Search recipes"></label></div>
            <div class="recipe-grid">@foreach($recipes as $recipe)<x-recipe-card :recipe="$recipe" />@endforeach</div><p class="empty-recipes" data-empty-recipes @if($recipes->isNotEmpty()) hidden @endif>No recipes found. Try another search or choose “The whole menu”.</p>
        </section>
    </div>
    @foreach($recipes as $recipe)<x-recipe-dialog :recipe="$recipe" />@endforeach
</x-food-page>

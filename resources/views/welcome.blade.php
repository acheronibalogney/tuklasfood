<x-food-page>
    <section class="hero container">
        <div class="hero-copy">
            <span class="eyebrow"><span class="status-dot"></span> BIG FLAVORS. EVERYDAY INGREDIENTS.</span>
            <h1>Less wondering.<br><span>More cooking.</span></h1>
            <p>Your next “ang sarap!” starts here. Discover Filipino recipes,<br class="desktop-break"> gather the essentials, and make something worth sharing.</p>
            <div class="hero-actions"><a class="button" href="#recipes">Explore the recipes <span aria-hidden="true">↗</span></a>
                @guest @if($demoAvailable)<form method="POST" action="{{ route('demo.login') }}">@csrf<button class="button button-outline" type="submit">Try the demo kitchen <span aria-hidden="true">→</span></button></form>@endif @endguest
                @auth<a class="button button-outline" href="{{ route('dashboard') }}">Open my kitchen →</a>@endauth
            </div>
            @error('demo')<p class="demo-error" role="alert">{{ $message }}</p>@enderror
        </div>
        @if($featured = $recipes->first())
        <div class="kitchen-board">
            <div class="board-pick"><span class="eyebrow">THE KITCHEN PICK / 01</span><h2>{{ $featured->title }}</h2><p>{{ $featured->description }}</p><button type="button" class="board-link" data-open="recipe-{{ $featured->id }}">Let’s cook this <span aria-hidden="true">↗</span></button><span class="board-time">◷ {{ $featured->total_minutes }} min <span>•</span> {{ $featured->servings }} servings</span></div>
            <div class="board-plate"><span class="plate-sticker">HOME<br>COOKED<br><b>HAPPINESS ✳</b></span><x-food-art art-id="hero" /><span class="art-caption">A little serving inspiration.</span></div>
            <div class="board-ingredients"><span class="eyebrow">FAMILIAR INGREDIENTS</span><h3>Small things.<br>Big flavor.</h3><ul>@foreach($featured->ingredients->take(4) as $item)<li><span class="ingredient-dot" aria-hidden="true">✳</span>{{ $item->ingredient->name }}<span>{{ (float) $item->quantity }} {{ $item->unit }}</span></li>@endforeach</ul><button type="button" data-open="recipe-{{ $featured->id }}">See the full recipe <span aria-hidden="true">→</span></button></div>
        </div>
        @endif
    </section>
    <div class="values-band"><div class="container"><span>FILIPINO ROOTS</span><span aria-hidden="true">✳</span><span>REAL KITCHENS</span><span aria-hidden="true">✳</span><span>EVERYDAY FAVORITES</span><span aria-hidden="true">✳</span><span>GOOD FOOD, GOOD MOOD</span></div></div>
    <section class="recipes-section" id="recipes"><div class="container section">
        <div class="section-heading"><div><span class="eyebrow">THE RECIPE LINEUP</span><h2>What’s cooking?</h2></div><p>{{ $recipes->count() }} Filipino favorites. Pick your next craving.</p></div>
        <div class="recipe-tools"><div class="recipe-filters" role="group" aria-label="Filter recipes"><button type="button" class="active" data-filter="all" aria-pressed="true">The whole menu</button><button type="button" data-filter="quick" aria-pressed="false">30 min or less</button><button type="button" data-filter="easy" aria-pressed="false">Easy favorites</button></div><label class="recipe-search"><span aria-hidden="true">⌕</span><input type="search" data-recipe-search placeholder="Find your craving…" aria-label="Search recipes"></label></div>
        <div class="recipe-grid">@foreach($recipes as $recipe)<x-recipe-card :recipe="$recipe" />@endforeach</div>
        <p class="empty-recipes" data-empty-recipes @if($recipes->isNotEmpty()) hidden @endif>No recipes found. Try another search or choose “The whole menu”.</p>
        <p class="collection-note">Open a recipe for the ingredients, equipment, and every cooking step. Illustrations are for inspiration.</p>
    </div></section>
    <section class="section container dashboard-preview" id="inside-kitchen">
        <div class="section-heading"><div><span class="eyebrow">INSIDE YOUR DASHBOARD</span><h2>Your everyday<br>cooking space.</h2></div><p>Here’s what you’ll find when you open My Kitchen.</p></div>
        <div class="dashboard-preview-grid">
            <article class="preview-collection"><span class="eyebrow">01 / RECIPE COLLECTION</span><h3>A good meal starts with a little choice.</h3><p>Search the collection, choose easy favorites, or filter for meals ready in 30 minutes.</p><div class="preview-recipe-list">@foreach($recipes->take(3) as $recipe)<div><span>{{ $recipe->title }}</span><strong>{{ $recipe->total_minutes }} min</strong></div>@endforeach</div><a class="button" href="{{ route('dashboard') }}">Open my kitchen ↗</a></article>
            <article><span class="eyebrow">02 / YOUR KITCHEN AT A GLANCE</span><h3>Recipes. Pantry. Tools.</h3><p>Your dashboard shows your recipe collection alongside the number of pantry ingredients and kitchen tools saved to your account.</p><div class="preview-stat"><strong>{{ $recipes->count() }}</strong><span>Published recipes to explore</span></div></article>
            <article><span class="eyebrow">03 / COOK WITH A PLAN</span><h3>Every step, in one place.</h3><p>Open a recipe to see its ingredients, equipment, servings, and cooking instructions. Your account settings are one click away.</p><a href="#recipes">Browse the recipe collection →</a></article>
            <article class="preview-assistant"><span class="eyebrow">04 / A COOKING COMPANION</span><h3>A little help when you need it.</h3><p>Ask about dinner ideas, ingredient swaps, and cooking techniques. @if(! \App\Support\CookingAssistant::configured()) AI connection is being set up. @endif</p><button type="button" class="button button-outline" data-chat-launch>Meet the cooking assistant ↗</button></article>
        </div>
    </section>
    <section class="section container how-section" id="how-it-works">
        <div><span class="eyebrow">A LITTLE LESS “ANO KAYANG ULAM?”</span><h2>From craving<br>to <span class="highlight-text">kain na.</span></h2><p>You bring the appetite.<br>We’ll bring the inspiration.</p></div>
        <div class="steps-list"><article><span>01</span><div><h3>Find your kind of delicious.</h3><p>Quick lunch or slow Sunday cooking? Pick a recipe that feels right.</p></div><b aria-hidden="true">↗</b></article><article><span>02</span><div><h3>Round up the essentials.</h3><p>Check the ingredients and equipment against what’s already in your kitchen.</p></div><b aria-hidden="true">↗</b></article><article><span>03</span><div><h3>Make it. Share it. Enjoy it.</h3><p>Follow the steps and turn a few simple ingredients into a really good meal.</p></div><b aria-hidden="true">↗</b></article></div>
    </section>
    <section class="container section demo-section" id="demo"><div class="demo-panel"><span class="demo-star" aria-hidden="true">✳</span><div><span class="eyebrow">THERE’S ROOM AT THE TABLE</span><h2>Make yourself<br>at home.</h2><p>Your own little corner of the kitchen starts with an account.</p></div><div class="demo-actions">
        @auth<a class="button" href="{{ route('dashboard') }}">Open my kitchen ↗</a>
        @else<a class="button" href="{{ route('register') }}">Create my account ↗</a>
            @if($demoAvailable)<form method="POST" action="{{ route('demo.login') }}">@csrf<button class="button button-outline" type="submit">Explore as Sample User →</button></form><small>Local demo · No password needed</small>@endif
        @endauth
    </div></div></section>
    @foreach($recipes as $recipe)<x-recipe-dialog :recipe="$recipe" />@endforeach
</x-food-page>

@props(['title' => 'TuklasFood — Discover your next home-cooked favorite'])
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="A little inspiration for your everyday kitchen. Discover Filipino recipes, explore familiar ingredients, and cook something good with TuklasFood.">
    <meta name="theme-color" content="#235b38">
    <title>{{ $title }}</title>
    @vite(['resources/css/app.css', 'resources/css/tuklasfood.css', 'resources/js/tuklasfood.js'])
    @livewireStyles
</head>
<body class="tf-site">
    <a class="skip-link" href="#main">Skip to content</a>
    <header class="site-header">
        <nav class="container nav" aria-label="Main navigation">
            <a class="brand" href="{{ route('home') }}" aria-label="TuklasFood home"><x-food-logo /></a>
            <div class="nav-links"><a href="{{ route('home') }}#recipes">Recipes</a><a href="{{ route('home') }}#inside-kitchen">Inside your kitchen</a><a href="{{ route('home') }}#how-it-works">How it works</a></div>
            <div class="nav-actions">
                @auth
                    <a href="{{ route('dashboard') }}" class="nav-login">My kitchen</a>
                    <form method="POST" action="{{ route('logout') }}">@csrf<button class="button button-small button-outline" type="submit">Log out <span aria-hidden="true">↗</span></button></form>
                @else
                    <a href="{{ route('login') }}" class="nav-login">Log in</a>
                    <a href="{{ route('register') }}" class="button button-small">Get started <span aria-hidden="true">↗</span></a>
                @endauth
            </div>
        </nav>
    </header>
    <main id="main">{{ $slot }}</main>
    <footer class="site-footer container"><a href="{{ route('home') }}" class="brand"><x-food-logo /></a><p>A little inspiration. A lot of sarap.</p><span>© {{ date('Y') }} TuklasFood</span></footer>
    <x-cooking-chat />
    @livewireScripts
</body>
</html>

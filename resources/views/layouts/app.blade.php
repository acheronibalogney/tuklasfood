<x-food-page :title="config('app.name', 'TuklasFood')">
    <x-banner />
    <section class="container section workspace-page">
        @isset($header)<header class="workspace-heading">{{ $header }}</header>@endisset
        {{ $slot }}
    </section>
    @stack('modals')
</x-food-page>

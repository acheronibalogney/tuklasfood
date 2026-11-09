<x-guest-layout>
    <x-authentication-card>
        <x-slot name="logo">
            <x-authentication-card-logo />
        </x-slot>

        <x-validation-errors class="mb-4" />

        @session('status')
            <div class="mb-4 font-medium text-sm text-green-600">
                {{ $value }}
            </div>
        @endsession

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <div>
                <x-label for="email" value="{{ __('Email') }}" />
                <x-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            </div>

            <div class="mt-4">
                <x-label for="password" value="{{ __('Password') }}" />
                <x-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="current-password" />
            </div>

            <div class="block mt-4">
                <label for="remember_me" class="flex items-center">
                    <x-checkbox id="remember_me" name="remember" />
                    <span class="ms-2 text-sm text-gray-600">{{ __('Remember me') }}</span>
                </label>
            </div>

            <div class="flex items-center justify-end mt-4">
                @if (Route::has('password.request'))
                    <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('password.request') }}">
                        {{ __('Forgot your password?') }}
                    </a>
                @endif

                <x-button class="ms-4">
                    {{ __('Log in') }}
                </x-button>
            </div>
        </form>
        @if(app()->environment('local') || config('app.demo_login_enabled'))
            <section class="test-accounts" aria-labelledby="test-accounts-title">
                <div class="test-accounts-heading"><h3 id="test-accounts-title">Try a testing account</h3><span>{{ app()->environment('local') ? 'LOCAL' : 'DEMO ACCESS' }}</span></div>
                <p>Choose a role to explore. No password needed.</p>
                <div class="test-accounts-grid">
                    <form method="POST" action="{{ route('demo.login') }}">
                        @csrf
                        <button type="submit" class="test-account-button"><span class="test-role">User <span aria-hidden="true">↗</span></span><span class="test-email">user@tuklasfood.test</span><span class="test-description">Explore recipes and your kitchen</span></button>
                    </form>
                    <form method="POST" action="{{ route('demo.admin') }}">
                        @csrf
                        <button type="submit" class="test-account-button"><span class="test-role">Admin <span aria-hidden="true">↗</span></span><span class="test-email">admin@tuklasfood.test</span><span class="test-description">Open the admin dashboard</span></button>
                    </form>
                </div>
            </section>
        @endif
    </x-authentication-card>
</x-guest-layout>

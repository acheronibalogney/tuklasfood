<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DemoLoginController extends Controller
{
    public const EMAIL = 'user@tuklasfood.test';

    public const ADMIN_EMAIL = 'admin@tuklasfood.test';

    public function __invoke(Request $request): RedirectResponse
    {
        abort_unless(app()->environment('local') || config('app.demo_login_enabled'), 404);

        $role = $request->route('demo_role', 'user');
        $email = $role === 'admin' ? self::ADMIN_EMAIL : self::EMAIL;
        $user = User::where('email', $email)->where('role', $role)->whereNull('two_factor_secret')->first();

        if (! $user) {
            return back()->withErrors(['demo' => 'The demo account is unavailable. You can create your own account instead.']);
        }

        Auth::login($user);
        $request->session()->regenerate();

        return to_route($role === 'admin' ? 'admin.dashboard' : 'dashboard');
    }
}

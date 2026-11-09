<?php

namespace App\Http\Controllers;

use App\Models\Recipe;
use App\Models\User;
use Illuminate\View\View;

class LandingController extends Controller
{
    public function __invoke(): View
    {
        return view('welcome', [
            'recipes' => Recipe::where('is_published', true)->with(['ingredients.ingredient', 'steps', 'equipment'])->orderBy('id')->get(),
            'demoAvailable' => (app()->environment('local') || config('app.demo_login_enabled'))
                && User::where('email', DemoLoginController::EMAIL)->where('role', 'user')->whereNull('two_factor_secret')->exists(),
        ]);
    }
}

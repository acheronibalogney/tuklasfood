<?php

namespace App\Http\Controllers;

use App\Models\Recipe;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        return view('dashboard', [
            'recipes' => Recipe::where('is_published', true)->with(['ingredients.ingredient', 'steps', 'equipment'])->orderBy('title')->get(),
            'pantryCount' => $request->user()->inventory()->count(),
            'equipmentCount' => $request->user()->equipment()->count(),
        ]);
    }
}

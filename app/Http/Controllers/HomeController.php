<?php

namespace App\Http\Controllers;

use App\Models\Module;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        return view('home', [
            'modules' => Module::query()
                ->published()
                ->withCount(['trainers' => fn ($query) => $query->where('is_published', true)])
                ->orderBy('sort')
                ->get(),
        ]);
    }
}

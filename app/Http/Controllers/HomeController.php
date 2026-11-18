<?php

namespace App\Http\Controllers;

use App\Models\Module;
use App\Models\Test;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $modules = Module::query()
            ->published()
            ->withCount(['trainers' => fn ($query) => $query->where('is_published', true)])
            ->orderBy('sort')
            ->get();

        return view('home', [
            'modules' => $modules,
            'moduleCount' => $modules->count(),
            'trainerCount' => $modules->sum('trainers_count'),
            'eiFormatsCount' => Test::query()
                ->where('is_active', true)
                ->whereIn('kind', ['ei_full', 'ei_express', 'ei_situational'])
                ->count(),
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Reflection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReflectionController extends Controller
{
    public function store(Request $request, TrainerAttempt $attempt): RedirectResponse
    {
        abort_unless($attempt->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'choice' => ['required', 'in:suitcase,basket,mincer'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        Reflection::query()->updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'trainer_attempt_id' => $attempt->id,
            ],
            [
                'choice' => $data['choice'],
                'note' => $data['note'] ?? null,
            ],
        );

        return redirect()
            ->route('trainers.result', $attempt)
            ->with('status', 'Рефлексия сохранена.');
    }
}

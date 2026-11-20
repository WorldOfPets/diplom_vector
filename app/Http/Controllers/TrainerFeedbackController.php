<?php

namespace App\Http\Controllers;

use App\Models\TrainerAttempt;
use App\Models\TrainerFeedback;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TrainerFeedbackController extends Controller
{
    public function store(Request $request, TrainerAttempt $attempt): RedirectResponse
    {
        abort_unless($attempt->user_id === $request->user()->id, 403);
        abort_unless($attempt->status === 'completed', 422, 'Сначала завершите тренажёр.');

        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:1000', 'required_if:rating,1,2'],
        ], [
            'rating.required' => 'Поставьте оценку от 1 до 5.',
            'comment.required_if' => 'Расскажите, пожалуйста, что не понравилось и что можно сделать лучше.',
        ]);

        TrainerFeedback::query()->updateOrCreate(
            ['attempt_id' => $attempt->id],
            [
                'user_id' => $request->user()->id,
                'trainer_id' => $attempt->trainer_id,
                'rating' => (int) $data['rating'],
                'comment' => isset($data['comment']) ? trim((string) $data['comment']) : null,
            ],
        );

        return redirect()
            ->route('trainers.result', $attempt)
            ->withFragment('feedback')
            ->with('status', 'Спасибо за отзыв.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\Feedback;
use App\Services\ContentProgressService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProgressController extends Controller
{
    public function __construct(private readonly ContentProgressService $progress) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $summary = $this->progress->sync($user);
        $certificate = $this->progress->issueCertificate($user);

        return view('progress.index', [
            'summary' => $summary,
            'snapshots' => $summary['snapshots'],
            'feedback' => Feedback::query()->where('user_id', $user->id)->first(),
            'certificate' => $certificate,
        ]);
    }

    public function feedback(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'useful' => ['nullable', 'string', 'in:trainers,theory,shorts,diagnostics'],
            'liked' => ['required', 'string', 'max:1500'],
            'improve' => ['nullable', 'string', 'max:1500'],
        ], [
            'rating.required' => 'Поставьте общую оценку.',
            'rating.between' => 'Оценка должна быть от 1 до 5.',
            'liked.required' => 'Напишите, что было самым полезным.',
        ]);

        $user = $request->user();
        $summary = $this->progress->sync($user);

        if ($summary['percent'] < ContentProgressService::CERTIFICATE_THRESHOLD) {
            return redirect()
                ->route('progress.index')
                ->withErrors(['certificate' => 'Сертификат доступен после прохождения 95% контента.']);
        }

        Feedback::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'rating' => (int) $data['rating'],
                'useful' => $data['useful'] ?? null,
                'liked' => $data['liked'],
                'improve' => $data['improve'] ?? null,
            ],
        );

        $certificate = $this->progress->issueCertificate($user);

        if ($certificate === null) {
            return redirect()
                ->route('progress.index')
                ->withErrors(['certificate' => 'Не удалось выпустить сертификат. Проверьте прогресс и попробуйте ещё раз.']);
        }

        return redirect()
            ->route('certificate.show')
            ->with('status', 'Спасибо за обратную связь! Сертификат готов.');
    }

    public function certificate(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        $summary = $this->progress->sync($user);
        $certificate = Certificate::query()->where('user_id', $user->id)->first();

        if ($certificate === null) {
            return redirect()
                ->route('progress.index')
                ->withErrors([
                    'certificate' => $summary['percent'] >= ContentProgressService::CERTIFICATE_THRESHOLD
                        ? (Feedback::query()->where('user_id', $user->id)->exists()
                            ? 'Сертификат ещё не выпущен. Обновите страницу или обратитесь к администратору.'
                            : 'Сначала заполните форму обратной связи.')
                        : 'Сертификат доступен после прохождения 95% контента.',
                ]);
        }

        return view('progress.certificate', [
            'certificate' => $certificate,
            'summary' => $summary,
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Enums\EiBranch;
use App\Models\Test;
use App\Models\TestAttempt;
use App\Models\TestResponse;
use App\Services\ContentProgressService;
use App\Services\EiInterpretationService;
use App\Services\EiScoringService;
use App\Services\MotivationScoringService;
use App\Support\Levels;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TestController extends Controller
{
    public function __construct(
        private readonly EiScoringService $eiScoring,
        private readonly MotivationScoringService $motivationScoring,
        private readonly EiInterpretationService $interpretation,
    ) {}

    public function index(Request $request): View
    {
        $tests = Test::query()
            ->where('is_active', true)
            ->withCount(['items' => fn ($query) => $query->where('is_active', true)])
            ->orderBy('id')
            ->get()
            ->each(function (Test $test) use ($request): void {
                $test->setAttribute('latest_attempt', $test->attempts()
                    ->where('user_id', $request->user()->id)
                    ->where('status', 'completed')
                    ->latest('finished_at')
                    ->first());
            });

        return view('tests.index', [
            'tests' => $tests,
        ]);
    }

    public function show(Request $request, Test $test): View
    {
        abort_unless($test->is_active, 404);

        $latest = $test->attempts()
            ->where('user_id', $request->user()->id)
            ->where('status', 'completed')
            ->latest('finished_at')
            ->first();

        $inProgress = $test->attempts()
            ->where('user_id', $request->user()->id)
            ->where('status', 'in_progress')
            ->latest()
            ->first();

        return view('tests.show', [
            'test' => $test,
            'itemsCount' => $test->items()->where('is_active', true)->count(),
            'latest' => $latest,
            'inProgress' => $inProgress,
        ]);
    }

    public function start(Request $request, Test $test): RedirectResponse
    {
        abort_unless($test->is_active, 404);

        $latest = $test->attempts()
            ->where('user_id', $request->user()->id)
            ->where('status', 'completed')
            ->latest('finished_at')
            ->first();

        if ($latest !== null && ! $request->boolean('retake')) {
            return redirect()->route('tests.result', $latest);
        }

        $attempt = $test->attempts()
            ->where('user_id', $request->user()->id)
            ->where('status', 'in_progress')
            ->latest()
            ->first();

        if ($attempt === null) {
            $attempt = TestAttempt::query()->create([
                'test_id' => $test->id,
                'user_id' => $request->user()->id,
                'form' => 'A',
                'status' => 'in_progress',
                'started_at' => now(),
            ]);
        }

        return redirect()->route('tests.take', $attempt);
    }

    public function take(Request $request, TestAttempt $attempt): View|RedirectResponse
    {
        abort_unless($attempt->user_id === $request->user()->id, 403);

        if ($attempt->status === 'completed') {
            return redirect()->route('tests.result', $attempt);
        }

        $items = $attempt->test->items()->where('is_active', true)->orderBy('sort')->get();

        if ($items->isEmpty()) {
            abort(503, 'В этом тесте пока нет заданий.');
        }

        $answers = TestResponse::query()
            ->where('test_attempt_id', $attempt->id)
            ->get()
            ->mapWithKeys(fn (TestResponse $response): array => [$response->test_item_id => $response->response]);

        $answers = collect($attempt->draft ?? [])->merge($answers);

        return view('tests.take', [
            'attempt' => $attempt->load('test'),
            'items' => $items->map(fn ($item): array => $item->publicData())->values(),
            'savedAnswers' => $answers,
            'timeLimit' => $attempt->test->time_limit_min,
        ]);
    }

    public function autosave(Request $request, TestAttempt $attempt): JsonResponse
    {
        abort_unless($attempt->user_id === $request->user()->id, 403);
        abort_if($attempt->status === 'completed', 422, 'Попытка уже завершена.');

        $data = $request->validate([
            'answers' => ['required', 'array', 'max:200'],
            'answers.*' => ['array'],
        ]);

        $itemIds = $attempt->test->items()->where('is_active', true)->pluck('id')->all();
        $answers = array_intersect_key($data['answers'], array_flip($itemIds));

        abort_if(strlen((string) json_encode($answers)) > 200000, 422, 'Черновик слишком большой.');

        $attempt->update(['draft' => $answers]);

        return response()->json(['ok' => true]);
    }

    public function submit(Request $request, TestAttempt $attempt): JsonResponse
    {
        abort_unless($attempt->user_id === $request->user()->id, 403);
        abort_if($attempt->status === 'completed', 422, 'Попытка уже завершена.');

        $data = $request->validate([
            'answers' => ['required', 'array', 'max:200'],
            'answers.*' => ['array'],
        ]);

        $items = $attempt->test->items()->where('is_active', true)->get();

        abort_if($items->isEmpty(), 422, 'В этом тесте нет активных заданий.');

        $answers = [];

        foreach ($items as $item) {
            $response = $data['answers'][$item->id] ?? null;
            if (is_array($response)) {
                $answers[$item->id] = $response;
            }
        }

        abort_if(strlen((string) json_encode($answers)) > 200000, 422, 'Ответы слишком большие.');

        try {
            $result = $attempt->test->kind === 'motivation'
                ? $this->motivationScoring->score($attempt->test, $answers)
                : $this->eiScoring->score($attempt->test, $answers);
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'Не удалось оценить ответы: контент теста настроен некорректно.',
            ], 422);
        }

        DB::transaction(function () use ($attempt, $result): void {
            TestResponse::query()->where('test_attempt_id', $attempt->id)->delete();

            foreach ($result['responses'] as $itemId => $response) {
                TestResponse::query()->create([
                    'test_attempt_id' => $attempt->id,
                    'test_item_id' => $itemId,
                    'response' => $response['response'],
                    'score' => $response['score'],
                    'max_score' => $response['max'],
                    'ms' => $response['ms'],
                ]);
            }

            $attempt->update([
                'status' => 'completed',
                'scores' => $result['scores'],
                'draft' => null,
                'overall' => $result['overall'],
                'level' => $result['level'],
                'finished_at' => now(),
                'duration_sec' => $attempt->started_at
                    ? max(1, $attempt->started_at->diffInSeconds(now()))
                    : null,
            ]);
        });

        app(ContentProgressService::class)->sync($request->user());

        return response()->json([
            'redirect' => route('tests.result', $attempt),
        ]);
    }

    public function result(Request $request, TestAttempt $attempt): View
    {
        abort_unless($attempt->user_id === $request->user()->id || $request->user()->isAdmin(), 403);

        $test = $attempt->test;
        $scores = $attempt->scores ?? [];
        $isMotivation = $test->kind === 'motivation';

        if ($isMotivation) {
            $subscaleLabels = $test->config['subscales'] ?? [
                'internal' => 'Внутренняя мотивация',
                'external' => 'Внешняя мотивация',
                'professional' => 'Профессиональная направленность',
            ];

            $branches = collect($scores)->map(fn ($value, $key): array => [
                'key' => $key,
                'label' => $subscaleLabels[$key] ?? $key,
                'value' => (float) $value,
                'color' => '#D97706',
                'text' => $this->motivationText($key, (float) $value),
            ])->values();
        } else {
            $branches = collect(EiBranch::eiBranches())
                ->filter(fn (EiBranch $branch): bool => array_key_exists($branch->value, $scores))
                ->map(fn (EiBranch $branch): array => [
                    'key' => $branch->value,
                    'label' => $branch->label(),
                    'value' => (float) $scores[$branch->value],
                    'color' => $branch->color(),
                    'text' => $this->interpretation->branch($branch->value, (float) $scores[$branch->value]),
                ])
                ->values();
        }

        $previous = TestAttempt::query()
            ->where('user_id', $attempt->user_id)
            ->where('test_id', $attempt->test_id)
            ->where('status', 'completed')
            ->where('id', '!=', $attempt->id)
            ->where('finished_at', '<', $attempt->finished_at)
            ->latest('finished_at')
            ->first();

        return view('tests.result', [
            'attempt' => $attempt,
            'test' => $test,
            'branches' => $branches,
            'level' => $attempt->level ?? 'average',
            'levelInfo' => Levels::info($attempt->level ?? 'average'),
            'overallText' => $isMotivation
                ? 'Мотивация — это не черта характера, а состояние, которое можно поддерживать через цели, среду и маленькие победы.'
                : $this->interpretation->overall((float) $attempt->overall, $attempt->level ?? 'average'),
            'recommendations' => $isMotivation ? [] : $this->interpretation->recommendations($scores),
            'previous' => $previous,
            'delta' => $previous?->overall !== null ? round((float) $attempt->overall - (float) $previous->overall, 1) : null,
        ]);
    }

    private function motivationText(string $key, float $value): string
    {
        return match ($key) {
            'internal' => $value >= 70
                ? 'Интерес и удовольствие от задачи — заметный ресурс. Опирайтесь на него, когда становится тяжело.'
                : ($value >= 45
                    ? 'Интерес есть, но он нестабилен. Помогают выбор задачи, понятный прогресс и маленькие победы.'
                    : 'Интерес пока не основной двигатель. Это не приговор: мотивацию можно собирать через смысл, среду и первые результаты.'),
            'external' => $value >= 70
                ? 'Сильная внешняя опора: требования, оценки, ожидания близких. Проверьте, что из этого поддерживает, а что давит.'
                : ($value >= 45
                    ? 'Внешние факторы влияют умеренно. Полезно понять, где вы действуете из страха, а где — из выбора.'
                    : 'Вы меньше зависите от внешних наград и давления. Это даёт свободу, но требует собственной опоры и смысла.'),
            'professional' => $value >= 70
                ? 'Профессиональный образ будущего достаточно ясен. Проверьте его реальной практикой и разговором с мастером.'
                : ($value >= 45
                    ? 'Направление формируется. Полезны стажировка, разговор с профессионалом и один пробный проект.'
                    : 'Профессиональный образ пока размыт. Начните не с «кем быть», а с одной задачи, которая нравится.'),
            default => $value >= 70
                ? 'Высокий показатель: это заметный ресурс, на который можно опираться.'
                : ($value >= 45
                    ? 'Средний уровень: мотивация есть, но ей нужна опора — цель, среда или регулярная практика.'
                    : 'Низкий уровень: начните с одной конкретной цели и маленького шага, а не с глобального плана.'),
        };
    }
}

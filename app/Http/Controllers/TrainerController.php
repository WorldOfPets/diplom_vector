<?php

namespace App\Http\Controllers;

use App\Models\Trainer;
use App\Models\TrainerAttempt;
use App\Services\ContentProgressService;
use App\Services\Trainers\TrainerEngineRegistry;
use App\Support\RussianTextGuard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TrainerController extends Controller
{
    public function __construct(private readonly TrainerEngineRegistry $engines) {}

    public function show(Request $request, Trainer $trainer): View|RedirectResponse
    {
        abort_unless($trainer->is_published && $trainer->module->is_published, 404);

        if ($trainer->engine === 'camera') {
            return redirect()->route('camera.show');
        }

        $engine = $this->engines->get($trainer->engine);

        $attempt = TrainerAttempt::query()->firstOrCreate(
            [
                'user_id' => $request->user()->id,
                'trainer_id' => $trainer->id,
                'status' => 'in_progress',
            ],
            [
                'trainer_version' => $trainer->version,
                'started_at' => now(),
                'config_snapshot' => $trainer->config,
            ],
        );

        if ((int) $attempt->trainer_version !== (int) $trainer->version) {
            $attempt->forceFill([
                'trainer_version' => $trainer->version,
                'config_snapshot' => $trainer->config,
                'response' => null,
                'started_at' => now(),
            ])->save();
        }

        $completed = TrainerAttempt::query()
            ->where('user_id', $request->user()->id)
            ->where('trainer_id', $trainer->id)
            ->where('status', 'completed')
            ->latest('completed_at')
            ->first();

        return view('trainers.show', [
            'trainer' => $trainer->load('module'),
            'engine' => $engine,
            'config' => $engine->publicConfig($trainer->config ?? []),
            'attempt' => $attempt,
            'completedAttempt' => $completed,
        ]);
    }

    public function submit(Request $request, Trainer $trainer): JsonResponse
    {
        abort_unless($trainer->is_published && $trainer->module?->is_published, 404);
        abort_if($trainer->engine === 'camera', 404);

        $data = $request->validate([
            'response' => ['required', 'array'],
            'attempt_id' => ['nullable', 'integer', 'exists:trainer_attempts,id'],
        ]);

        $response = $data['response'];
        abort_if(strlen((string) json_encode($response)) > 256000, 422, 'Ответ слишком большой. Попробуйте сократить рисунок или количество действий.');

        $this->guardFreeText($response);

        $attempt = null;
        if (isset($data['attempt_id'])) {
            $attempt = TrainerAttempt::query()->find($data['attempt_id']);
        }

        if ($attempt === null || $attempt->user_id !== $request->user()->id || $attempt->trainer_id !== $trainer->id) {
            $attempt = new TrainerAttempt;
            $attempt->user_id = $request->user()->id;
            $attempt->trainer_id = $trainer->id;
            $attempt->started_at = now();
        }

        if ($attempt->exists) {
            if ($attempt->status === 'completed') {
                return response()->json([
                    'message' => 'Эта попытка уже завершена. Обновите страницу и откройте результат.',
                ], 409);
            }

            if ((int) $attempt->trainer_version !== (int) $trainer->version) {
                return response()->json([
                    'message' => 'Тренажёр обновился. Обновите страницу и начните заново — старые ответы не будут смешаны с новыми.',
                ], 409);
            }
        }

        $engine = $this->engines->get($trainer->engine);

        try {
            $result = $engine->score($trainer->config ?? [], $response);
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'Контент тренажёра настроен некорректно. Сообщите администратору.',
            ], 422);
        }

        $threshold = (float) ($trainer->settings['threshold'] ?? 0);

        DB::transaction(function () use ($attempt, $trainer, $result, $response, $threshold): void {
            $attempt->fill([
                'trainer_version' => $trainer->version,
                'status' => 'completed',
                'response' => $response,
                'config_snapshot' => $trainer->config,
                'metrics' => $result->metrics,
                'score' => $result->percent,
                'max_score' => 100,
                'percent' => $result->percent,
                'passed' => $result->percent >= $threshold,
                'feedback' => implode("\n", $result->feedback),
                'duration_sec' => $attempt->started_at ? max(1, $attempt->started_at->diffInSeconds(now())) : null,
                'completed_at' => now(),
            ]);
            $attempt->save();

            $attempt->metrics()->delete();
            foreach ($result->metrics as $key => $value) {
                if ($value === null || ! is_numeric($value)) {
                    continue;
                }

                $attempt->metrics()->create([
                    'metric_key' => $key,
                    'metric_value' => (float) $value,
                    'metric_label' => $key,
                ]);
            }
        });

        app(ContentProgressService::class)->sync($request->user());

        return response()->json([
            'redirect' => route('trainers.result', $attempt),
        ]);
    }

    public function result(Request $request, TrainerAttempt $attempt): View|RedirectResponse
    {
        abort_unless(
            $attempt->user_id === $request->user()->id || $request->user()->isAdmin(),
            403,
        );

        if ($attempt->status !== 'completed') {
            return redirect()->route('trainers.show', $attempt->trainer);
        }

        $attempt->load(['trainer.module', 'reflection']);
        $trainer = $attempt->trainer;

        $next = Trainer::query()
            ->where('is_published', true)
            ->where('module_id', $trainer->module_id)
            ->where('sort', '>', $trainer->sort)
            ->orderBy('sort')
            ->first();

        if ($next === null) {
            $next = Trainer::query()
                ->where('is_published', true)
                ->whereHas('module', fn ($query) => $query->where('is_published', true))
                ->where('module_id', '!=', $trainer->module_id)
                ->orderBy('module_id')
                ->orderBy('sort')
                ->first();
        }

        return view('trainers.result', [
            'attempt' => $attempt,
            'trainer' => $trainer,
            'chart' => $this->chart($trainer, $attempt->metrics ?? []),
            'next' => $next,
        ]);
    }

    /**
     * @param  array<string, mixed>  $metrics
     * @return array<string, mixed>|null
     */
    private function chart(Trainer $trainer, array $metrics): ?array
    {
        $config = $trainer->config ?? [];
        $prefixes = [
            'motive_' => ['categories', 'radar', 'percent'],
            'strategy_' => ['categories', 'bar', 'percent'],
            'area_' => ['areas', 'radar', 'points'],
            'bucket_' => ['buckets', 'bar', 'percent'],
        ];

        foreach ($prefixes as $prefix => [$source, $type, $unit]) {
            $values = [];
            foreach ($metrics as $key => $value) {
                if (str_starts_with((string) $key, $prefix)) {
                    $values[substr((string) $key, strlen($prefix))] = (float) $value;
                }
            }

            if ($values === []) {
                continue;
            }

            $labels = [];
            $colors = [];
            foreach ($config[$source] ?? [] as $entry) {
                $entryKey = $entry['key'] ?? $entry['id'] ?? null;
                if ($entryKey === null) {
                    continue;
                }
                $labels[$entryKey] = $entry['label'];
                $colors[$entryKey] = $entry['color'] ?? '#2D5BFF';
            }

            $ordered = [];
            foreach ($values as $key => $value) {
                $ordered[] = [
                    'label' => $labels[$key] ?? $key,
                    'value' => $value,
                    'color' => $colors[$key] ?? '#2D5BFF',
                ];
            }

            return [
                'type' => $type,
                'unit' => $unit,
                'max' => $unit === 'points' ? max(1, (int) ($config['max'] ?? 10)) : 100,
                'data' => $ordered,
            ];
        }

        return null;
    }

    /**
     * Свободный текст проверяем по словарю: набор букв не должен засчитываться.
     *
     * @param  array<string, mixed>  $response
     */
    private function guardFreeText(array $response): void
    {
        $values = [];

        foreach (['texts', 'fields', 'milestones'] as $key) {
            $group = $response[$key] ?? null;

            if (! is_array($group)) {
                continue;
            }

            foreach ($group as $value) {
                if (is_string($value) && trim($value) !== '') {
                    $values[] = $value;
                }
            }
        }

        if ($values === []) {
            return;
        }

        $guard = app(RussianTextGuard::class);
        $suspicious = [];

        foreach ($values as $value) {
            foreach ($guard->suspiciousWords($value) as $word) {
                $suspicious[$word] = true;
            }
        }

        if ($suspicious === []) {
            return;
        }

        $words = array_slice(array_keys($suspicious), 0, 5);

        throw ValidationException::withMessages([
            'response' => 'Похоже, часть текста — случайный набор букв: «'.implode('», «', $words).'». Проверьте, пожалуйста, написание и попробуйте снова.',
        ]);
    }
}

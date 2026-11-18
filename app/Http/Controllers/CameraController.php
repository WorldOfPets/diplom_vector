<?php

namespace App\Http\Controllers;

use App\Models\Trainer;
use App\Models\TrainerAttempt;
use App\Services\ContentProgressService;
use App\Services\Trainers\Engines\CameraEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CameraController extends Controller
{
    public function show(Request $request): View
    {
        $trainer = $this->cameraTrainer();

        $completed = TrainerAttempt::query()
            ->where('user_id', $request->user()->id)
            ->where('trainer_id', $trainer->id)
            ->where('status', 'completed')
            ->latest('completed_at')
            ->first();

        $progress = $request->session()->get($this->progressKey($request->user()->id, $trainer->id), []);
        $targetIds = collect($trainer->config['targets'] ?? [])->pluck('id')->all();
        $completedTargets = array_values(array_intersect($targetIds, array_keys($progress)));

        return view('camera.show', [
            'trainer' => $trainer,
            'completed' => $completed,
            'hasConsent' => $request->user()->camera_consent_at !== null,
            'completedTargets' => $completedTargets,
            'progress' => $progress,
        ]);
    }

    public function consent(Request $request): RedirectResponse
    {
        $request->user()->forceFill(['camera_consent_at' => now()])->save();

        return redirect()->route('camera.show')->with('status', 'Согласие на камеру сохранено. Кадры не покидают устройство.');
    }

    public function revokeConsent(Request $request): RedirectResponse
    {
        $request->user()->forceFill(['camera_consent_at' => null])->save();

        return redirect()
            ->route('camera.show')
            ->with('status', 'Согласие на камеру отозвано. Доступ отключён.');
    }

    public function progress(Request $request): JsonResponse
    {
        abort_if($request->user()->camera_consent_at === null, 403, 'Сначала подтвердите согласие на камеру.');

        $data = $request->validate([
            'target' => ['required', 'string', 'max:40'],
            'emotion' => ['required', 'string', 'max:40'],
            'confidence' => ['required', 'numeric', 'between:0,100'],
            'duration_ms' => ['required', 'integer', 'between:0,60000'],
            'attempts' => ['required', 'integer', 'between:1,20'],
        ]);

        $trainer = $this->cameraTrainer();
        $targets = collect($trainer->config['targets'] ?? []);
        $targetIds = $targets->pluck('id')->all();

        abort_unless(in_array($data['target'], $targetIds, true), 422, 'Неизвестная эмоция.');

        $threshold = (float) ($targets->firstWhere('id', $data['target'])['threshold'] ?? 80);

        abort_if((float) $data['confidence'] < $threshold, 422, 'Недостаточная уверенность модели.');
        abort_if((int) $data['duration_ms'] < 1500, 422, 'Слишком короткое удержание выражения.');

        $key = $this->progressKey($request->user()->id, $trainer->id);
        $progress = $request->session()->get($key, []);
        $progress[$data['target']] = [
            'confidence' => (float) $data['confidence'],
            'duration_ms' => (int) $data['duration_ms'],
            'attempts' => (int) $data['attempts'],
            'completed_at' => now()->toIso8601String(),
        ];
        $request->session()->put($key, $progress);

        $completed = array_values(array_intersect($targetIds, array_keys($progress)));
        $total = count($targetIds);

        return response()->json([
            'completed' => $completed,
            'count' => count($completed),
            'total' => $total,
            'percent' => $total > 0 ? (int) round(count($completed) / $total * 100) : 0,
            'done' => $total > 0 && count($completed) >= $total,
        ]);
    }

    public function submit(Request $request): JsonResponse
    {
        abort_if($request->user()->camera_consent_at === null, 403, 'Сначала подтвердите согласие на камеру.');

        $trainer = $this->cameraTrainer();
        $targetIds = collect($trainer->config['targets'] ?? [])->pluck('id')->all();
        $key = $this->progressKey($request->user()->id, $trainer->id);
        $progress = $request->session()->get($key, []);
        $completed = array_values(array_intersect($targetIds, array_keys($progress)));

        if ($targetIds === [] || count($completed) < count($targetIds)) {
            return response()->json([
                'message' => 'Сначала пройдите все эмоции практикума.',
            ], 422);
        }

        $data = [
            'targets' => $progress,
            'completed' => count($completed),
            'total' => count($targetIds),
            'duration_ms' => array_sum(array_map(fn (array $item): int => (int) ($item['duration_ms'] ?? 0), $progress)),
            'attempts' => array_sum(array_map(fn (array $item): int => (int) ($item['attempts'] ?? 1), $progress)),
        ];

        $result = app(CameraEngine::class)->score($trainer->config ?? [], $data);
        $threshold = (float) ($trainer->settings['threshold'] ?? 80);

        $attempt = DB::transaction(function () use ($request, $trainer, $data, $result, $threshold, $key): TrainerAttempt {
            $durationMs = (int) ($data['duration_ms'] ?? 0);

            $attempt = TrainerAttempt::query()->create([
                'user_id' => $request->user()->id,
                'trainer_id' => $trainer->id,
                'trainer_version' => $trainer->version,
                'status' => 'completed',
                'response' => $data,
                'config_snapshot' => $trainer->config,
                'metrics' => $result->metrics,
                'score' => $result->percent,
                'max_score' => 100,
                'percent' => $result->percent,
                'passed' => $result->percent >= $threshold,
                'feedback' => implode("\n", $result->feedback),
                'duration_sec' => max(1, (int) round($durationMs / 1000)),
                'started_at' => now()->subMilliseconds(min($durationMs, 60000)),
                'completed_at' => now(),
            ]);

            foreach ($result->metrics as $metricKey => $value) {
                if ($value !== null && is_numeric($value)) {
                    $attempt->metrics()->create([
                        'metric_key' => $metricKey,
                        'metric_value' => (float) $value,
                        'metric_label' => $metricKey,
                    ]);
                }
            }

            $request->session()->forget($key);

            return $attempt;
        });

        app(ContentProgressService::class)->sync($request->user());

        return response()->json([
            'redirect' => route('trainers.result', $attempt),
        ]);
    }

    private function cameraTrainer(): Trainer
    {
        $trainer = Trainer::query()
            ->where('engine', 'camera')
            ->where('is_published', true)
            ->firstOrFail();

        abort_unless($trainer->engine === 'camera', 404);

        return $trainer;
    }

    private function progressKey(int $userId, int $trainerId): string
    {
        return 'camera.progress.'.$userId.'.'.$trainerId;
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Module;
use App\Models\Trainer;
use App\Services\Trainers\TrainerEngineRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TrainerController extends Controller
{
    public function __construct(private readonly TrainerEngineRegistry $engines) {}

    public function index(Request $request): View
    {
        $trainers = Trainer::query()
            ->with('module')
            ->withCount(['attempts' => fn ($query) => $query->where('status', 'completed')])
            ->when($request->integer('module_id'), fn ($query, int $moduleId) => $query->where('module_id', $moduleId))
            ->orderBy('module_id')
            ->orderBy('sort')
            ->get();

        return view('admin.trainers.index', [
            'trainers' => $trainers,
            'modules' => Module::query()->orderBy('sort')->get(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.trainers.form', [
            'trainer' => null,
            'modules' => Module::query()->orderBy('sort')->get(),
            'engines' => $this->engines->options(),
            ...$this->formData($request, null),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['slug'] = $this->slug($data['slug'] ?? null, $data['title']);

        $trainer = Trainer::query()->create($data + ['version' => 1]);
        AuditLog::record('trainer.created', $trainer);

        return redirect()->route('admin.trainers.index')->with('status', 'Тренажёр создан.');
    }

    public function edit(Request $request, Trainer $trainer): View
    {
        return view('admin.trainers.form', [
            'trainer' => $trainer->load('module'),
            'modules' => Module::query()->orderBy('sort')->get(),
            'engines' => $this->engines->options(),
            ...$this->formData($request, $trainer),
        ]);
    }

    public function update(Request $request, Trainer $trainer): RedirectResponse
    {
        $data = $this->validated($request, $trainer);
        $data['slug'] = $this->slug($data['slug'] ?? null, $data['title'], $trainer);

        $configChanged = $trainer->config !== $data['config'];

        $trainer->update($data + [
            'version' => $configChanged ? $trainer->version + 1 : $trainer->version,
        ]);

        AuditLog::record('trainer.updated', $trainer, ['version' => $trainer->version]);

        return redirect()->route('admin.trainers.index')->with('status', 'Тренажёр обновлён.');
    }

    public function toggle(Trainer $trainer): RedirectResponse
    {
        $trainer->update(['is_published' => ! $trainer->is_published]);
        AuditLog::record('trainer.toggled', $trainer, ['published' => $trainer->is_published]);

        return back()->with('status', 'Публикация изменена.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Trainer $trainer = null): array
    {
        $data = $request->validate([
            'module_id' => ['required', 'integer', 'exists:modules,id'],
            'title' => ['required', 'string', 'max:160'],
            'slug' => ['nullable', 'string', 'max:160'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'cover_path' => ['nullable', 'string', 'max:255'],
            'engine' => ['required', Rule::in(array_keys($this->engines->options()))],
            'config_json' => ['required', 'string'],
            'threshold' => ['required', 'numeric', 'between:0,100'],
            'duration_min' => ['required', 'integer', 'between:1,120'],
            'points' => ['required', 'integer', 'between:0,1000'],
            'sort' => ['required', 'integer', 'between:0,1000'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        $config = json_decode($data['config_json'], true);

        if (! is_array($config)) {
            throw ValidationException::withMessages([
                'config_json' => 'Конфигурация должна быть корректным JSON-объектом.',
            ]);
        }

        return [
            'module_id' => $data['module_id'],
            'title' => $data['title'],
            'slug' => $data['slug'] ?? null,
            'subtitle' => $data['subtitle'] ?? null,
            'description' => $data['description'] ?? null,
            'cover_path' => $data['cover_path'] ?? null,
            'engine' => $data['engine'],
            'config' => $config,
            'settings' => ['threshold' => (float) $data['threshold']],
            'duration_min' => $data['duration_min'],
            'points' => $data['points'],
            'sort' => $data['sort'],
            'is_published' => $request->boolean('is_published'),
        ];
    }

    /**
     * @return array{selectedEngine: string, schema: array<string, mixed>, config: array<string, mixed>}
     */
    private function formData(Request $request, ?Trainer $trainer): array
    {
        $requested = $request->string('engine')->trim()->value();
        $selected = $requested !== '' ? $requested : ($trainer?->engine ?? 'reflection');

        if (! $this->engines->has($selected)) {
            $selected = 'reflection';
        }

        $engine = $this->engines->get($selected);
        $config = $trainer !== null && $trainer->engine === $selected ? ($trainer->config ?? []) : [];

        return [
            'selectedEngine' => $selected,
            'schema' => $this->normalizeSchema($engine->schema()),
            'config' => $config,
        ];
    }

    /**
     * @param  array<string, mixed>  $schema
     * @return list<array<string, mixed>>
     */
    private function normalizeSchema(array $schema): array
    {
        $normalized = [];

        foreach ($schema as $name => $field) {
            if (! is_array($field)) {
                continue;
            }

            if (! isset($field['key'])) {
                $field['key'] = is_string($name) ? $name : 'field'.count($normalized);
            }

            if (isset($field['fields']) && is_array($field['fields'])) {
                $field['fields'] = $this->normalizeSchema($field['fields']);
            }

            $normalized[] = $field;
        }

        return $normalized;
    }

    private function slug(?string $slug, string $title, ?Trainer $trainer = null): string
    {
        $base = Str::slug($slug ?: $title, '-', 'ru');

        if ($base === '') {
            $base = 'trainer-'.time();
        }

        $candidate = $base;
        $index = 2;

        while (Trainer::query()->where('slug', $candidate)->when($trainer, fn ($query) => $query->where('id', '!=', $trainer->id))->exists()) {
            $candidate = $base.'-'.$index++;
        }

        return $candidate;
    }
}

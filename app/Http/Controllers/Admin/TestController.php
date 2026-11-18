<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Test;
use App\Models\TestItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TestController extends Controller
{
    private const TYPES = ['single', 'multi', 'scene', 'image_choice', 'best_worst', 'ordering', 'matching', 'scale', 'motivation'];

    public function index(): View
    {
        return view('admin.tests.index', [
            'tests' => Test::query()
                ->withCount(['items', 'attempts'])
                ->orderBy('id')
                ->get(),
        ]);
    }

    public function show(Test $test): View
    {
        return view('admin.tests.show', [
            'test' => $test->load(['items' => fn ($query) => $query->orderBy('sort')]),
            'attemptsCount' => $test->attempts()->where('status', 'completed')->count(),
        ]);
    }

    public function update(Request $request, Test $test): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'time_limit_min' => ['required', 'integer', 'between:1,180'],
        ]);

        $test->update($data);
        AuditLog::record('test.updated', $test);

        return back()->with('status', 'Настройки теста обновлены.');
    }

    public function createItem(Request $request, Test $test): View
    {
        $selectedType = $request->string('type')->trim()->value() ?: 'single';
        if (! in_array($selectedType, self::TYPES, true)) {
            $selectedType = 'single';
        }

        return view('admin.tests.items.form', [
            'test' => $test,
            'item' => null,
            'types' => self::TYPES,
            'selectedType' => $selectedType,
            'config' => [],
            'key' => [],
        ]);
    }

    public function storeItem(Request $request, Test $test): RedirectResponse
    {
        $data = $this->validated($request);
        $data['sort'] = (int) $test->items()->max('sort') + 1;

        $item = $test->items()->create($data);
        AuditLog::record('test.item_created', $item, ['test' => $test->slug]);

        return redirect()->route('admin.tests.show', $test)->with('status', 'Задание добавлено.');
    }

    public function editItem(Request $request, Test $test, TestItem $item): View
    {
        abort_unless($item->test_id === $test->id, 404);

        $selectedType = $request->string('type')->trim()->value() ?: $item->type;
        if (! in_array($selectedType, self::TYPES, true)) {
            $selectedType = $item->type;
        }

        return view('admin.tests.items.form', [
            'test' => $test,
            'item' => $item,
            'types' => self::TYPES,
            'selectedType' => $selectedType,
            'config' => $selectedType === $item->type ? ($item->config ?? []) : [],
            'key' => $selectedType === $item->type ? ($item->key_data ?? []) : [],
        ]);
    }

    public function updateItem(Request $request, Test $test, TestItem $item): RedirectResponse
    {
        abort_unless($item->test_id === $test->id, 404);

        $item->update($this->validated($request));
        AuditLog::record('test.item_updated', $item, ['test' => $test->slug]);

        return redirect()->route('admin.tests.show', $test)->with('status', 'Задание обновлено.');
    }

    public function toggleItem(Test $test, TestItem $item): RedirectResponse
    {
        abort_unless($item->test_id === $test->id, 404);

        $item->update(['is_active' => ! $item->is_active]);
        AuditLog::record('test.item_toggled', $item, ['active' => $item->is_active]);

        return back()->with('status', 'Статус задания изменён.');
    }

    public function toggle(Test $test): RedirectResponse
    {
        $test->update(['is_active' => ! $test->is_active]);
        AuditLog::record('test.toggled', $test, ['active' => $test->is_active]);

        return back()->with('status', 'Статус теста изменён.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'branch' => ['required', 'string', 'max:30'],
            'type' => ['required', Rule::in(self::TYPES)],
            'prompt' => ['required', 'string', 'max:2000'],
            'media_path' => ['nullable', 'string', 'max:255'],
            'config_json' => ['nullable', 'string'],
            'key_json' => ['nullable', 'string'],
            'weight' => ['required', 'numeric', 'between:0.1,10'],
            'difficulty' => ['required', 'integer', 'between:1,3'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $config = $this->decodeJson($data['config_json'] ?? null, 'config_json');
        $key = $this->decodeJson($data['key_json'] ?? null, 'key_json');

        return [
            'branch' => $data['branch'],
            'type' => $data['type'],
            'prompt' => $data['prompt'],
            'media_path' => $data['media_path'] ?? null,
            'config' => $config,
            'key_data' => $key,
            'weight' => $data['weight'],
            'difficulty' => $data['difficulty'],
            'is_active' => $request->boolean('is_active'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJson(?string $value, string $field): array
    {
        if ($value === null || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        if (! is_array($decoded)) {
            throw ValidationException::withMessages([
                $field => 'Некорректный JSON. Проверьте кавычки и запятые.',
            ]);
        }

        return $decoded;
    }
}

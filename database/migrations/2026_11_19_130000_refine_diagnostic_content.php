<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * @var list<string>
     */
    private array $profileTests = ['ei-full', 'ei-express'];

    public function up(): void
    {
        DB::transaction(fn (): mixed => $this->applyUp());
    }

    public function down(): void
    {
        DB::transaction(fn (): mixed => $this->applyDown());
    }

    private function applyUp(): void
    {
        foreach ($this->profileTests as $slug) {
            $this->updateItem($slug, 10, [
                'type' => 'multi',
                'config' => fn (array $config): array => [...$config, 'max' => 2],
                'key_data' => ['weights' => ['o1' => 1.0, 'o2' => 0.8], 'penalty' => 0.2],
            ]);

            $this->updateItem($slug, 11, [
                'type' => 'multi',
                'config' => fn (array $config): array => [...$config, 'max' => 2],
                'key_data' => ['weights' => ['o1' => 1.0, 'o2' => 0.4, 'o3' => 0.3], 'penalty' => 0.2],
            ]);

            $this->updateItem($slug, 17, [
                'prompt' => 'Обиду чаще всего описывают как смесь…',
            ]);

            $this->updateItem($slug, 24, [
                'prompt' => 'Человек чувствует гордость и тревогу одновременно. Что это может быть?',
                'config' => fn (array $config): array => $this->replaceOption(
                    $config,
                    'o1',
                    'Тревога из-за ответственности и феномен самозванца',
                ),
            ]);

            $this->updateItem($slug, 25, [
                'prompt' => 'Что часто стоит за громкой агрессией?',
                'config' => fn (array $config): array => $this->replaceOption(
                    $config,
                    'o1',
                    'Страх, боль или бессилие',
                ),
            ]);

            $this->updateItem($slug, 26, [
                'prompt' => 'Что обычно происходит, если эмоцию долго игнорировать?',
                'config' => function (array $config): array {
                    $config = $this->replaceOption($config, 'o1', 'Она не исчезает бесследно и может проявляться иначе');

                    return $this->replaceOption($config, 'o2', 'Она исчезает навсегда');
                },
            ]);
        }

        $this->updateItem('ei-situational', 7, [
            'key_data' => ['best' => 'o1', 'worst' => 'o2'],
        ]);
    }

    private function applyDown(): void
    {
        foreach ($this->profileTests as $slug) {
            $this->updateItem($slug, 10, [
                'type' => 'single',
                'config' => fn (array $config): array => $this->withoutMax($config),
                'key_data' => ['weights' => ['o1' => 1.0, 'o2' => 0.8]],
            ]);

            $this->updateItem($slug, 11, [
                'type' => 'single',
                'config' => fn (array $config): array => $this->withoutMax($config),
                'key_data' => ['weights' => ['o1' => 1.0, 'o2' => 0.4, 'o3' => 0.3]],
            ]);

            $this->updateItem($slug, 17, [
                'prompt' => 'В русской культуре обиду чаще всего описывают как смесь…',
            ]);

            $this->updateItem($slug, 24, [
                'prompt' => 'Человек чувствует гордость и страх одновременно. Что это может быть?',
                'config' => fn (array $config): array => $this->replaceOption(
                    $config,
                    'o1',
                    'Страх успеха и синдром самозванца',
                ),
            ]);

            $this->updateItem($slug, 25, [
                'prompt' => 'Какая эмоция чаще прячется за громкой агрессией?',
                'config' => fn (array $config): array => $this->replaceOption(
                    $config,
                    'o1',
                    'Страх и бессилие',
                ),
            ]);

            $this->updateItem($slug, 26, [
                'prompt' => 'Что обычно происходит с эмоцией, если её долго подавлять?',
                'config' => function (array $config): array {
                    $config = $this->replaceOption($config, 'o1', 'Она усиливается и находит выход');

                    return $this->replaceOption($config, 'o2', 'Она исчезает');
                },
            ]);
        }

        $this->updateItem('ei-situational', 7, [
            'key_data' => ['best' => 'o1', 'worst' => 'o4'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $changes
     */
    private function updateItem(string $slug, int $sort, array $changes): void
    {
        $row = DB::table('test_items')
            ->join('tests', 'tests.id', '=', 'test_items.test_id')
            ->where('tests.slug', $slug)
            ->where('test_items.sort', $sort)
            ->select('test_items.*')
            ->first();

        if ($row === null) {
            return;
        }

        if (isset($changes['config']) && ! is_array(json_decode((string) $row->config, true))) {
            return;
        }

        $payload = [];

        foreach ($changes as $column => $value) {
            if ($value instanceof Closure) {
                $current = json_decode((string) $row->{$column}, true);
                $payload[$column] = json_encode($value(is_array($current) ? $current : []), JSON_UNESCAPED_UNICODE);

                continue;
            }

            if (in_array($column, ['config', 'key_data'], true)) {
                $payload[$column] = json_encode($value, JSON_UNESCAPED_UNICODE);

                continue;
            }

            $payload[$column] = $value;
        }

        if ($payload === []) {
            return;
        }

        DB::table('test_items')->where('id', $row->id)->update($payload);
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    private function replaceOption(array $config, string $id, string $label): array
    {
        foreach ($config['options'] ?? [] as $index => $option) {
            if (($option['id'] ?? null) === $id) {
                $config['options'][$index]['label'] = $label;

                return $config;
            }
        }

        return $config;
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    private function withoutMax(array $config): array
    {
        unset($config['max']);

        return $config;
    }
};

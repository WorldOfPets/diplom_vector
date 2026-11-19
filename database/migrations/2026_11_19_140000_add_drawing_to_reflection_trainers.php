<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * @var array<string, array<string, mixed>>
     */
    private array $drawings = [
        'card-of-day' => [
            'title' => 'Нарисуйте состояние',
            'prompt' => 'Быстрый скетч: погода, фигура или просто цвет. Не нужно красиво.',
            'background' => 'blank',
            'palette' => ['#FF6B4A', '#F59E0B', '#2D5BFF', '#2BB673', '#7C5CFF'],
            'min_strokes' => 1,
        ],
        'body-map' => [
            'title' => 'Где это в теле?',
            'prompt' => 'Обведите или закрасьте зоны, где живёт напряжение. Можно рисовать поверх силуэта.',
            'background' => 'body',
            'palette' => ['#FF6B4A', '#F59E0B', '#2D5BFF', '#2BB673', '#7C5CFF'],
            'min_strokes' => 2,
        ],
    ];

    public function up(): void
    {
        DB::transaction(function (): void {
            foreach ($this->drawings as $slug => $drawing) {
                $trainer = DB::table('trainers')->where('slug', $slug)->first();

                if ($trainer === null) {
                    continue;
                }

                $config = json_decode((string) $trainer->config, true);

                if (! is_array($config)) {
                    continue;
                }

                $config['drawing'] = $drawing;

                DB::table('trainers')->where('id', $trainer->id)->update([
                    'config' => json_encode($config, JSON_UNESCAPED_UNICODE),
                    'version' => (int) $trainer->version + 1,
                ]);
            }
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            foreach (array_keys($this->drawings) as $slug) {
                $trainer = DB::table('trainers')->where('slug', $slug)->first();

                if ($trainer === null) {
                    continue;
                }

                $config = json_decode((string) $trainer->config, true);

                if (! is_array($config)) {
                    continue;
                }

                unset($config['drawing']);

                DB::table('trainers')->where('id', $trainer->id)->update([
                    'config' => json_encode($config, JSON_UNESCAPED_UNICODE),
                    'version' => max(1, (int) $trainer->version - 1),
                ]);
            }
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->apply(true);
    }

    public function down(): void
    {
        $this->apply(false);
    }

    private function apply(bool $up): void
    {
        $trainer = DB::table('trainers')->where('slug', 'stop-crank')->first();

        if ($trainer === null) {
            return;
        }

        $config = json_decode((string) $trainer->config, true);

        if (! is_array($config)) {
            return;
        }

        if ($up) {
            $config['title'] = 'Стоп-кран 2.0';
            $config['prompt'] = 'Нажимайте на «Спокойно» и не нажимайте на «Триггер». На середине правила поменяются — читайте сигнал, а не привычку.';
            $config['adaptive'] = true;
            $config['min_window_ms'] = 650;
            $config['window_step_ms'] = 60;
            $config['rule_flip_after'] = 15;
            $config['distractors'] = ['устал', 'не успею', 'ошибка', 'все смотрят', 'провал'];
        } else {
            unset(
                $config['adaptive'],
                $config['min_window_ms'],
                $config['window_step_ms'],
                $config['rule_flip_after'],
                $config['distractors'],
            );
            $config['title'] = 'Стоп-кран';
            $config['prompt'] = 'Нажимайте, когда видите «Спокойно». Не нажимайте на «Триггер».';
        }

        DB::table('trainers')->where('id', $trainer->id)->update([
            'title' => $up ? 'Стоп-кран 2.0' : 'Стоп-кран',
            'subtitle' => $up
                ? 'Адаптивная игра на торможение: окно сужается, появляются отвлекающие слова, а правила меняются на середине.'
                : 'Тренировка торможения импульса: нажать вовремя и не нажать на «триггер».',
            'description' => $up
                ? '30 проб. Нажимайте на «Спокойно» и не нажимайте на «Триггер». На середине сигналы меняются местами — тренируется не только торможение, но и переключение правил.'
                : '30 проб. Нажимайте на «Спокойно» и не нажимайте на «Триггер». Это игра про скорость торможения, а не про силу воли.',
            'duration_min' => $up ? 5 : 4,
            'config' => json_encode($config, JSON_UNESCAPED_UNICODE),
            'version' => $up
                ? (int) $trainer->version + 1
                : max(1, (int) $trainer->version - 1),
        ]);
    }
};

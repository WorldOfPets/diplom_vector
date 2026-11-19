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
        $trainer = DB::table('trainers')->where('slug', 'describe-situation')->first();

        if ($trainer === null) {
            return;
        }

        $config = json_decode((string) $trainer->config, true);

        if (! is_array($config)) {
            return;
        }

        if ($up) {
            $config['prompt'] = 'Наставник показывает студенту на экране, что стоит поправить. Что чувствует студент в этот момент? Выберите до 3 эмоций и потребность.';
            $config['image_alt'] = 'Наставник показывает студенту на экран ноутбука';
            $config['emotions'] = [
                ['id' => 'annoyance', 'label' => 'Досада'],
                ['id' => 'shame', 'label' => 'Смущение'],
                ['id' => 'anxiety', 'label' => 'Волнение'],
                ['id' => 'indifference', 'label' => 'Безразличие'],
                ['id' => 'joy', 'label' => 'Радость'],
                ['id' => 'calm', 'label' => 'Спокойствие'],
            ];
            $config['needs'] = [
                ['id' => 'clarity', 'label' => 'Понять, что исправить'],
                ['id' => 'support', 'label' => 'Поддержка'],
                ['id' => 'respect', 'label' => 'Уважение'],
                ['id' => 'pause', 'label' => 'Пауза'],
                ['id' => 'recognition', 'label' => 'Признание'],
            ];
            $config['scoring'] = [
                'emotions' => ['annoyance' => 1.0, 'shame' => 0.7, 'anxiety' => 0.5],
                'need_weights' => ['clarity' => 1.0, 'support' => 0.8, 'respect' => 0.6],
                'intensity_target' => 4,
                'intensity_tolerance' => 1,
                'summary' => 'Досада на замечание — нормальная реакция. Она подсказывает, что вам важно сделать эту работу хорошо.',
            ];
            $subtitle = 'Что чувствует человек и что ему нужно? Сцена разбора проекта.';
            $description = 'Посмотрите на сцену, выберите эмоции и потребность. Ключ учитывает контекст, а не «правильное выражение лица».';
        } else {
            $config['prompt'] = 'Что чувствует студент, который показывает на экран? Выберите до 3 эмоций и потребность.';
            $config['image_alt'] = 'Студент резко показывает на ноутбук';
            $config['emotions'] = [
                ['id' => 'anger', 'label' => 'Злость'],
                ['id' => 'resentment', 'label' => 'Обида'],
                ['id' => 'irritation', 'label' => 'Раздражение'],
                ['id' => 'fear', 'label' => 'Страх'],
                ['id' => 'interest', 'label' => 'Азарт'],
                ['id' => 'indifference', 'label' => 'Безразличие'],
            ];
            $config['needs'] = [
                ['id' => 'heard', 'label' => 'Быть услышанным'],
                ['id' => 'control', 'label' => 'Контроль'],
                ['id' => 'support', 'label' => 'Поддержка'],
                ['id' => 'rest', 'label' => 'Пауза'],
                ['id' => 'respect', 'label' => 'Уважение'],
            ];
            $config['scoring'] = [
                'emotions' => ['anger' => 1.0, 'resentment' => 0.7, 'irritation' => 0.5],
                'need_weights' => ['heard' => 1.0, 'respect' => 0.7],
                'intensity_target' => 4,
                'intensity_tolerance' => 1,
                'summary' => 'За громкой реакцией часто стоит потребность быть услышанным.',
            ];
            $subtitle = 'Что чувствует человек и что ему нужно? Сцена из группового проекта.';
            $description = 'Посмотрите на сцену, выберите эмоции и потребность. Ключ учитывает контекст, а не «правильное выражение лица».';
        }

        DB::table('trainers')->where('id', $trainer->id)->update([
            'subtitle' => $subtitle,
            'description' => $description,
            'config' => json_encode($config, JSON_UNESCAPED_UNICODE),
            'version' => $up
                ? (int) $trainer->version + 1
                : max(1, (int) $trainer->version - 1),
        ]);
    }
};

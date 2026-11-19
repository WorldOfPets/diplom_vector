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
        $trainer = DB::table('trainers')->where('slug', 'focus-switch')->first();

        if ($trainer === null) {
            return;
        }

        $config = json_decode((string) $trainer->config, true);

        if (! is_array($config)) {
            return;
        }

        if ($up) {
            $config = [
                'title' => 'Эмоциональный Струп',
                'prompt' => 'Выберите эмоцию, которую показывает лицо. Слово может противоречить — игнорируйте его.',
                'time_limit_ms' => 2500,
                'options' => [
                    ['id' => 'joy', 'label' => 'Радость'],
                    ['id' => 'anger', 'label' => 'Злость'],
                    ['id' => 'fear', 'label' => 'Страх'],
                    ['id' => 'sadness', 'label' => 'Грусть'],
                    ['id' => 'surprise', 'label' => 'Удивление'],
                    ['id' => 'disgust', 'label' => 'Отвращение'],
                    ['id' => 'neutral', 'label' => 'Спокойствие'],
                ],
                'items' => [
                    ['face' => '😊', 'word' => 'ЗЛОСТЬ', 'answer' => 'joy'],
                    ['face' => '😠', 'word' => 'РАДОСТЬ', 'answer' => 'anger'],
                    ['face' => '😨', 'word' => 'СПОКОЙСТВИЕ', 'answer' => 'fear'],
                    ['face' => '😢', 'word' => 'ВОСТОРГ', 'answer' => 'sadness'],
                    ['face' => '😮', 'word' => 'ГРУСТЬ', 'answer' => 'surprise'],
                    ['face' => '🤢', 'word' => 'НЕЖНОСТЬ', 'answer' => 'disgust'],
                    ['face' => '😐', 'word' => 'ТРЕВОГА', 'answer' => 'neutral'],
                    ['face' => '😊', 'word' => 'ГРУСТЬ', 'answer' => 'joy'],
                    ['face' => '😠', 'word' => 'СТРАХ', 'answer' => 'anger'],
                    ['face' => '😨', 'word' => 'ЗЛОСТЬ', 'answer' => 'fear'],
                    ['face' => '😢', 'word' => 'РАДОСТЬ', 'answer' => 'sadness'],
                    ['face' => '😮', 'word' => 'ОТВРАЩЕНИЕ', 'answer' => 'surprise'],
                    ['face' => '🤢', 'word' => 'УДИВЛЕНИЕ', 'answer' => 'disgust'],
                    ['face' => '😐', 'word' => 'РАДОСТЬ', 'answer' => 'neutral'],
                    ['face' => '😊', 'word' => 'СТРАХ', 'answer' => 'joy'],
                    ['face' => '😠', 'word' => 'СПОКОЙСТВИЕ', 'answer' => 'anger'],
                    ['face' => '😨', 'word' => 'РАДОСТЬ', 'answer' => 'fear'],
                    ['face' => '😢', 'word' => 'УДИВЛЕНИЕ', 'answer' => 'sadness'],
                    ['face' => '😊', 'word' => 'РАДОСТЬ', 'answer' => 'joy'],
                    ['face' => '😢', 'word' => 'ГРУСТЬ', 'answer' => 'sadness'],
                    ['face' => '😠', 'word' => 'ЗЛОСТЬ', 'answer' => 'anger'],
                    ['face' => '😨', 'word' => 'СТРАХ', 'answer' => 'fear'],
                ],
            ];
        } else {
            $config = [
                'title' => 'Переключение фокуса',
                'prompt' => 'Нажимайте на «Цель». Не нажимайте на «Шум».',
                'trials' => 30,
                'go_ratio' => 0.7,
                'response_window_ms' => 1000,
                'go_label' => 'Цель',
                'nogo_label' => 'Шум',
                'go_hint' => 'Действуйте',
                'nogo_hint' => 'Стоп',
            ];
        }

        DB::table('trainers')->where('id', $trainer->id)->update([
            'engine' => $up ? 'stroop' : 'go_no_go',
            'title' => $up ? 'Эмоциональный Струп' : 'Переключение фокуса',
            'subtitle' => $up
                ? 'Смотри на лицо, а не на слово.'
                : 'Действовать по цели и тормозить реакцию на шум.',
            'description' => $up
                ? 'Лицо и слово могут противоречить друг другу. Выберите эмоцию лица, игнорируя текст. Тренировка торможения и распознавания.'
                : 'Игра на внимание: нажимайте на «Цель» и не нажимайте на «Шум». Тренирует переключение и торможение.',
            'duration_min' => $up ? 5 : 4,
            'config' => json_encode($config, JSON_UNESCAPED_UNICODE),
            'version' => $up
                ? (int) $trainer->version + 1
                : max(1, (int) $trainer->version - 1),
        ]);
    }
};

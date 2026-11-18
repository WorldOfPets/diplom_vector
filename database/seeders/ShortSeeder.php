<?php

namespace Database\Seeders;

use App\Models\Short;
use Illuminate\Database\Seeder;

class ShortSeeder extends Seeder
{
    public function run(): void
    {
        $shorts = [
            [
                'slug' => 'emotion-signal',
                'title' => 'Эмоция — это сигнал',
                'subtitle' => 'Не помеха, а информация о потребности',
                'topic' => 'База',
                'video_path' => 'media/shorts/emotion-signal.mp4',
                'poster_path' => 'media/shorts/emotion-signal.jpg',
                'duration_sec' => 9,
                'sort' => 1,
            ],
            [
                'slug' => 'pause-before-reaction',
                'title' => 'Пауза перед реакцией',
                'subtitle' => 'Один выдох меняет исход',
                'topic' => 'Регуляция',
                'video_path' => 'media/shorts/pause-before-reaction.mp4',
                'poster_path' => 'media/shorts/pause-before-reaction.jpg',
                'duration_sec' => 9,
                'sort' => 2,
            ],
            [
                'slug' => 'i-statement',
                'title' => 'Я-высказывание',
                'subtitle' => 'Факт, чувство, просьба',
                'topic' => 'Коммуникация',
                'video_path' => 'media/shorts/i-statement.mp4',
                'poster_path' => 'media/shorts/i-statement.jpg',
                'duration_sec' => 9,
                'sort' => 3,
            ],
            [
                'slug' => 'boundary-without-aggression',
                'title' => 'Граница без агрессии',
                'subtitle' => 'Коротко, спокойно, по делу',
                'topic' => 'Границы',
                'video_path' => 'media/shorts/boundary-without-aggression.mp4',
                'poster_path' => 'media/shorts/boundary-without-aggression.jpg',
                'duration_sec' => 9,
                'sort' => 4,
            ],
            [
                'slug' => 'empathy-not-merging',
                'title' => 'Эмпатия ≠ слияние',
                'subtitle' => 'Понять, не растворяясь',
                'topic' => 'Эмпатия',
                'video_path' => 'media/shorts/empathy-not-merging.mp4',
                'poster_path' => 'media/shorts/empathy-not-merging.jpg',
                'duration_sec' => 9,
                'sort' => 5,
            ],
            [
                'slug' => 'motivation-meaning-step',
                'title' => 'Мотивация = смысл + шаг',
                'subtitle' => 'Не ждать вдохновения',
                'topic' => 'Мотивация',
                'video_path' => 'media/shorts/motivation-meaning-step.mp4',
                'poster_path' => 'media/shorts/motivation-meaning-step.jpg',
                'duration_sec' => 9,
                'sort' => 6,
            ],
            [
                'slug' => 'stop-impulse',
                'title' => 'Стоп-кран для импульса',
                'subtitle' => 'Сначала тело, потом решение',
                'topic' => 'Регуляция',
                'video_path' => 'media/shorts/stop-impulse.mp4',
                'poster_path' => 'media/shorts/stop-impulse.jpg',
                'duration_sec' => 9,
                'sort' => 7,
            ],
            [
                'slug' => 'behind-not-want',
                'title' => 'Что стоит за «не хочу»',
                'subtitle' => 'Страх, усталость или отсутствие смысла',
                'topic' => 'Мотивация',
                'video_path' => 'media/shorts/behind-not-want.mp4',
                'poster_path' => 'media/shorts/behind-not-want.jpg',
                'duration_sec' => 9,
                'sort' => 8,
            ],
        ];

        foreach ($shorts as $short) {
            Short::query()->updateOrCreate(
                ['slug' => $short['slug']],
                $short + ['is_published' => true],
            );
        }
    }
}

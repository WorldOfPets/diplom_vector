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
                'slug' => 'history-emotion-management',
                'title' => 'История управления эмоциями: от древности до наших дней',
                'subtitle' => 'От Свода Хаммурапи и Фрейда до современных офисов',
                'topic' => 'История',
                'video_path' => 'media/shorts/history-emotion-management.mp4',
                'poster_path' => 'media/shorts/history-emotion-management.jpg',
                'width' => 1080,
                'height' => 1920,
                'has_audio' => true,
                'duration_sec' => 37,
                'sort' => 1,
            ],
            [
                'slug' => 'ei-evolution-darwin-goleman',
                'title' => 'Эволюция эмоционального интеллекта: от Дарвина до Голмана',
                'subtitle' => 'Короткая история идеи',
                'topic' => 'История',
                'video_path' => 'media/shorts/ei-evolution-darwin-goleman.mp4',
                'poster_path' => 'media/shorts/ei-evolution-darwin-goleman.jpg',
                'width' => 1080,
                'height' => 1920,
                'has_audio' => true,
                'duration_sec' => 21,
                'sort' => 2,
            ],
            [
                'slug' => 'ei-leadership',
                'title' => 'Как эмоциональный интеллект стал инструментом лидеров',
                'subtitle' => 'Почему навыки общения ценят в управлении',
                'topic' => 'Лидерство',
                'video_path' => 'media/shorts/ei-leadership.mp4',
                'poster_path' => 'media/shorts/ei-leadership.jpg',
                'width' => 1080,
                'height' => 1920,
                'has_audio' => true,
                'duration_sec' => 14,
                'sort' => 3,
            ],
            [
                'slug' => 'soft-skills-success',
                'title' => 'Недостающий элемент успеха: гибкие навыки',
                'subtitle' => 'Почему одних знаний недостаточно',
                'topic' => 'Навыки',
                'video_path' => 'media/shorts/soft-skills-success.mp4',
                'poster_path' => 'media/shorts/soft-skills-success.jpg',
                'width' => 1080,
                'height' => 1920,
                'has_audio' => true,
                'duration_sec' => 14,
                'sort' => 4,
            ],
            [
                'slug' => 'iq-not-guarantee',
                'title' => 'Почему высокий IQ не гарантирует успеха',
                'subtitle' => 'Что ещё влияет на результат',
                'topic' => 'Наука',
                'video_path' => 'media/shorts/iq-not-guarantee.mp4',
                'poster_path' => 'media/shorts/iq-not-guarantee.jpg',
                'width' => 1080,
                'height' => 1920,
                'has_audio' => true,
                'duration_sec' => 22,
                'sort' => 5,
            ],
            [
                'slug' => 'ei-hype-or-literacy',
                'title' => 'Коммерциализация эмоционального интеллекта: видимость против реальности',
                'subtitle' => 'Что продаётся под видом EQ',
                'topic' => 'Критическое мышление',
                'video_path' => 'media/shorts/ei-hype-or-literacy.mp4',
                'poster_path' => 'media/shorts/ei-hype-or-literacy.jpg',
                'width' => 1080,
                'height' => 1920,
                'has_audio' => true,
                'duration_sec' => 37,
                'sort' => 6,
            ],
            [
                'slug' => 'ei-myths-science',
                'title' => 'Научные данные против коммерческого хайпа',
                'subtitle' => 'Что на самом деле показывают исследования',
                'topic' => 'Наука',
                'video_path' => 'media/shorts/ei-myths-science.mp4',
                'poster_path' => 'media/shorts/ei-myths-science.jpg',
                'width' => 1080,
                'height' => 1920,
                'has_audio' => true,
                'duration_sec' => 22,
                'sort' => 7,
            ],
            [
                'slug' => 'ei-myths-debunked',
                'title' => 'Развенчание мифов об эмоциональном интеллекте',
                'subtitle' => 'Что ЭИ не обещает и почему это важно',
                'topic' => 'Наука',
                'video_path' => 'media/shorts/ei-myths-debunked.mp4',
                'poster_path' => 'media/shorts/ei-myths-debunked.jpg',
                'width' => 1080,
                'height' => 1920,
                'has_audio' => true,
                'duration_sec' => 20,
                'sort' => 8,
            ],
            [
                'slug' => 'manage-emotions-practice',
                'title' => 'Как управлять эмоциями для практических задач',
                'subtitle' => 'Регуляция — это навык, а не врождённая черта',
                'topic' => 'Регуляция',
                'video_path' => 'media/shorts/manage-emotions-practice.mp4',
                'poster_path' => 'media/shorts/manage-emotions-practice.jpg',
                'width' => 1080,
                'height' => 1920,
                'has_audio' => true,
                'duration_sec' => 15,
                'sort' => 9,
            ],
        ];

        foreach ($shorts as $short) {
            Short::query()->updateOrCreate(
                ['slug' => $short['slug']],
                $short + ['is_published' => true],
            );
        }

        Short::query()
            ->whereNotIn('slug', array_column($shorts, 'slug'))
            ->delete();
    }
}

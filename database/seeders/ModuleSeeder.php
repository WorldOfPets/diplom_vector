<?php

namespace Database\Seeders;

use App\Models\Module;
use Illuminate\Database\Seeder;

class ModuleSeeder extends Seeder
{
    public function run(): void
    {
        $modules = [
            [
                'slug' => 'understand-self',
                'title' => 'Понимаю себя',
                'subtitle' => 'Замечать эмоции и называть их точнее',
                'description' => 'База эмоционального интеллекта: распознать сигнал, понять причину и не спутать страх с ленью, а злость — с обидой.',
                'color' => 'blue',
                'icon' => 'user',
                'sort' => 1,
            ],
            [
                'slug' => 'manage-state',
                'title' => 'Управляю состоянием',
                'subtitle' => 'Снижать напряжение и выбирать реакцию',
                'description' => 'Практики саморегуляции: дыхание, переключение внимания, работа с мыслями и телом. Управление — не подавление.',
                'color' => 'coral',
                'icon' => 'heart',
                'sort' => 2,
            ],
            [
                'slug' => 'motivation',
                'title' => 'Хочу и делаю',
                'subtitle' => 'Собирать мотивацию из целей и смысла',
                'description' => 'Ядро продукта: мотивы, ценности, цели и маленькие шаги. Здесь становится видно, что стоит за «не хочу» и «не могу».',
                'color' => 'amber',
                'icon' => 'flame',
                'sort' => 3,
            ],
            [
                'slug' => 'understand-others',
                'title' => 'Понимаю других',
                'subtitle' => 'Считывать чувства и потребности',
                'description' => 'Эмпатия без слияния: увидеть эмоцию другого, не потеряв свою. Сцены, диалоги и невербальные сигналы.',
                'color' => 'lilac',
                'icon' => 'target',
                'sort' => 4,
            ],
            [
                'slug' => 'communicate',
                'title' => 'Договариваюсь',
                'subtitle' => 'Конфликты, границы и просьбы',
                'description' => 'Пять стратегий конфликта, Я-высказывания и экологичные формулировки. Навык, который видно в реальных отношениях.',
                'color' => 'mint',
                'icon' => 'grid',
                'sort' => 5,
            ],
        ];

        foreach ($modules as $module) {
            Module::query()->updateOrCreate(
                ['slug' => $module['slug']],
                $module + ['is_published' => true],
            );
        }
    }
}

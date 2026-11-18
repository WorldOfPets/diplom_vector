<?php

namespace Database\Seeders;

use App\Models\Group;
use Illuminate\Database\Seeder;

class GroupSeeder extends Seeder
{
    public function run(): void
    {
        $groups = [
            ['name' => 'ИС-21', 'code' => 'IS-21', 'specialty' => 'Информационные системы и программирование', 'course' => 2, 'curator_name' => 'Елена Сергеева'],
            ['name' => 'ИС-22', 'code' => 'IS-22', 'specialty' => 'Информационные системы и программирование', 'course' => 3, 'curator_name' => 'Елена Сергеева'],
            ['name' => 'ТМ-21', 'code' => 'TM-21', 'specialty' => 'Технология машиностроения', 'course' => 2, 'curator_name' => 'Игорь Мельников'],
            ['name' => 'ПК-22', 'code' => 'PK-22', 'specialty' => 'Строительство и эксплуатация зданий и сооружений', 'course' => 3, 'curator_name' => 'Марина Ковалёва'],
            ['name' => 'ЭЛ-21', 'code' => 'EL-21', 'specialty' => 'Техническая эксплуатация электрического и электромеханического оборудования', 'course' => 2, 'curator_name' => 'Павел Дорохов'],
        ];

        foreach ($groups as $group) {
            Group::query()->updateOrCreate(['code' => $group['code']], $group);
        }
    }
}

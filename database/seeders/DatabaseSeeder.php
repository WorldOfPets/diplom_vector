<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            GroupSeeder::class,
            ModuleSeeder::class,
            EmotionSeeder::class,
            TheorySeeder::class,
            TheoryExtendedSeeder::class,
            TheoryTaskSeeder::class,
            TestSeeder::class,
            TrainerSeeder::class,
            TrainerWave2Seeder::class,
            PhotoLibrarySeeder::class,
            ShortSeeder::class,
        ]);
    }
}

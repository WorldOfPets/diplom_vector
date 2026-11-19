<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('tests')
            ->whereIn('slug', ['ei-full', 'ei-express', 'ei-situational'])
            ->increment('version');

        DB::table('trainers')
            ->where('slug', 'six-trees')
            ->increment('version');
    }

    public function down(): void
    {
        DB::table('tests')
            ->whereIn('slug', ['ei-full', 'ei-express', 'ei-situational'])
            ->where('version', '>', 1)
            ->decrement('version');

        DB::table('trainers')
            ->where('slug', 'six-trees')
            ->where('version', '>', 1)
            ->decrement('version');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shorts', function (Blueprint $table) {
            $table->unsignedSmallInteger('width')->default(720)->after('poster_path');
            $table->unsignedSmallInteger('height')->default(1280)->after('width');
            $table->boolean('has_audio')->default(false)->after('height');
        });
    }

    public function down(): void
    {
        Schema::table('shorts', function (Blueprint $table) {
            $table->dropColumn(['width', 'height', 'has_audio']);
        });
    }
};

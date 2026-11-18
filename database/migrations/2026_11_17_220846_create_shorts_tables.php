<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shorts', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->string('topic', 40)->default('ЭИ');
            $table->string('video_path');
            $table->string('poster_path')->nullable();
            $table->unsignedSmallInteger('duration_sec')->default(9);
            $table->unsignedInteger('likes_count')->default(0);
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        Schema::create('short_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('short_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['short_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('short_likes');
        Schema::dropIfExists('shorts');
    }
};

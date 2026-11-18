<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('camera_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('emotion', 40);
            $table->decimal('confidence', 5, 2)->default(0);
            $table->unsignedInteger('duration_ms')->default(0);
            $table->unsignedTinyInteger('attempts')->default(1);
            $table->timestamp('completed_at');
            $table->timestamps();

            $table->unique(['user_id', 'emotion']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('camera_progress');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trainer_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trainer_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('trainer_version')->default(1);
            $table->string('status', 20)->default('in_progress')->index();
            $table->json('response')->nullable();
            $table->json('config_snapshot')->nullable();
            $table->json('metrics')->nullable();
            $table->decimal('score', 8, 2)->default(0);
            $table->decimal('max_score', 8, 2)->default(0);
            $table->decimal('percent', 5, 2)->default(0);
            $table->boolean('passed')->default(false);
            $table->text('feedback')->nullable();
            $table->unsignedInteger('duration_sec')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'trainer_id', 'completed_at']);
        });

        Schema::create('attempt_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trainer_attempt_id')->constrained()->cascadeOnDelete();
            $table->string('metric_key');
            $table->decimal('metric_value', 10, 3)->default(0);
            $table->string('metric_label')->nullable();
            $table->timestamps();

            $table->index('metric_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attempt_metrics');
        Schema::dropIfExists('trainer_attempts');
    }
};

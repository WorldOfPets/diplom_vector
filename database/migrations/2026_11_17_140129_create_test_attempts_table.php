<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('form', 4)->default('A');
            $table->string('status', 20)->default('in_progress')->index();
            $table->json('scores')->nullable();
            $table->decimal('overall', 5, 2)->nullable();
            $table->string('level', 30)->nullable();
            $table->unsignedInteger('duration_sec')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'test_id', 'finished_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_attempts');
    }
};

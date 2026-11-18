<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_attempt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('test_item_id')->constrained()->cascadeOnDelete();
            $table->json('response')->nullable();
            $table->decimal('score', 8, 3)->default(0);
            $table->decimal('max_score', 8, 3)->default(1);
            $table->unsignedInteger('ms')->nullable();
            $table->timestamps();

            $table->unique(['test_attempt_id', 'test_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_responses');
    }
};

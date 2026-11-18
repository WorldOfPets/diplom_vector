<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_id')->constrained()->cascadeOnDelete();
            $table->string('branch', 30)->index();
            $table->string('type', 30)->index();
            $table->text('prompt');
            $table->string('media_path')->nullable();
            $table->json('config')->nullable();
            $table->json('key_data')->nullable();
            $table->decimal('weight', 6, 2)->default(1);
            $table->unsignedTinyInteger('difficulty')->default(2);
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_items');
    }
};

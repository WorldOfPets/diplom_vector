<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diary_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('entry_date');
            $table->string('slot', 20)->default('now');
            $table->foreignId('emotion_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('intensity')->default(3);
            $table->string('cause')->nullable();
            $table->json('body_zones')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'entry_date', 'slot']);
            $table->index(['user_id', 'entry_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diary_entries');
    }
};

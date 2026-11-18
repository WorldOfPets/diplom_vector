<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('short_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('short_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('viewed_at');
            $table->timestamps();

            $table->unique(['short_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('short_views');
    }
};

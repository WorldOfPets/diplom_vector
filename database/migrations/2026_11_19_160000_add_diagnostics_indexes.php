<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('test_responses', function (Blueprint $table): void {
            $table->index('test_item_id');
        });

        Schema::table('test_items', function (Blueprint $table): void {
            $table->index(['test_id', 'sort']);
        });
    }

    public function down(): void
    {
        Schema::table('test_items', function (Blueprint $table): void {
            $table->dropIndex(['test_id', 'sort']);
        });

        Schema::table('test_responses', function (Blueprint $table): void {
            $table->dropIndex(['test_item_id']);
        });
    }
};

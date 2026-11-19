<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('test_attempts', function (Blueprint $table): void {
            $table->unsignedInteger('items_total')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('test_attempts', function (Blueprint $table): void {
            $table->dropColumn('items_total');
        });
    }
};

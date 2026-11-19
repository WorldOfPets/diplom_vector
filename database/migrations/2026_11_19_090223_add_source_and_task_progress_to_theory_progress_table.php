<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('theory_progress', function (Blueprint $table) {
            $table->timestamp('source_opened_at')->nullable()->after('video_completed_at');
            $table->timestamp('task_completed_at')->nullable()->after('source_opened_at');
        });
    }

    public function down(): void
    {
        Schema::table('theory_progress', function (Blueprint $table) {
            $table->dropColumn(['source_opened_at', 'task_completed_at']);
        });
    }
};

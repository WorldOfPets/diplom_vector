<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('theory_progress', function (Blueprint $table) {
            $table->decimal('video_progress', 5, 2)->default(0)->after('read_at');
            $table->timestamp('video_completed_at')->nullable()->after('video_progress');
        });
    }

    public function down(): void
    {
        Schema::table('theory_progress', function (Blueprint $table) {
            $table->dropColumn(['video_progress', 'video_completed_at']);
        });
    }
};

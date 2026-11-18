<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('theme', 20)->default('light')->after('camera_consent_at');
            $table->string('tone', 20)->default('vibe')->after('theme');
            $table->timestamp('preferences_set_at')->nullable()->after('tone');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['theme', 'tone', 'preferences_set_at']);
        });
    }
};

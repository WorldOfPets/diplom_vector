<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('yandex_id')->nullable()->unique()->after('id');
            $table->string('avatar_url')->nullable()->after('email');
            $table->string('role', 20)->default('student')->index()->after('password');
            $table->unsignedBigInteger('group_id')->nullable()->index()->after('role');
            $table->string('specialty')->nullable()->after('group_id');
            $table->unsignedTinyInteger('course')->nullable()->after('specialty');
            $table->timestamp('consent_at')->nullable()->after('course');
            $table->string('consent_version', 20)->nullable()->after('consent_at');
            $table->timestamp('camera_consent_at')->nullable()->after('consent_version');
            $table->timestamp('last_seen_at')->nullable()->after('camera_consent_at');
            $table->string('password')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['yandex_id']);
            $table->dropColumn([
                'yandex_id',
                'avatar_url',
                'role',
                'group_id',
                'specialty',
                'course',
                'consent_at',
                'consent_version',
                'camera_consent_at',
                'last_seen_at',
            ]);
        });
    }
};

<?php

use App\Support\DiaryCipher;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('diary_entries', function (Blueprint $table) {
            $table->text('cause')->nullable()->change();
            $table->text('body_zones')->nullable()->change();
        });

        DB::table('diary_entries')
            ->select(['id', 'user_id', 'cause', 'body_zones', 'note'])
            ->orderBy('id')
            ->chunkById(100, function ($entries): void {
                foreach ($entries as $entry) {
                    $updates = [];

                    foreach (['cause', 'note'] as $field) {
                        if ($entry->{$field} !== null) {
                            $updates[$field] = DiaryCipher::encrypt((int) $entry->user_id, (string) $entry->{$field});
                        }
                    }

                    if ($entry->body_zones !== null) {
                        $updates['body_zones'] = DiaryCipher::encrypt((int) $entry->user_id, (string) $entry->body_zones);
                    }

                    if ($updates !== []) {
                        DB::table('diary_entries')->where('id', $entry->id)->update($updates);
                    }
                }
            });
    }

    public function down(): void
    {
        DB::table('diary_entries')
            ->select(['id', 'user_id', 'cause', 'body_zones', 'note'])
            ->orderBy('id')
            ->chunkById(100, function ($entries): void {
                foreach ($entries as $entry) {
                    $updates = [];

                    foreach (['cause', 'note'] as $field) {
                        if ($entry->{$field} !== null) {
                            $updates[$field] = DiaryCipher::decrypt((int) $entry->user_id, (string) $entry->{$field});
                        }
                    }

                    if ($entry->body_zones !== null) {
                        $updates['body_zones'] = DiaryCipher::decrypt((int) $entry->user_id, (string) $entry->body_zones);
                    }

                    if ($updates !== []) {
                        DB::table('diary_entries')->where('id', $entry->id)->update($updates);
                    }
                }
            });

        Schema::table('diary_entries', function (Blueprint $table) {
            $table->string('cause')->nullable()->change();
            $table->json('body_zones')->nullable()->change();
        });
    }
};

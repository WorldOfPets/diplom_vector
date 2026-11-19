<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->updateConfig(true);
    }

    public function down(): void
    {
        $this->updateConfig(false);
    }

    private function updateConfig(bool $up): void
    {
        $trainer = DB::table('trainers')->where('slug', 'marionette')->first();

        if ($trainer === null) {
            return;
        }

        $config = json_decode((string) $trainer->config, true);

        if (! is_array($config)) {
            return;
        }

        if ($up) {
            $config['puppet'] = [
                'title' => 'Соберите позу',
                'prompt' => 'Выберите положение головы, плеч, рук и корпуса. Соберите позу для уверенного ответа, затем сравните с тревогой.',
                'target' => 'confident',
                'targets' => [
                    'confident' => 'уверенный ответ',
                    'anxious' => 'тревога',
                    'closed' => 'закрытость',
                    'aggressive' => 'напряжение и злость',
                    'neutral' => 'нейтрально',
                ],
            ];
        } else {
            unset($config['puppet']);
        }

        DB::table('trainers')->where('id', $trainer->id)->update([
            'config' => json_encode($config, JSON_UNESCAPED_UNICODE),
            'version' => $up
                ? (int) $trainer->version + 1
                : max(1, (int) $trainer->version - 1),
        ]);
    }
};

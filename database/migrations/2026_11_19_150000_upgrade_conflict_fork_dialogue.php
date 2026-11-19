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
        $trainer = DB::table('trainers')->where('slug', 'conflict-fork')->first();

        if ($trainer === null) {
            return;
        }

        $config = json_decode((string) $trainer->config, true);

        if (! is_array($config)) {
            return;
        }

        $config = $up ? $this->upgrade($config) : $this->downgrade($config);

        DB::table('trainers')->where('id', $trainer->id)->update([
            'config' => json_encode($config, JSON_UNESCAPED_UNICODE),
            'version' => $up
                ? (int) $trainer->version + 1
                : max(1, (int) $trainer->version - 1),
        ]);
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    private function upgrade(array $config): array
    {
        $config['character'] = 'Мастер';
        $config['tension'] = ['start' => 30, 'label' => 'Напряжение'];
        $config['scoring']['max_score'] = 6;

        $config['nodes']['start']['mood'] = 'tense';
        $config['nodes']['start']['options'][0]['tension'] = 30;
        $config['nodes']['start']['options'][1]['tension'] = 10;
        $config['nodes']['start']['options'][2]['tension'] = -15;
        $config['nodes']['start']['options'][3]['tension'] = -10;

        $config['nodes']['escalation']['mood'] = 'angry';
        $config['nodes']['avoid']['mood'] = 'closed';

        $config['nodes']['collab']['mood'] = 'open';
        unset($config['nodes']['collab']['outcome']);
        $config['nodes']['collab']['options'] = [
            ['label' => 'Уточнить критерии и предложить план переделки', 'next' => 'plan', 'score' => 3, 'tension' => -10, 'feedback' => 'Вы закрепили договорённость конкретным планом.'],
            ['label' => 'Кивнуть и уйти, не уточняя сроки', 'next' => 'comply', 'score' => 1, 'tension' => 5, 'feedback' => 'Согласие без деталей снова оставляет пространство для недопонимания.'],
        ];

        $config['nodes']['boundary']['mood'] = 'calm';
        unset($config['nodes']['boundary']['outcome']);
        $config['nodes']['boundary']['options'] = [
            ['label' => 'После занятия подойти и обсудить работу', 'next' => 'after', 'score' => 3, 'tension' => -10, 'feedback' => 'Вы вернулись к разговору, когда эмоции спали.'],
            ['label' => 'Не подойти: «само рассосётся»', 'next' => 'withdraw', 'score' => 1, 'tension' => 5, 'feedback' => 'Граница осталась, но вопрос — нет.'],
        ];

        $config['nodes']['plan'] = [
            'text' => 'Вы вместе фиксируете, что и когда переделать. Мастер кивает.',
            'mood' => 'open',
            'outcome' => 'Сотрудничество: конкретика, сроки и сохранённые отношения.',
        ];
        $config['nodes']['comply'] = [
            'text' => 'Вы уходите с кивком, но без ясного плана. На следующей паре снова непонятно, что не так.',
            'mood' => 'closed',
            'outcome' => 'Согласие без конкретики: напряжение вернётся.',
        ];
        $config['nodes']['after'] = [
            'text' => 'После занятия вы спокойно обсуждаете работу. Мастер объясняет, что именно его задело.',
            'mood' => 'calm',
            'outcome' => 'Граница + уважение: вы не согласились терпеть, но и не начали войну.',
        ];
        $config['nodes']['withdraw'] = [
            'text' => 'Вы не подходите. Мастер считает, что вы проигнорировали замечание.',
            'mood' => 'closed',
            'outcome' => 'Пауза без разговора: недопонимание закрепляется.',
        ];

        return $config;
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    private function downgrade(array $config): array
    {
        unset($config['character'], $config['tension']);
        $config['scoring']['max_score'] = 3;

        unset(
            $config['nodes']['start']['mood'],
            $config['nodes']['escalation']['mood'],
            $config['nodes']['avoid']['mood'],
            $config['nodes']['collab']['mood'],
            $config['nodes']['boundary']['mood'],
            $config['nodes']['plan'],
            $config['nodes']['comply'],
            $config['nodes']['after'],
            $config['nodes']['withdraw'],
        );

        foreach ($config['nodes']['start']['options'] ?? [] as $index => $option) {
            unset($config['nodes']['start']['options'][$index]['tension']);
        }

        unset($config['nodes']['collab']['options'], $config['nodes']['boundary']['options']);
        $config['nodes']['collab']['outcome'] = 'Сотрудничество: вы получили конкретику и сохранили лицо.';
        $config['nodes']['boundary']['outcome'] = 'Граница + уважение: вы не согласились терпеть, но и не начали войну.';

        return $config;
    }
};

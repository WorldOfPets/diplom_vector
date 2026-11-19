<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * @var list<array{id: string, label: string, slot: string}>
     */
    private array $chips = [
        ['id' => 'chip-fact', 'label' => 'часть проекта по моей теме не готова за два дня до сдачи', 'slot' => 'fact'],
        ['id' => 'chip-feeling', 'label' => 'тревога', 'slot' => 'feeling'],
        ['id' => 'chip-cause', 'label' => 'от этого зависит допуск к защите', 'slot' => 'cause'],
        ['id' => 'chip-request', 'label' => 'пришли свою часть сегодня до 20:00', 'slot' => 'request'],
        ['id' => 'trap-always', 'label' => 'ты всегда всё срываешь', 'slot' => 'fact'],
        ['id' => 'trap-respect', 'label' => 'ты меня не уважаешь', 'slot' => 'feeling'],
        ['id' => 'trap-blame', 'label' => 'сам виноват', 'slot' => 'cause'],
        ['id' => 'trap-vague', 'label' => 'будь ответственнее', 'slot' => 'request'],
    ];

    public function up(): void
    {
        $this->updateConfig(
            $this->chips,
            ['ты всегда', 'ты никогда', 'сам виноват', 'из-за тебя', 'не уважаешь', 'будь ответственнее'],
            true,
        );
    }

    public function down(): void
    {
        $this->updateConfig(null, ['ты всегда', 'ты никогда', 'сам виноват', 'из-за тебя'], false);
    }

    /**
     * @param  list<array{id: string, label: string, slot: string}>|null  $chips
     * @param  list<string>  $blacklist
     */
    private function updateConfig(?array $chips, array $blacklist, bool $increment): void
    {
        $trainer = DB::table('trainers')->where('slug', 'i-statement')->first();

        if ($trainer === null) {
            return;
        }

        $config = json_decode((string) $trainer->config, true);

        if (! is_array($config)) {
            return;
        }

        if ($chips === null) {
            unset($config['chips']);
        } else {
            $config['chips'] = $chips;
        }

        $config['blacklist'] = $blacklist;

        DB::table('trainers')->where('id', $trainer->id)->update([
            'config' => json_encode($config, JSON_UNESCAPED_UNICODE),
            'version' => $increment
                ? (int) $trainer->version + 1
                : max(1, (int) $trainer->version - 1),
        ]);
    }
};

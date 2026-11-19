<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private string $slug = 'wise-man';

    /**
     * @return list<string>
     */
    private function steps(): array
    {
        return [
            'Устройтесь так, чтобы ничего не мешало. Сделайте три спокойных вдоха и выдоха — без усилия и задержек.',
            'Представьте место, где вам спокойно: пустой зал, веранда, мастерская — любое, где вас не торопят. Детали не важны, достаточно общего ощущения.',
            'Там вас ждёт собеседник, которому можно сказать всё как есть. Лицо представлять не обязательно: важно ощущение, что вас выслушают без оценок.',
            'Сформулируйте один вопрос о себе — не о том, как поступить другим, а о том, что происходит с вами: что важно, что мешает, чего хочется.',
            'Собеседник не даёт советов. Он выслушивает и протягивает предмет — знак ответа. Заметьте первое, что появится, не придумывая специально.',
            'Поблагодарите и возвращайтесь. Ниже выберите символ, который был у вас в руках — или ближе всего к этому образу.',
        ];
    }

    public function up(): void
    {
        $this->updateConfig(fn (array $config): array => [
            ...$config,
            'scenario' => implode("\n", $this->steps()),
        ]);
    }

    public function down(): void
    {
        $this->updateConfig(function (array $config): array {
            unset($config['scenario']);

            return $config;
        });
    }

    /**
     * @param  callable(array<string, mixed>): array<string, mixed>  $mutator
     */
    private function updateConfig(callable $mutator): void
    {
        DB::transaction(function () use ($mutator): void {
            $trainer = DB::table('trainers')->where('slug', $this->slug)->first();

            if ($trainer === null) {
                return;
            }

            $config = json_decode((string) $trainer->config, true);

            if (! is_array($config)) {
                return;
            }

            DB::table('trainers')->where('id', $trainer->id)->update([
                'config' => json_encode($mutator($config), JSON_UNESCAPED_UNICODE),
                'version' => (int) $trainer->version + 1,
            ]);
        });
    }
};

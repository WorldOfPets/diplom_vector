<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->replaceStarLabel('Табличка «Стать лучше»');
    }

    public function down(): void
    {
        $this->replaceStarLabel('Табличка «Ближе к Богу»');
    }

    private function replaceStarLabel(string $label): void
    {
        $trainer = DB::table('trainers')->where('slug', 'six-trees')->first();

        if ($trainer === null) {
            return;
        }

        $config = json_decode((string) $trainer->config, true);

        if (! is_array($config)) {
            return;
        }

        foreach ($config['items'] ?? [] as $index => $item) {
            if (($item['id'] ?? null) === 'star') {
                $config['items'][$index]['label'] = $label;
            }
        }

        DB::table('trainers')->where('id', $trainer->id)->update([
            'config' => json_encode($config, JSON_UNESCAPED_UNICODE),
        ]);
    }
};

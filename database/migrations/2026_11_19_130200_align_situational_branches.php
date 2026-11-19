<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->setBranches(['managing']);
    }

    public function down(): void
    {
        $this->setBranches(['understanding', 'managing']);
    }

    /**
     * @param  list<string>  $branches
     */
    private function setBranches(array $branches): void
    {
        $test = DB::table('tests')->where('slug', 'ei-situational')->first();

        if ($test === null) {
            return;
        }

        $config = json_decode((string) $test->config, true);

        if (! is_array($config)) {
            return;
        }

        $config['branches'] = $branches;

        DB::table('tests')->where('id', $test->id)->update([
            'config' => json_encode($config, JSON_UNESCAPED_UNICODE),
        ]);
    }
};

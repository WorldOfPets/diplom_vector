<?php

namespace Tests\Feature;

use App\Models\Test;
use App\Models\TestItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TestItemPublicDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_best_worst_public_data_does_not_leak_option_scores(): void
    {
        $test = Test::query()->create([
            'slug' => 'situational',
            'title' => 'Кейсы',
            'kind' => 'ei_situational',
            'time_limit_min' => 20,
            'is_active' => true,
            'config' => [],
            'version' => 1,
        ]);

        $item = TestItem::query()->create([
            'test_id' => $test->id,
            'branch' => 'managing',
            'type' => 'best_worst',
            'prompt' => 'Выберите лучшую и худшую реакцию',
            'config' => [
                'options' => [
                    ['id' => 'o1', 'label' => 'Спокойно уточнить', 'scores' => ['managing' => 1]],
                    ['id' => 'o2', 'label' => 'Начать спор', 'scores' => ['managing' => 0]],
                ],
            ],
            'key_data' => ['best' => 'o1', 'worst' => 'o2'],
            'weight' => 1,
            'difficulty' => 2,
            'sort' => 1,
            'is_active' => true,
        ]);

        $public = $item->publicData();

        $this->assertArrayNotHasKey('scores', $public['config']['options'][0]);
        $this->assertArrayNotHasKey('scores', $public['config']['options'][1]);
        $this->assertSame('Спокойно уточнить', $public['config']['options'][0]['label']);
    }
}

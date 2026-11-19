<?php

namespace Tests\Unit;

use App\Models\Test;
use App\Models\TestItem;
use App\Services\EiScoringService;
use App\Services\Trainers\Engines\ProfileSlidersEngine;
use App\Services\Trainers\Engines\ReflectionEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiagnosticScoringTest extends TestCase
{
    use RefreshDatabase;

    public function test_multi_item_gives_full_marks_when_both_weighted_emotions_are_selected(): void
    {
        $test = Test::query()->create([
            'slug' => 'ei-test',
            'title' => 'Тест ЭИ',
            'kind' => 'ei_express',
            'is_active' => true,
            'version' => 1,
        ]);

        $item = TestItem::query()->create([
            'test_id' => $test->id,
            'branch' => 'understanding',
            'type' => 'multi',
            'prompt' => 'Выберите эмоции, которые подходят',
            'config' => ['options' => [
                ['id' => 'joy', 'label' => 'Радость'],
                ['id' => 'calm', 'label' => 'Спокойствие'],
            ]],
            'key_data' => ['weights' => ['joy' => 1.0, 'calm' => 0.8]],
            'sort' => 1,
            'is_active' => true,
        ]);

        $result = (new EiScoringService)->score($test, [
            $item->id => ['values' => ['joy', 'calm']],
        ]);

        $this->assertSame(1.8, (float) $result['responses'][$item->id]['max']);
        $this->assertSame(1.8, (float) $result['responses'][$item->id]['score']);
        $this->assertSame(100.0, (float) $result['scores']['understanding']);
    }

    public function test_multi_item_gives_partial_marks_when_only_one_weighted_emotion_is_selected(): void
    {
        $test = Test::query()->create([
            'slug' => 'ei-test',
            'title' => 'Тест ЭИ',
            'kind' => 'ei_express',
            'is_active' => true,
            'version' => 1,
        ]);

        $item = TestItem::query()->create([
            'test_id' => $test->id,
            'branch' => 'understanding',
            'type' => 'multi',
            'prompt' => 'Выберите эмоции, которые подходят',
            'config' => ['options' => [
                ['id' => 'joy', 'label' => 'Радость'],
                ['id' => 'calm', 'label' => 'Спокойствие'],
            ]],
            'key_data' => ['weights' => ['joy' => 1.0, 'calm' => 0.8]],
            'sort' => 1,
            'is_active' => true,
        ]);

        $result = (new EiScoringService)->score($test, [
            $item->id => ['values' => ['joy']],
        ]);

        $score = (float) $result['responses'][$item->id]['score'];
        $percent = (float) $result['scores']['understanding'];

        $this->assertGreaterThan(0.0, $score);
        $this->assertLessThan(1.8, $score);
        $this->assertGreaterThan(0.0, $percent);
        $this->assertLessThan(100.0, $percent);
        $this->assertEqualsWithDelta(55.6, $percent, 0.1);
    }

    public function test_ordering_without_user_action_scores_zero(): void
    {
        [$test, $item] = $this->orderingTest();

        $result = (new EiScoringService)->score($test, [
            $item->id => ['order' => ['a', 'b', 'c'], 'orderTouched' => false],
        ]);

        $this->assertSame(0.0, (float) $result['responses'][$item->id]['score']);
    }

    public function test_ordering_with_user_action_scores_full(): void
    {
        [$test, $item] = $this->orderingTest();

        $result = (new EiScoringService)->score($test, [
            $item->id => ['order' => ['a', 'b', 'c'], 'orderTouched' => true],
        ]);

        $this->assertSame(1.0, (float) $result['responses'][$item->id]['score']);
    }

    public function test_ordering_without_flag_keeps_legacy_scoring(): void
    {
        [$test, $item] = $this->orderingTest();

        $result = (new EiScoringService)->score($test, [
            $item->id => ['order' => ['a', 'b', 'c']],
        ]);

        $this->assertSame(1.0, (float) $result['responses'][$item->id]['score']);
    }

    /**
     * @return array{0: Test, 1: TestItem}
     */
    private function orderingTest(): array
    {
        $test = Test::query()->create([
            'slug' => 'ordering-test',
            'title' => 'Порядок',
            'kind' => 'ei_express',
            'is_active' => true,
            'version' => 1,
        ]);

        $item = TestItem::query()->create([
            'test_id' => $test->id,
            'branch' => 'managing',
            'type' => 'ordering',
            'prompt' => 'Расставьте порядок',
            'config' => ['items' => [
                ['id' => 'a', 'label' => 'Первый'],
                ['id' => 'b', 'label' => 'Второй'],
                ['id' => 'c', 'label' => 'Третий'],
            ]],
            'key_data' => ['order' => ['a', 'b', 'c']],
            'sort' => 1,
            'is_active' => true,
        ]);

        return [$test, $item];
    }

    public function test_profile_sliders_reach_full_percent_when_all_values_are_zero(): void
    {
        $result = (new ProfileSlidersEngine)->score([
            'areas' => [
                ['key' => 'study', 'label' => 'Учёба'],
                ['key' => 'rest', 'label' => 'Отдых'],
            ],
            'max' => 10,
        ], [
            'values' => ['study' => 0, 'rest' => 0],
        ]);

        $this->assertSame(100.0, $result->percent);
        $this->assertSame(0, $result->metrics['area_study']);
        $this->assertSame(0, $result->metrics['area_rest']);
    }

    public function test_reflection_reports_scale_growth_without_claiming_tension_dropped(): void
    {
        $result = (new ReflectionEngine)->score([
            'sliders_before' => [
                ['key' => 'calm', 'label' => 'Спокойствие', 'min' => 0, 'max' => 10],
            ],
            'sliders_after' => [
                ['key' => 'calm', 'label' => 'Спокойствие', 'min' => 0, 'max' => 10],
            ],
        ], [
            'before' => ['calm' => 3],
            'after' => ['calm' => 7],
        ]);

        $feedback = implode(' ', $result->feedback);

        $this->assertStringContainsString('Наибольшее изменение', $feedback);
        $this->assertStringContainsString('«Спокойствие»', $feedback);
        $this->assertStringContainsString('+4', $feedback);
        $this->assertStringNotContainsString('снизилось', mb_strtolower($feedback));
    }
}

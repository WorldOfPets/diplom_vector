<?php

namespace Tests\Unit;

use App\Services\Trainers\Engines\ReflectionEngine;
use PHPUnit\Framework\TestCase;

class ReflectionDrawingTest extends TestCase
{
    public function test_drawing_counts_as_a_required_part_of_the_exercise(): void
    {
        $engine = new ReflectionEngine;

        $result = $engine->score(
            ['drawing' => ['min_strokes' => 2]],
            ['strokes' => [
                ['color' => '#FF6B4A', 'width' => 6, 'points' => [['x' => 0.1, 'y' => 0.2]]],
                ['color' => '#2D5BFF', 'width' => 6, 'points' => [['x' => 0.3, 'y' => 0.4]]],
            ]],
        );

        $this->assertSame(100.0, $result->percent);
        $this->assertSame(2, $result->metrics['strokes']);
        $this->assertStringContainsString('Рисунок сохранён', implode(' ', $result->feedback));
    }

    public function test_drawing_without_strokes_is_incomplete(): void
    {
        $engine = new ReflectionEngine;

        $result = $engine->score(['drawing' => ['min_strokes' => 2]], ['strokes' => []]);

        $this->assertSame(0.0, $result->percent);
        $this->assertSame(0, $result->metrics['strokes']);
        $this->assertStringContainsString('Добавьте хотя бы один штрих', implode(' ', $result->feedback));
    }
}

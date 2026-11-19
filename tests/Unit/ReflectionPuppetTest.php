<?php

namespace Tests\Unit;

use App\Services\Trainers\Engines\ReflectionEngine;
use PHPUnit\Framework\TestCase;

class ReflectionPuppetTest extends TestCase
{
    public function test_puppet_state_counts_as_a_required_part(): void
    {
        $result = (new ReflectionEngine)->score(
            ['puppet' => ['target' => 'confident', 'targets' => ['confident' => 'уверенный ответ']]],
            ['puppet' => [
                'head' => 'up',
                'shoulders' => 'neutral',
                'arms' => 'open',
                'posture' => 'straight',
                'state' => 'confident',
            ]],
        );

        $this->assertSame(100.0, $result->percent);
        $this->assertSame(4, $result->metrics['puppet_parts']);
        $this->assertStringContainsString('уверенный ответ', implode(' ', $result->feedback));
    }

    public function test_puppet_without_state_is_incomplete(): void
    {
        $result = (new ReflectionEngine)->score(['puppet' => ['target' => 'confident']], []);

        $this->assertSame(0.0, $result->percent);
        $this->assertSame(0, $result->metrics['puppet_parts']);
    }
}

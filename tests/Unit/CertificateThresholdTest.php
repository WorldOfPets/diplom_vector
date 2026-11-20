<?php

namespace Tests\Unit;

use App\Services\ContentProgressService;
use PHPUnit\Framework\TestCase;

class CertificateThresholdTest extends TestCase
{
    public function test_95_percent_is_exact_and_not_a_rounded_display_value(): void
    {
        $this->assertSame(171, ContentProgressService::requiredUnits(179));

        $this->assertFalse(ContentProgressService::isCertificateReady(170, 179));
        $this->assertTrue(ContentProgressService::isCertificateReady(171, 179));
    }

    public function test_ready_flag_is_false_for_empty_course(): void
    {
        $this->assertFalse(ContentProgressService::isCertificateReady(0, 0));
    }
}

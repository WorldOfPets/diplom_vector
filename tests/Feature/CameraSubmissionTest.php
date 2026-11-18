<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Group;
use App\Models\Module;
use App\Models\Trainer;
use App\Models\TrainerAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CameraSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_camera_result_is_scored_and_consent_is_saved(): void
    {
        $group = Group::query()->create([
            'name' => 'ИС-21',
            'code' => 'IS-21',
            'course' => 2,
        ]);

        $user = User::factory()->create([
            'role' => UserRole::Student,
            'group_id' => $group->id,
            'consent_at' => now(),
            'consent_version' => '1.0',
        ]);

        $module = Module::query()->create([
            'slug' => 'manage-state',
            'title' => 'Управляю состоянием',
            'sort' => 1,
            'is_published' => true,
        ]);

        Trainer::query()->create([
            'module_id' => $module->id,
            'slug' => 'mimika',
            'title' => 'Мимика',
            'engine' => 'camera',
            'config' => [
                'targets' => [
                    ['id' => 'happy', 'label' => 'Радость', 'emoji' => '😊', 'threshold' => 80],
                ],
            ],
            'settings' => ['threshold' => 80],
            'points' => 20,
            'duration_min' => 5,
            'sort' => 1,
            'is_published' => true,
        ]);

        $response = $this->actingAs($user)->postJson(route('camera.submit'), [
            'target' => 'happy',
            'emotion' => 'happy',
            'confidence' => 90,
            'duration_ms' => 2000,
            'attempts' => 1,
        ]);

        $response->assertOk()->assertJsonStructure(['redirect']);

        $attempt = TrainerAttempt::query()->firstOrFail();

        $this->assertSame('completed', $attempt->status);
        $this->assertSame(90.0, (float) $attempt->percent);
        $this->assertTrue($attempt->passed);
        $this->assertNotNull($user->fresh()->camera_consent_at);
    }
}

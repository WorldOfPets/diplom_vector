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

class CameraProgressTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_camera_targets_are_collected_before_final_submit(): void
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
            'camera_consent_at' => now(),
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
                    ['id' => 'sad', 'label' => 'Грусть', 'emoji' => '😔', 'threshold' => 80],
                ],
            ],
            'settings' => ['threshold' => 80],
            'points' => 20,
            'duration_min' => 5,
            'sort' => 1,
            'is_published' => true,
        ]);

        $this->actingAs($user)
            ->postJson(route('camera.progress'), [
                'target' => 'happy',
                'emotion' => 'happy',
                'confidence' => 88,
                'duration_ms' => 2000,
                'attempts' => 1,
            ])
            ->assertOk()
            ->assertJson([
                'count' => 1,
                'total' => 2,
                'percent' => 50,
                'done' => false,
            ]);

        $this->actingAs($user)
            ->postJson(route('camera.submit'))
            ->assertStatus(422);

        $this->actingAs($user)
            ->postJson(route('camera.progress'), [
                'target' => 'sad',
                'emotion' => 'sad',
                'confidence' => 40,
                'duration_ms' => 2000,
                'attempts' => 1,
            ])
            ->assertStatus(422);

        $this->actingAs($user)
            ->postJson(route('camera.progress'), [
                'target' => 'sad',
                'emotion' => 'sad',
                'confidence' => 92,
                'duration_ms' => 2000,
                'attempts' => 1,
            ])
            ->assertOk()
            ->assertJson([
                'count' => 2,
                'total' => 2,
                'percent' => 100,
                'done' => true,
            ]);

        $this->actingAs($user)
            ->postJson(route('camera.submit'))
            ->assertOk()
            ->assertJsonStructure(['redirect']);

        $attempt = TrainerAttempt::query()->firstOrFail();

        $this->assertSame('completed', $attempt->status);
        $this->assertSame(100.0, (float) $attempt->percent);
        $this->assertTrue($attempt->passed);
        $this->assertSame(2, (int) ($attempt->response['completed'] ?? 0));
        $this->assertSame(2, (int) ($attempt->response['total'] ?? 0));
        $this->assertSame(90.0, (float) ($attempt->metrics['confidence'] ?? 0));
    }
}

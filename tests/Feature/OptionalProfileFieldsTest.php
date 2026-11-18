<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OptionalProfileFieldsTest extends TestCase
{
    use RefreshDatabase;

    public function test_onboarding_does_not_require_group_or_course(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Student,
            'consent_at' => null,
            'consent_version' => null,
        ]);

        $this->actingAs($user)
            ->post(route('onboarding.store'), [
                'name' => 'Студент без группы',
                'consent' => '1',
            ])
            ->assertRedirect(route('dashboard'));

        $user->refresh();

        $this->assertSame('Студент без группы', $user->name);
        $this->assertNull($user->group_id);
        $this->assertNull($user->course);
        $this->assertNotNull($user->consent_at);
        $this->assertFalse($user->needsOnboarding());
    }

    public function test_profile_update_allows_empty_group_and_course(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Student,
            'consent_at' => now(),
            'consent_version' => '1.0',
        ]);

        $this->actingAs($user)
            ->put(route('profile.update'), [
                'name' => 'Без группы',
                'group_id' => '',
                'course' => '',
                'specialty' => '',
            ])
            ->assertRedirect(route('profile.index'));

        $user->refresh();

        $this->assertSame('Без группы', $user->name);
        $this->assertNull($user->group_id);
        $this->assertNull($user->course);
    }
}

<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PreferencesTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_choose_theme_and_tone_which_apply_to_client_pages(): void
    {
        $user = $this->student();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Настройка интерфейса')
            ->assertSee('Привет. Давай сначала посмотрим, что у тебя внутри', false);

        $this->actingAs($user)
            ->post(route('preferences.store'), [
                'theme' => 'dark',
                'tone' => 'professional',
            ])
            ->assertRedirect();

        $user->refresh();

        $this->assertSame('dark', $user->theme);
        $this->assertSame('professional', $user->tone);
        $this->assertNotNull($user->preferences_set_at);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-theme="dark"', false)
            ->assertSee('data-tone="professional"', false)
            ->assertSee('Начните с диагностики — она покажет, с какого модуля лучше стартовать.')
            ->assertDontSee('Настройка интерфейса');
    }

    public function test_guest_can_choose_theme_and_tone_before_registration(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Настройка интерфейса');

        $this->post(route('preferences.store'), [
            'theme' => 'dark',
            'tone' => 'vibe',
        ])->assertRedirect();

        $this->assertSame('dark', session('vektor_theme'));
        $this->assertSame('vibe', session('vektor_tone'));

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('data-theme="dark"', false)
            ->assertSee('data-tone="vibe"', false)
            ->assertDontSee('Настройка интерфейса');
    }

    public function test_preferences_are_validated(): void
    {
        $this->post(route('preferences.store'), [
            'theme' => 'neon',
            'tone' => 'unknown',
        ])->assertSessionHasErrors(['theme', 'tone']);
    }

    public function test_user_can_update_profile_data(): void
    {
        $user = $this->student();
        $group = Group::query()->create([
            'name' => 'ИС-22',
            'code' => 'IS-22',
            'course' => 3,
        ]);

        $this->actingAs($user)
            ->put(route('profile.update'), [
                'name' => 'Новое имя',
                'group_id' => $group->id,
                'course' => 3,
                'specialty' => '09.02.07',
            ])
            ->assertRedirect(route('profile.index'));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Новое имя',
            'group_id' => $group->id,
            'course' => 3,
            'specialty' => '09.02.07',
        ]);
    }

    private function student(): User
    {
        $group = Group::query()->create([
            'name' => 'ИС-21',
            'code' => 'IS-21',
            'course' => 2,
        ]);

        return User::factory()->create([
            'role' => UserRole::Student,
            'group_id' => $group->id,
            'consent_at' => now(),
            'consent_version' => '1.0',
        ]);
    }
}

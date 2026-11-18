<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Certificate;
use App\Models\Group;
use App\Models\Module;
use App\Models\ProgressSnapshot;
use App\Models\Short;
use App\Models\ShortView;
use App\Models\Test;
use App\Models\TestAttempt;
use App\Models\TheoryArticle;
use App\Models\TheoryProgress;
use App\Models\Trainer;
use App\Models\TrainerAttempt;
use App\Models\User;
use App\Services\ContentProgressService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentProgressTest extends TestCase
{
    use RefreshDatabase;

    public function test_progress_is_counted_across_all_content_types_and_creates_three_snapshots(): void
    {
        $group = Group::query()->create(['name' => 'ИС-21', 'code' => 'IS-21', 'course' => 2]);
        $user = User::factory()->create([
            'role' => UserRole::Student,
            'group_id' => $group->id,
            'consent_at' => now(),
            'consent_version' => '1.0',
        ]);

        $module = Module::query()->create([
            'slug' => 'understand-self',
            'title' => 'Понимаю себя',
            'sort' => 1,
            'is_published' => true,
        ]);

        $trainer = Trainer::query()->create([
            'module_id' => $module->id,
            'slug' => 'card-of-day',
            'title' => 'Карта дня',
            'engine' => 'reflection',
            'config' => ['prompt' => 'Выберите карту'],
            'settings' => ['threshold' => 0],
            'sort' => 1,
            'is_published' => true,
        ]);

        $article = TheoryArticle::query()->create([
            'slug' => 'what-is-ei',
            'title' => 'Что такое ЭИ',
            'body' => 'Текст',
            'sort' => 1,
            'is_published' => true,
        ]);

        $test = Test::query()->create([
            'slug' => 'ei-express',
            'title' => 'Экспресс-ЭИ',
            'kind' => 'ei_express',
            'time_limit_min' => 15,
            'is_active' => true,
            'config' => [],
            'version' => 1,
        ]);

        $short = Short::query()->create([
            'slug' => 'emotion-signal',
            'title' => 'Эмоция — это сигнал',
            'topic' => 'База',
            'video_path' => 'media/shorts/emotion-signal.mp4',
            'duration_sec' => 9,
            'sort' => 1,
            'is_published' => true,
        ]);

        $service = app(ContentProgressService::class);

        $this->actingAs($user)->get(route('progress.index'))->assertOk();

        $initial = ProgressSnapshot::query()->where('user_id', $user->id)->where('stage', 'initial')->first();
        $this->assertNotNull($initial);
        $this->assertSame(0.0, (float) $initial->percent);

        TheoryProgress::query()->create([
            'user_id' => $user->id,
            'theory_article_id' => $article->id,
            'read_at' => now(),
        ]);

        TrainerAttempt::query()->create([
            'user_id' => $user->id,
            'trainer_id' => $trainer->id,
            'trainer_version' => 1,
            'status' => 'completed',
            'percent' => 100,
            'passed' => true,
            'completed_at' => now(),
        ]);

        TestAttempt::query()->create([
            'user_id' => $user->id,
            'test_id' => $test->id,
            'status' => 'completed',
            'overall' => 80,
            'finished_at' => now(),
        ]);

        ShortView::query()->create([
            'user_id' => $user->id,
            'short_id' => $short->id,
            'viewed_at' => now(),
            'progress' => 100,
        ]);

        $summary = $service->sync($user);

        $this->assertSame(4, $summary['done']);
        $this->assertSame(4, $summary['total']);
        $this->assertSame(100.0, (float) $summary['percent']);
        $this->assertTrue($summary['certificate_ready']);

        foreach (['initial', 'fifty', 'ninety'] as $stage) {
            $this->assertDatabaseHas('progress_snapshots', [
                'user_id' => $user->id,
                'stage' => $stage,
            ]);
        }

        $this->assertNull($service->issueCertificate($user));

        $this->actingAs($user)
            ->get(route('progress.index'))
            ->assertOk()
            ->assertSee('Сертификат доступен')
            ->assertSee('Отправить и получить сертификат');

        $this->actingAs($user)
            ->post(route('progress.feedback'), [
                'rating' => 5,
                'useful' => 'trainers',
                'liked' => 'Понравилась практика',
                'improve' => 'Больше шортсов',
            ])
            ->assertRedirect(route('certificate.show'));

        $this->assertDatabaseHas('feedback', [
            'user_id' => $user->id,
            'rating' => 5,
        ]);

        $certificate = Certificate::query()->where('user_id', $user->id)->firstOrFail();

        $this->assertStringStartsWith('VK-', $certificate->number);
        $this->assertDatabaseHas('certificates', [
            'user_id' => $user->id,
            'percent' => 100,
        ]);

        $this->actingAs($user)
            ->get(route('certificate.show'))
            ->assertOk()
            ->assertSee($certificate->number);
    }
}

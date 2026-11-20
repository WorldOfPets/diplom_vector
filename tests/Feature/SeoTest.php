<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_is_indexable_and_has_canonical_open_graph_and_structured_data(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('name="robots" content="index,follow"', false)
            ->assertSee('rel="canonical"', false)
            ->assertSee('property="og:title"', false)
            ->assertSee('property="og:image"', false)
            ->assertSee('name="twitter:card"', false)
            ->assertSee('application/ld+json', false)
            ->assertSee('EducationalOrganization', false);
    }

    public function test_sitemap_lists_only_public_pages(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee(route('home'), false)
            ->assertSee(route('credits'), false)
            ->assertSee(route('privacy'), false)
            ->assertDontSee('/dashboard', false)
            ->assertDontSee('/admin', false);
    }

    public function test_authenticated_pages_are_not_indexed(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Student,
            'consent_at' => now(),
            'consent_version' => '1.0',
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('name="robots" content="noindex,nofollow"', false);
    }

    public function test_robots_txt_points_to_the_sitemap(): void
    {
        $robots = file_get_contents(public_path('robots.txt'));

        $this->assertStringContainsString('Sitemap: https://vectoredu.space/sitemap.xml', $robots);
        $this->assertStringContainsString('Disallow: /admin', $robots);
    }
}

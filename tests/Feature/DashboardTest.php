<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_dashboard()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertOk();
    }

    public function test_the_dashboard_route_renders_the_dashboard_page_at_its_url()
    {
        $this->withoutVite();
        $this->actingAs(User::factory()->create());

        $this->assertSame('/dashboard', route('dashboard', absolute: false));

        $this->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('dashboard'));
    }
}

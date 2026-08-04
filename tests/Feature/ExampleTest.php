<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_the_admin_panel_login_is_available(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
        $this->get('/admin/login')->assertSuccessful();
    }
}

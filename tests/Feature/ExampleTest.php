<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_check_confirms_the_application_and_database(): void
    {
        $this->get('/health')
            ->assertOk()
            ->assertJson(['status' => 'ok', 'database' => 'ok']);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->get('/login')->assertOk()->assertSee('給与明細ポータル');
    }
}

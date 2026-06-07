<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $this->get('/')->assertRedirect('/login');

        $user = User::factory()->create([
            'role' => 'superadmin',
            'aktif' => true,
        ]);

        $this->actingAs($user)->get('/')->assertStatus(200);
    }
}

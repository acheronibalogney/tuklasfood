<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin')->assertRedirect('/login');
    }

    public function test_regular_user_gets_403(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/admin')->assertForbidden();
    }

    public function test_admin_can_enter(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'admin'])->save();
        $this->actingAs($admin)->get('/admin')->assertOk();
    }

    public function test_registration_cannot_set_role(): void
    {
        $this->post('/register', [
            'name' => 'Sneaky',
            'email' => 'sneaky@example.test',
            'password' => 'password-123-ABC',
            'password_confirmation' => 'password-123-ABC',
            'role' => 'admin',
        ]);

        $this->assertSame('user', User::where('email', 'sneaky@example.test')->value('role'));
    }
}

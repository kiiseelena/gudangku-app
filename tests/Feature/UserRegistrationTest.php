<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_register_new_user()
    {
        $admin = User::where('username', 'kira')->first();

        $response = $this->actingAs($admin)->postJson('/users', [
            'username' => 'newuser',
            'role' => 'Gudang'
        ]);

        $response->assertStatus(201)
                 ->assertJson([
                     'success' => true
                 ]);

        $this->assertDatabaseHas('users', [
            'username' => 'newuser',
            'role' => 'Gudang'
        ]);
    }

    public function test_non_admin_cannot_register_user()
    {
        $manager = User::where('username', 'jiwon')->first();

        $response = $this->actingAs($manager)->postJson('/users', [
            'username' => 'someuser',
            'role' => 'Gudang'
        ]);

        $response->assertStatus(403);
    }
}

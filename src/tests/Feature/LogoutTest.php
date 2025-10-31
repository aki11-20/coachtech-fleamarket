<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_logout()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password123'),
        ]);
        $this->actingAs($user);

        $token = 'test_token';
        $this->withSession(['_token' => $token]);

        $response = $this->post('/logout', ['_token' => $token]);

        $response->assertRedirect();
        $this->assertGuest();
    }
}

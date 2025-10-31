<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_name_is_required()
    {
        $response = $this->post('/register', [
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(302)
        ->assertSessionHasErrors([
            'name' => 'お名前を入力してください',
        ]);
    }

    public function test_email_is_required() {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(302)
        ->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
        ]);
    }

    public function test_password_is_required() {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(302)
        ->assertSessionHasErrors([
            'password' => 'パスワードを入力してください',
        ]);
    }

    public function test_password_must_be_at_least_8_characters() {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'pass1',
            'password_confirmation' => 'pass1',
        ]);

        $response->assertStatus(302)
        ->assertSessionHasErrors([
            'password' => 'パスワードは8文字以上で入力してください',
        ]);
    }

    public function test_password_confirmation_must_match() {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password111',
        ]);

        $response->assertStatus(302)
        ->assertSessionHasErrors([
            'password' => 'パスワードと一致しません',
        ]);
    }

    public function test_user_is_created_and_redirected_after_successful_registration() {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
        ]);

        $user = User::where('email', 'test@example.com')->first();
        $this->assertTrue(Hash::check('password123', $user->password));

        $response->assertStatus(302);
        $location = $response->headers->get('Location');
        $this->assertTrue(
            str_contains($location, '/email/verify') ||
            str_contains($location, '/profile'), 'Expected redirect to email verification or profile setup, got: ' . $location
        );
    }
}

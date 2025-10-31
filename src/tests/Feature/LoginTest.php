<?php

namespace Tests\Feature;

use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_is_required()
    {
        $response = $this->from('/login')->post('/login', [
            'email' => '',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
        ]);
    }

    public function test_password_is_required() {
        $response = $this->from('/login')->post('/login', [
            'email' => 'test@example.com',
            'password' => '',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください',
        ]);
    }

    public function test_wrong_credentials_show_error_message() {
        $response = $this->from('/login')->post('/login', [
            'email' => 'not-registered@example.com',
            'password' => 'wrong-pass',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors([
            'email' => 'ログイン情報が登録されていません',
        ]);
    }

    public function test_user_can_login_with_correct_credentials() {
        $user = User::create([
            'name' =>'Test',
            'email' => 'test@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
    }
}

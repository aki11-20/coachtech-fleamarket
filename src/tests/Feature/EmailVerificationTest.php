<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\URL;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
    }

    private function registerUser(string $email = 'verify@example.com'): User {
        $user = User::create([
            'name' => '新規ユーザー',
            'email' => $email,
            'password' => Hash::make('password123'),
        ]);
        $this->assertNull($user->email_verified_at);
        return $user;
    }

    public function test_registration_sends_email_verification_notification() {
        Notification::fake();

        $email = 'register-send@example.com';
        $user = $this->registerUser($email);

        $user->sendEmailVerificationNotification();

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_verification_notice_page_shows_button_and_posting_resends_email() {
        Notification::fake();

        $user = $this->registerUser('notice@example.com');

        $this->actingAs($user, 'web')
        ->get(route('verification.notice'))
        ->assertOk()
        ->assertSee('認証はこちらから');

        $this->actingAs($user, 'web')
        ->post(route('verification.send'))
        ->assertRedirect()
        ->assertSessionHas('status', 'verification-link-sent');

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_verifying_email_via_signed_url_redirects_to_profile_edit() {
        $user = $this->registerUser('finish@example.com');

        $verificationUrl = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]);

        $this->actingAs($user, 'web')
        ->get($verificationUrl)
        ->assertRedirect(route('profile.edit'));

        $this->assertNotNull($user->fresh()->email_verified_at, 'Email was not marked as verified');
    }
}

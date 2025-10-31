<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserProfileEditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
    }

    private function createVerifiedUserWithProfile(array $userOverrides = [], array $profileOverrides = []): User {
        $user = User::create(array_merge([
            'name' => 'TEST',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ], $userOverrides));

        $user->markEmailAsVerified();

        $user->profile()->create(array_merge([
            'nickname' => 'テスト',
            'postal_code' => '604-8005',
            'address' => '京都府京都市中京区テスト通1-2',
            'building' => '北館501',
            'image' => 'images/profile/test.jpg',
        ], $profileOverrides));
        return $user;
    }

    public function test_user_profile_edit_page_displays_correct_initial_values()
    {
        $user = $this->createVerifiedUserWithProfile();

        $response = $this->actingAs($user, 'web')
        ->get(route('profile.edit'));

        $response->assertOk();
        $response->assertSee('http://localhost/images/profile/test.jpg', false);
        $response->assertSee('テスト');
        $response->assertSee('604-8005');
        $response->assertSee('京都府京都市中京区テスト通1-2');
        $response->assertSee('北館501');
    }
}

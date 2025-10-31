<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use App\Http\Responses\RegisterResponse;
use Laravel\Fortify\Contracts\VerifyEmailResponse as VerifyEmailResponseContract;
use App\Http\Responses\VerifyEmailResponse;



class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(RegisterResponseContract::class, RegisterResponse::class);
        $this->app->singleton(VerifyEmailResponseContract::class, VerifyEmailResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::registerView(function () {
            return view('auth.register');
        });
        Fortify::loginView(function () {
            return view('auth.login');
        });
        Fortify::verifyEmailView(function () {
            return view('auth.verify');
        });
        
        Fortify::authenticateUsing(function ($request) {
            Validator::make(
                $request->all(),
                (new LoginRequest)->rules(),
                (new LoginRequest)->messages()
            )->validate();

            $user = User::where('email', $request->email)->first();
            if ($user && Hash::check($request->password, $user->password)) {
                return $user;
            }
            throw ValidationException::withMessages([
                'email' => 'ログイン情報が登録されていません',
            ]);
        });

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(50)->by($request->email.'|'.$request->ip());
        });
    }
}

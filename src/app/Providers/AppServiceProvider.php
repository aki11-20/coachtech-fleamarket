<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Contracts\RegisterResponse;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(RegisterResponse::class, function ($app) {
            return new class implements RegisterResponse {
                public function toResponse($request) {
                    if ($request->user() && ! $request->user()->hasVerifiedEmail()) {
                        return redirect()->route('verification.notice');
                    }
                    return redirect()->route('profile.edit');
                }
            };
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}

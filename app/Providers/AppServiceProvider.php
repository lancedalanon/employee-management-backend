<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;
use Spatie\Csp\CspServiceProvider;
use Spatie\LaravelSettings\LaravelSettingsServiceProvider;
use Illuminate\Routing\UrlGenerator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(UrlGenerator $url): void
    {
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);
        CspServiceProvider::class;
        LaravelSettingsServiceProvider::class;

        if (env('APP_ENV') == 'production') {
            $url->forceScheme('https');
        }
    }
}

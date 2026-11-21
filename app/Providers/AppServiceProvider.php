<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

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
    public function boot(): void
    {
        $isLocalHost = in_array(request()->getHost(), ['localhost', '127.0.0.1', '::1'], true);

        if (!$isLocalHost && (request()->header('X-Forwarded-Proto') === 'https' || $this->app->environment('production'))) {
            URL::forceScheme('https');
        }
    }
}

<?php

namespace App\Providers;

use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // $opensslConf = env('OPENSSL_CONF');

        // if (is_string($opensslConf) && $opensslConf !== '' && is_file($opensslConf)) {
        //     putenv('OPENSSL_CONF='.$opensslConf);
        // }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Authenticate::redirectUsing(fn () => route('login'));

    if (config('app.env') === 'production') {
        URL::forceScheme('https');
        URL::forceRootUrl(config('app.url'));
    }

    }
}
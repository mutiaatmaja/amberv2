<?php

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Support\Facades\App;
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
        $locale = config('app.locale', 'id');

        App::setLocale($locale);
        Carbon::setLocale($locale);

        setlocale(LC_TIME, 'id_ID.UTF-8', 'id_ID.utf8', 'id_ID', 'Indonesian');
    }
}

<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // Routes are loaded via bootstrap/app.php withRouting()
        // Do not manually load routes here to avoid duplicate route registration
    }
}

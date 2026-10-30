<?php

namespace App\Providers;

use App\Domain\Activity\ActivityLogger;
use App\Domain\Settings\SettingService;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ActivityLogger::class);
        $this->app->singleton(SettingService::class);
    }

    public function boot(): void
    {
        // Kata sandi minimal 14 karakter (Bagian 13.1).
        Password::defaults(function () {
            return Password::min((int) config('nara.password_min_length', 14));
        });

        // HTTPS wajib di produksi (Bagian 13.1).
        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }
    }
}

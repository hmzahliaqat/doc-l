<?php

namespace App\Providers;

use App\Models\SuperAdminSetting;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Storage;

class SuperAdminSettingsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // Share SuperAdminSettings with all views
        View::composer('*', function ($view) {
            $settings = SuperAdminSetting::first();

            $appSettings = [
                'app_name' => $settings ? $settings->app_name : config('app.name'),
                'app_logo' => null,
                'app_logo_url' => null,
            ];

            if ($settings && $settings->app_logo) {
                $appSettings['app_logo'] = $settings->app_logo;
                $appSettings['app_logo_url'] = url(Storage::url($settings->app_logo));
            }

            $view->with('appSettings', $appSettings);
        });
    }
}

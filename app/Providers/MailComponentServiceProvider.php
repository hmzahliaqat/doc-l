<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\Compilers\BladeCompiler;

class MailComponentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register the mail component namespace
        $this->callAfterResolving(BladeCompiler::class, function (BladeCompiler $blade) {
            $blade->componentNamespace('Illuminate\\Mail\\Markdown\\Components', 'mail');
        });
    }
}

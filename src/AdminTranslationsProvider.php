<?php

namespace Brackets\AdminTranslations;

use Brackets\Admin\AdminProvider;
use Brackets\AdminTranslations\Commands\ScanAndSave;
use Illuminate\Support\ServiceProvider;
use Brackets\AdminTranslations\TranslationServiceProvider;

class AdminTranslationsProvider extends ServiceProvider {

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/admin-translations.php', 'admin-translations');

        if(config('admin-translations.use-routes', true)) {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        }

        // this should be removed once in Laravel 5.5 and provider auto-discovery
        $this->app->register(TranslationServiceProvider::class);
        $this->app->register(AdminProvider::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->commands([
            ScanAndSave::class,
        ]);

        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'brackets/admin-translations');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/admin-translations.php' => config_path('admin-translations.php'),
            ], 'config');

            if (!class_exists('CreateTranslationsTable')) {
                $timestamp = date('Y_m_d_His', time());
                $this->publishes([
                    __DIR__ . '/../database/migrations/create_translations_table.php.stub' => database_path('migrations') . '/' . $timestamp . '_create_translations_table.php',
                ], 'migrations');
            }
        }
    }
}

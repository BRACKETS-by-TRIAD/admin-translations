<?php

namespace Brackets\AdminTranslations;

use Illuminate\Support\ServiceProvider;
use Spatie\TranslationLoader\TranslationServiceProvider;

class AdminTranslationsProvider extends ServiceProvider {

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        if(config('admin-translations.use-routes', true)) {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        }

        $this->mergeConfigFrom(__DIR__.'/../config/admin-translations.php', 'admin-translations');

        $this->app->register(TranslationServiceProvider::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/admin-translations.php' => config_path('admin-translations.php'),
            ], 'config');

            if (!class_exists('ChangeLanguageLinesTable')) {
                $timestamp = date('Y_m_d_His', time()+2);

                $this->publishes([
                    __DIR__ . '/../database/migrations/change_language_lines_table.php.stub' => database_path('migrations') . '/' . $timestamp . '_change_language_lines_table.php',
                ], 'migrations');
            }
        }
    }

}

<?php

namespace Brackets\AdminTranslations;

use Illuminate\Support\ServiceProvider;

class AdminTranslationsProvider extends ServiceProvider {
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
    }
}

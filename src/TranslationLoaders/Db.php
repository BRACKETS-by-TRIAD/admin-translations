<?php

namespace Brackets\AdminTranslations\TranslationLoaders;

use Brackets\AdminTranslations\LanguageLine;
use Brackets\AdminTranslations\Exceptions\InvalidConfiguration;

class Db implements TranslationLoader
{
    public function loadTranslations(string $locale, string $group): array
    {
        $model = $this->getConfiguredModelClass();

        return $model::getTranslationsForGroup($locale, $group);
    }

    protected function getConfiguredModelClass(): string
    {
        $modelClass = config('laravel-translation-loader.model');

        if (! is_a(new $modelClass, LanguageLine::class)) {
            throw InvalidConfiguration::invalidModel($modelClass);
        }

        return $modelClass;
    }
}

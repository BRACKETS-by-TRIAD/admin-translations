<?php

namespace Brackets\AdminTranslations\TranslationLoaders;

use Brackets\AdminTranslations\Exceptions\InvalidConfiguration;
use Brackets\AdminTranslations\Translation;

class Db implements TranslationLoader
{
    public function loadTranslations(string $locale, string $group, string $namespace): array
    {
        $model = $this->getConfiguredModelClass();

        return $model::getTranslationsForGroupAndNamespace($locale, $group, $namespace);
    }

    protected function getConfiguredModelClass(): string
    {
        $modelClass = config('admin-translations.model');

        if (! is_a(new $modelClass, Translation::class)) {
            throw InvalidConfiguration::invalidModel($modelClass);
        }

        return $modelClass;
    }
}

<?php

namespace Brackets\AdminTranslations;

use Illuminate\Translation\FileLoader;
use Brackets\AdminTranslations\TranslationLoaders\TranslationLoader;

class TranslationLoaderManager extends FileLoader
{
    /**
     * Load the messages for the given locale.
     *
     * @param string $locale
     * @param string $group
     * @param string $namespace
     *
     * @return array
     */
    public function load($locale, $group, $namespace = null): array
    {
        $fileTranslations = parent::load($locale, $group, $namespace);

        $loaderTranslations = $this->getTranslationsForTranslationLoaders($locale, $group, $namespace);

        return $this->mergeTranslations($fileTranslations, $loaderTranslations);
    }

    protected function getTranslationsForTranslationLoaders(
        string $locale,
        string $group,
        string $namespace
    ): array {
        return collect(config('admin-translations.translation_loaders'))
            ->map(function (string $className) {
                return app($className);
            })
            ->mapWithKeys(function (TranslationLoader $translationLoader) use ($locale, $group, $namespace) {
                return $translationLoader->loadTranslations($locale, $group, $namespace);
            })
            ->toArray();
    }

    protected function mergeTranslations($fileTranslations, $loaderTranslations) {
        $globalDottedTranslations = array_dot($loaderTranslations) + array_dot($fileTranslations);
        $globalTranslations = array();
        foreach ($globalDottedTranslations as $key => $value) {
            array_set($globalTranslations, $key, $value);
        }
        return $globalTranslations;
    }
}

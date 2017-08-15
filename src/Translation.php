<?php

namespace Brackets\AdminTranslations;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Spatie\TranslationLoader\LanguageLine;

class Translation extends LanguageLine
{
    use SoftDeletes;

    public static function boot()
    {
        static::bootTraits();

        static::saved(function (Translation $translation) {
            $translation->flushGroupCache();
        });

        static::deleted(function (Translation $translation) {
            $translation->flushGroupCache();
        });
    }

    public static function getTranslationsForGroupAndNamespace(string $locale, string $group, string $namespace): array
    {
        return Cache::rememberForever(static::getCacheKeyWithNamespace($group, $locale, $namespace), function () use ($group, $locale, $namespace) {
            if($namespace !== '*') {
                $group = $namespace.'::'.$group;
            }
            return static::query()
                    ->where('group', $group)
                    ->get()
                    ->reduce(function ($lines, LanguageLine $languageLine) use ($locale) {
                        array_set($lines, $languageLine->key, $languageLine->getTranslation($locale));

                        return $lines;
                    }) ?? [];
        });
    }

    public static function getCacheKeyWithNamespace(string $group, string $locale, string $namespace): string
    {
        return "spatie.laravel-translation-loader.{$namespace}.{$group}.{$locale}";
    }

}

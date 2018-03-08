<?php

namespace Brackets\AdminTranslations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class Translation extends Model
{
    use SoftDeletes;

    /** @var array */
    public $translatable = ['text'];

    /** @var array */
    public $guarded = ['id'];

    /** @var array */
    protected $casts = ['text' => 'array'];

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
        if($namespace == '' || is_null($namespace)) {
            $namespace = '*';
        }
        return Cache::rememberForever(static::getCacheKey($namespace, $group, $locale), function () use ($namespace, $group, $locale) {
            return static::query()
                    ->where('namespace', $namespace)
                    ->where('group', $group)
                    ->get()
                    ->reject(function(Translation $translation) use ($locale, $group) {
                        return empty($translation->getTranslation($locale, $group));
                    })
                    ->reduce(function ($translations, Translation $translation) use ($locale, $group) {
                        if($group === '*') {
                            $translations[$translation->key] = $translation->getTranslation($locale, $group);
                        } else {
                            array_set($translations, $translation->key, $translation->getTranslation($locale));
                        }

                        return $translations;
                    }) ?? [];
        });
    }

    public static function getCacheKey(string $namespace, string $group, string $locale): string
    {
        return "brackets.admin-translations.{$namespace}.{$group}.{$locale}";
    }

    /**
     * @param string $locale
     *
     * @return string
     */
    public function getTranslation(string $locale, string $group = null): string
    {
        if(! isset($this->text[$locale]) && $group === '*') {
            $fallback = config('app.fallback_locale');

            return $this->text[$fallback] ?? $this->key;
        }
        return $this->text[$locale] ?? '';
    }

    /**
     * @param string $locale
     * @param string $value
     *
     * @return $this
     */
    public function setTranslation(string $locale, string $value)
    {
        $this->text = array_merge($this->text ?? [], [$locale => $value]);

        return $this;
    }

    protected function flushGroupCache()
    {
        foreach ($this->getTranslatedLocales() as $locale) {
            Cache::forget(static::getCacheKey(!is_null($this->namespace) ? $this->namespace : '*', $this->group, $locale));
        }
    }

    protected function getTranslatedLocales(): array
    {
        return array_keys($this->text);
    }
}

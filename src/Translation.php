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
        return Cache::rememberForever(static::getCacheKey($group, $locale, $namespace), function () use ($group, $locale, $namespace) {
            if($namespace == '') {
                $namespace = '*';
            }
            return static::query()
                    ->where('group', $group)
                    ->where('namespace', $namespace)
                    ->get()
                    ->reduce(function ($translations, Translation $translation) use ($locale) {
                        array_set($translations, $translation->key, $translation->getTranslation($locale));

                        return $translations;
                    }) ?? [];
        });
    }

    public static function getCacheKey(string $group, string $locale, string $namespace): string
    {
        return "brackets.admin-translations.{$namespace}.{$group}.{$locale}";
    }

    /**
     * @param string $locale
     *
     * @return string
     */
    public function getTranslation(string $locale): string
    {
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
            Cache::forget(static::getCacheKey($this->group, $locale, !is_null($this->namespace) ? $this->namespace : ''));
        }
    }

    protected function getTranslatedLocales(): array
    {
        return array_keys($this->text);
    }
}

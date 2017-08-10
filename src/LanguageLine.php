<?php

namespace Brackets\AdminTranslations;

use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\TranslationLoader\LanguageLine as ParentClass;

class LanguageLine extends ParentClass
{
    use SoftDeletes;

    public static function boot()
    {
        static::bootTraits();

        static::saved(function (LanguageLine $languageLine) {
            $languageLine->flushGroupCache();
        });

        static::deleted(function (LanguageLine $languageLine) {
            $languageLine->flushGroupCache();
        });
    }
}

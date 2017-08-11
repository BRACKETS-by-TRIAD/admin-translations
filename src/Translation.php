<?php

namespace Brackets\AdminTranslations;

use Illuminate\Database\Eloquent\SoftDeletes;
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

}

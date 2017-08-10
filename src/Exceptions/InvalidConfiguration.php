<?php

namespace Brackets\AdminTranslations\Exceptions;

use Exception;
use Brackets\AdminTranslations\LanguageLine;

class InvalidConfiguration extends Exception
{
    public static function invalidModel(string $className): self
    {
        return new static("You have configured an invalid class `{$className}`.".
            'A valid class extends '.LanguageLine::class.'.');
    }
}

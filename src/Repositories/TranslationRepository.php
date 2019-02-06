<?php

namespace Brackets\AdminTranslations\Repositories;

use Brackets\AdminTranslations\Translation;

class TranslationRepository
{
    public function createOrUpdate($namespace, $group, $key, $language, $text)
    {
        /** @var Translation $translation */
        $translation = Translation::withTrashed()
            ->where('namespace', $namespace)
            ->where('group', $group)
            ->where('key', $key)
            ->first();

        $defaultLocale = config('app.locale');

        if ($translation) {
            if (!$this->isCurrentTransForTranslationArray($translation, $defaultLocale)) {
                $translation->restore();
            }
        } else {
            $translation = Translation::make([
                'namespace' => $namespace,
                'group' => $group,
                'key' => $key,
                'text' => [$language => $text],
            ]);

            if (!$this->isCurrentTransForTranslationArray($translation, $defaultLocale)) {
                $translation->save();
            }
        }
    }

    protected function isCurrentTransForTranslationArray(Translation $translation, $locale): bool
    {
        if ($translation->group == '*') {
            return is_array(__($translation->key, [], $locale));
        } elseif ($translation->namespace == '*') {
            return is_array(trans($translation->group . '.' . $translation->key, [], $locale));
        } else {
            return is_array(trans($translation->namespace . '::' . $translation->group . '.' . $translation->key, [], $locale));
        }
    }
}
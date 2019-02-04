<?php

namespace Brackets\AdminTranslations\Service\Import;
use Brackets\AdminTranslations\Translation;
use Illuminate\Support\Collection;

class TranslationService
{
    public function saveCollection(Collection $collection, $language)
    {
        $collection->each(function ($item) use ($language) {
            $this->createOrUpdate($item['namespace'], $item['group'], $item['default'], $language, $item[$language]);
        });
    }

    private function createOrUpdate($namespace, $group, $key, $language, $text)
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

    public function buildKeyForArray($row): string
    {
        return $row['namespace'] . '.' . $row['group'] . '.' . $row['default'];
    }

    public function rowExistsInArray($row, $array): bool
    {
        return array_key_exists($this->buildKeyForArray($row), $array);
    }

    public function rowValueEqualsValueInArray($row, $array, $request): bool
    {
        $chooseLanguage = strtolower($request->importLanguage);

        if (!empty($array[$this->buildKeyForArray($row)]['text'])) {
            if (isset($array[$this->buildKeyForArray($row)]['text'][$chooseLanguage])) {
                return $this->rowExistsInArray($row, $array) && strval($row[$chooseLanguage]) === strval($array[$this->buildKeyForArray($row)]['text'][$chooseLanguage]);
            } else {
                return false;
            }

        }
        return true;
    }

    public function isCurrentTransForTranslationArray(Translation $translation, $locale): bool
    {
        if ($translation->group == '*') {
            return is_array(__($translation->key, [], $locale));
        } elseif ($translation->namespace == '*') {
            return is_array(trans($translation->group . '.' . $translation->key, [], $locale));
        } else {
            return is_array(trans($translation->namespace . '::' . $translation->group . '.' . $translation->key, [], $locale));
        }
    }

    public function getAllTranslationsForGivenLang($chooseLanguage)
    {
        return Translation::all()->filter(function ($translation) use ($chooseLanguage) {
            if (isset($translation->text->{$chooseLanguage})) {
                return array_key_exists($chooseLanguage, $translation->text) && strlen(strval($translation->text->{$chooseLanguage}) > 0);
            }
            return true;
        })->keyBy(function ($translation) {
            return $translation->namespace . '.' . $translation->group . '.' . $translation->key;
        })->toArray();
    }

    public function checkAndUpdateTranslations($chooseLanguage, $existingTranslations, $collection)
    {
        $numberOfImportedTranslations = 0;
        $numberOfUpdatedTranslations = 0;

        $collection->map(function ($item) use ($chooseLanguage, $existingTranslations, &$numberOfUpdatedTranslations, &$numberOfImportedTranslations) {
            if (isset($existingTranslations[$this->buildKeyForArray($item)]['id'])) {
                $id = $existingTranslations[$this->buildKeyForArray($item)]['id'];
                $existringTraslationInDatabase = Translation::find($id);
                $textArray = $existringTraslationInDatabase->text;
                if (isset($textArray[$chooseLanguage])) {
                    if ($textArray[$chooseLanguage] != $item[$chooseLanguage]) {
                        $numberOfUpdatedTranslations++;
                        $textArray[$chooseLanguage] = $item[$chooseLanguage];
                        $existringTraslationInDatabase->update(['text' => $textArray]);
                    }
                } else {
                    $numberOfUpdatedTranslations++;
                    $textArray[$chooseLanguage] = $item[$chooseLanguage];
                    $existringTraslationInDatabase->update(['text' => $textArray]);
                }
            } else {
                $numberOfImportedTranslations++;
                $this->createOrUpdate($item['namespace'], $item['group'], $item['default'], $chooseLanguage, $item[$chooseLanguage]);
            }
        });

        return ['numberOfImportedTranslations' => $numberOfImportedTranslations, 'numberOfUpdatedTranslations' => $numberOfUpdatedTranslations];
    }

    public function getCollectionWithConflicts($collection, $request, $existingTranslations, $chooseLanguage)
    {
        return $collection->map(function ($row) use ($request, $existingTranslations, $chooseLanguage) {
            $row['has_conflict'] = false;
            if (!$this->rowValueEqualsValueInArray($row, $existingTranslations, $request)) {
                $row['has_conflict'] = true;
                if (isset($existingTranslations[$this->buildKeyForArray($row)])) {
                    if (isset($existingTranslations[$this->buildKeyForArray($row)]['text'][$chooseLanguage])) {
                        $row['current_value'] = strval($existingTranslations[$this->buildKeyForArray($row)]['text'][$chooseLanguage]);
                    } else {
                        $row['has_conflict'] = false;
                        $row['current_value'] = "";
                    }

                } else {
                    $row['current_value'] = "";
                    $row['has_conflict'] = false;
                }
            }
            return $row;
        });
    }

    public function getNumberOfConflicts($collection)
    {
        return $collection->filter(function ($row) {
            return $row['has_conflict'];
        })->count();
    }

    public function getFilteredExistingTranslations($collection, $existingTranslations)
    {
        return $collection->reject(function ($row) use ($existingTranslations) {
            // filter out rows representing translations existing in the database (treat deleted_at as non-existing)
            return $this->rowExistsInArray($row, $existingTranslations);
        });
    }
}
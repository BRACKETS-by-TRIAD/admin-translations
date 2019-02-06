<?php

namespace Brackets\AdminTranslations\Service\Import;

use Brackets\AdminTranslations\Translation;
use Illuminate\Support\Collection;
use Brackets\AdminTranslations\Imports\TranslationsImport;
use Brackets\AdminTranslations\Repositories\TranslationRepository;

class TranslationService
{
    protected $translationRepository;

    public function __construct(
        TranslationRepository $translationRepository
    )
    {
        $this->translationRepository = $translationRepository;
    }

    public function saveCollection(Collection $filteredCollection, $language)
    {
        $filteredCollection->each(function ($item) use ($language) {
            $this->translationRepository->createOrUpdate($item['namespace'], $item['group'], $item['default'], $language, $item[$language]);
        });
    }

    public function buildKeyForArray($row): string
    {
        return $row['namespace'] . '.' . $row['group'] . '.' . $row['default'];
    }

    public function rowExistsInArray($row, $array): bool
    {
        return array_key_exists($this->buildKeyForArray($row), $array);
    }

    public function rowValueEqualsValueInArray($row, $array, $choosenLanguage): bool
    {
        if (!empty($array[$this->buildKeyForArray($row)]['text'])) {
            if (isset($array[$this->buildKeyForArray($row)]['text'][$choosenLanguage])) {
                return $this->rowExistsInArray($row, $array) && strval($row[$choosenLanguage]) === strval($array[$this->buildKeyForArray($row)]['text'][$choosenLanguage]);
            } else {
                return false;
            }
        }
        return true;
    }

    public function getAllTranslationsForGivenLang($choosenLanguage)
    {
        return Translation::all()->filter(function ($translation) use ($choosenLanguage) {
            if (isset($translation->text->{$choosenLanguage})) {
                return array_key_exists($choosenLanguage, $translation->text) && strlen(strval($translation->text->{$choosenLanguage}) > 0);
            }
            return true;
        })->keyBy(function ($translation) {
            return $translation->namespace . '.' . $translation->group . '.' . $translation->key;
        })->toArray();
    }

    public function checkAndUpdateTranslations($choosenLanguage, $existingTranslations, $collectionToUpdate)
    {
        $numberOfImportedTranslations = 0;
        $numberOfUpdatedTranslations = 0;

        $collectionToUpdate->map(function ($item) use ($choosenLanguage, $existingTranslations, &$numberOfUpdatedTranslations, &$numberOfImportedTranslations) {
            if (isset($existingTranslations[$this->buildKeyForArray($item)]['id'])) {
                $id = $existingTranslations[$this->buildKeyForArray($item)]['id'];
                $existringTraslationInDatabase = Translation::find($id);
                $textArray = $existringTraslationInDatabase->text;
                if (isset($textArray[$choosenLanguage])) {
                    if ($textArray[$choosenLanguage] != $item[$choosenLanguage]) {
                        $numberOfUpdatedTranslations++;
                        $textArray[$choosenLanguage] = $item[$choosenLanguage];
                        $existringTraslationInDatabase->update(['text' => $textArray]);
                    }
                } else {
                    $numberOfUpdatedTranslations++;
                    $textArray[$choosenLanguage] = $item[$choosenLanguage];
                    $existringTraslationInDatabase->update(['text' => $textArray]);
                }
            } else {
                $numberOfImportedTranslations++;
                $this->translationRepository->createOrUpdate($item['namespace'], $item['group'], $item['default'], $choosenLanguage, $item[$choosenLanguage]);
            }
        });

        return ['numberOfImportedTranslations' => $numberOfImportedTranslations, 'numberOfUpdatedTranslations' => $numberOfUpdatedTranslations];
    }

    public function getCollectionWithConflicts($collectionFromImportedFile, $existingTranslations, $choosenLanguage)
    {
        return $collectionFromImportedFile->map(function ($row) use ($existingTranslations, $choosenLanguage) {
            $row['has_conflict'] = false;
            if (!$this->rowValueEqualsValueInArray($row, $existingTranslations, $choosenLanguage)) {
                $row['has_conflict'] = true;
                if (isset($existingTranslations[$this->buildKeyForArray($row)])) {
                    if (isset($existingTranslations[$this->buildKeyForArray($row)]['text'][$choosenLanguage])) {
                        $row['current_value'] = strval($existingTranslations[$this->buildKeyForArray($row)]['text'][$choosenLanguage]);
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

    public function getNumberOfConflicts($collectionWithConflicts)
    {
        return $collectionWithConflicts->filter(function ($row) {
            return $row['has_conflict'];
        })->count();
    }

    public function getFilteredExistingTranslations($collectionFromImportedFile, $existingTranslations)
    {
        return $collectionFromImportedFile->reject(function ($row) use ($existingTranslations) {
            // filter out rows representing translations existing in the database (treat deleted_at as non-existing)
            return $this->rowExistsInArray($row, $existingTranslations);
        });
    }

    public function validImportFile($collectionToImport, $choosenLanguage)
    {
        $requiredHeaders = ['namespace', 'group', 'default', $choosenLanguage];

        foreach ($requiredHeaders as $item) {
            if (!isset($collectionToImport->first()[$item])) return false;
        }

        return true;
    }

    public function getCollectionFromImportedFile($file, $choosenLanguage)
    {
        if ($file->getClientOriginalExtension() != "xlsx"){
            abort(409,"Unsupported file type");
        }

        try {
            $collectionFromImportedFile = (new TranslationsImport())->toCollection($file)->first();
        } catch (\Exception $e) {
            abort(409,"Unsupported file type");
        }

        if(!$this->validImportFile($collectionFromImportedFile, $choosenLanguage)){
            abort(409,"Wrong syntax in your import");
        }

        return $collectionFromImportedFile;
    }
}
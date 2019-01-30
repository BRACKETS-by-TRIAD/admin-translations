<?php

namespace Brackets\AdminTranslations\Http\Controllers\Admin;

use Brackets\AdminGenerator\Generate\UpdateRequest;
use Brackets\AdminTranslations\Http\Requests\Admin\Translation\UpdateTranslation;
use Brackets\AdminTranslations\Http\Responses\TranslationsAdminListingResponse;
use Brackets\AdminTranslations\Translation;
use Brackets\AdminTranslations\Exports\TranslationsExport;
use Brackets\AdminTranslations\Imports\TranslationsImport;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Database\Eloquent\Builder;
use Brackets\AdminTranslations\Http\Requests\Admin\Translation\IndexTranslation;
use Brackets\AdminTranslations\Http\Requests\Admin\Translation\ImportTranslation;
use Illuminate\Http\Response;
use Brackets\AdminListing\AdminListing;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;

class TranslationsController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * Display a listing of the resource.
     *
     * @param  IndexTranslation $request
     * @return Responsable
     */
    public function index(IndexTranslation $request)
    {

        // create and AdminListing instance for a specific model and
        $data = AdminListing::create(Translation::class)->processRequestAndGet(
        // pass the request with params
            $request,

            // set columns to query
            ['id', 'namespace', 'group', 'key', 'text', 'created_at', 'updated_at'],

            // set columns to searchIn
            ['group', 'key', 'text->en', 'text->sk'],

            function(Builder $query) use ($request) {
                if ($request->has('group')) {
                    $query->whereGroup($request->group);
                }
            }
        );

        return new TranslationsAdminListingResponse($data);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  UpdateTranslation $request
     * @param  Translation $translation
     * @return Response|array
     */
    public function update(UpdateTranslation $request, Translation $translation)
    {
        $translation->update($request->validated());

        if ($request->ajax()) {
            return [];
        }

        return redirect('admin/translation');
    }

    public function export(UpdateTranslation $request)
    {
        return Excel::download(new TranslationsExport($request), 'translations.xlsx');
    }

    public function import(ImportTranslation $request) // FIXME create separate request class
    {
        if ($request->hasFile('fileImport')){
            // FIXME extract all this code with helper private methods
            $chooseLanguage = strtolower($request->importLanguage);
            // let's grab only the first sheet
            $collection = (new TranslationsImport())->toCollection(request()->file('fileImport'))->first();

            $existingTranslations = Translation::all()->filter(function($translation ) use ($chooseLanguage){
                if(isset($translation->text->{$chooseLanguage})){
                    return array_key_exists($chooseLanguage, $translation->text) && strlen(strval($translation->text->{$chooseLanguage}) > 0);
                }
                return true;
            })->keyBy(function($translation){
                return $translation->namespace . '.' . $translation->group . '.' . $translation->key;
            })->toArray();
            
            if ($request->input('onlyMissing') === 'true') {
                $filteredCollection = $collection->reject(function($row) use ($existingTranslations) {
                    // filter out rows representing translations existing in the database (treat deleted_at as non-existing)
                    return $this->rowExistsInArray($row, $existingTranslations);
                });

                $this->saveCollection($filteredCollection, $chooseLanguage);
                // return success
                return ['numberOfImportedTranslations' => count($filteredCollection), 'numberOfUpdatedTranslations' => 0];
            } else {
                $collection = $collection->map(function ($row) use ($request, $existingTranslations, $chooseLanguage) {
                    if(!$this->rowValueEqualsValueInArray($row, $existingTranslations, $request)){
                        $row['has_conflict'] = true;
                        if(isset($existingTranslations[$this->buildKeyForArray($row)])){
                            $row['current_value'] = strval($existingTranslations[$this->buildKeyForArray($row)]['text'][$chooseLanguage]);
                        } else {
                            $row['current_value'] = "";
                        }
                        return $row;
                    }
                    $row['has_conflict'] = false;
                    return $row;
                });

                $conflicts = $collection->filter(function($row){
                    return $row['has_conflict'];
                })->count();

                if($conflicts == 0){
                    $numberOfUpdatedTranslations = 0;
                    $numberOfImportedTranslations = 0;

                    $collection->map(function($item) use ($chooseLanguage, $existingTranslations, &$numberOfImportedTranslations, &$numberOfUpdatedTranslations){
                        if(isset($existingTranslations[$this->buildKeyForArray($item)]['id'])){
                            $id = $existingTranslations[$this->buildKeyForArray($item)]['id'];
                            $t = Translation::find($id);
                            $textArray = $t->text;
                            if($textArray[$chooseLanguage] != $item[$chooseLanguage]){
                                $numberOfUpdatedTranslations++;
                                $textArray[$chooseLanguage] = $item[$chooseLanguage];
                                $t->update(['text' => $textArray]);
                            }
                        } else {
                            $numberOfImportedTranslations++;
                            $this->createOrUpdate($item['namespace'], $item['group'], $item['default'], $chooseLanguage, $item[$chooseLanguage]);
                        }
                    });

                    return ['numberOfImportedTranslations' => $numberOfImportedTranslations, 'numberOfUpdatedTranslations' => $numberOfUpdatedTranslations];
                } else {
                    // let user decide how to resolve conflicts
                    return $collection;
                }
            }

        }
    }

    private function buildKeyForArray($row)
    {
        return $row['namespace'] . '.' . $row['group'] . '.' . $row['default'];
    }

    private function rowExistsInArray($row, $array)
    {
        return array_key_exists($this->buildKeyForArray($row), $array);
    }

    private function rowValueEqualsValueInArray($row, $array, $request)
    {
        $chooseLanguage = strtolower($request->importLanguage);

        if(!empty($array[$this->buildKeyForArray($row)]['text'])){
            return $this->rowExistsInArray($row, $array) && strval($row[$chooseLanguage]) === strval($array[$this->buildKeyForArray($row)]['text'][$chooseLanguage]);
        }
        return true;
    }

    public function importResolvedConflicts(UpdateTranslation $request)
    {
        $collection = collect($request->input('resolved_translations'));
        $chooseLanguage = strtolower($request->importLanguage);
        $numberOfImportedTranslations = 0;
        $numberOfUpdatedTranslations = 0;
        $valid = true;

        $existingTranslations = Translation::all()->filter(function($translation ) use ($chooseLanguage){
            if(isset($translation->text->{$chooseLanguage})){
                return array_key_exists($chooseLanguage, $translation->text) && strlen(strval($translation->text->{$chooseLanguage}) > 0);
            }
            return true;
        })->keyBy(function($translation){
            return $translation->namespace . '.' . $translation->group . '.' . $translation->key;
        })->toArray();

        $collection->map(function($item) use ($chooseLanguage, $existingTranslations, &$numberOfUpdatedTranslations, &$numberOfImportedTranslations){
            if (!(array_key_exists('namespace', $item) && array_key_exists('group', $item)
                && array_key_exists('default', $item) && array_key_exists($chooseLanguage, $item))){
                $valid = false;
            } else {
                if(isset($existingTranslations[$this->buildKeyForArray($item)]['id'])){
                    $id = $existingTranslations[$this->buildKeyForArray($item)]['id'];
                    $t = Translation::find($id);
                    $textArray = $t->text;
                    if($textArray[$chooseLanguage] != $item[$chooseLanguage]){
                        $numberOfUpdatedTranslations++;
                        $textArray[$chooseLanguage] = $item[$chooseLanguage];
                        $t->update(['text' => $textArray]);
                    }
                } else {
                    $numberOfImportedTranslations++;
                    $this->createOrUpdate($item['namespace'], $item['group'], $item['default'], $chooseLanguage, $item[$chooseLanguage]);
                }
            }
        });

        if(!$valid){
            return response()->json("Validation error", 422);
        }

        return ['numberOfImportedTranslations' => $numberOfImportedTranslations, 'numberOfUpdatedTranslations' => $numberOfUpdatedTranslations];;
    }

    protected function saveCollection(Collection $collection, $language)
    {
        $collection->each(function($item) use($language){
            $this->createOrUpdate($item['namespace'], $item['group'], $item['default'], $language, $item[$language]);
        });
    }

    protected function createOrUpdate($namespace, $group, $key, $language, $text) {
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

    private function isCurrentTransForTranslationArray(Translation $translation, $locale) {
        if ($translation->group == '*') {
            return is_array(__($translation->key, [], $locale));
        } elseif ($translation->namespace == '*') {
            return is_array(trans($translation->group.'.'.$translation->key, [], $locale));
        } else {
            return is_array(trans($translation->namespace . '::' . $translation->group . '.' . $translation->key, [], $locale));
        }
    }


}

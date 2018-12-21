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


use Illuminate\Support\Facades\Log;


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

    public function import(UpdateTranslation $request) // FIXME create separate request class
    {
        if ($request->hasFile('fileImport')){


            // FIXME extract all this code with helper private methods
            $chooseLanguage = strtolower($request->importLanguage);

            // let's grab only the first sheet
            $collection = (new TranslationsImport())->toCollection(request()->file('fileImport'))->first();

            $existingTranslations = Translation::all()->filter(function($translation , $chooseLanguage){
                return array_key_exists($chooseLanguage, $translation->text) && strlen(strval($translation->text->{$chooseLanguage}) > 0);
            })->keyBy(function($translation){
                return $translation->namespace . ' . ' . $translation->group . ' . ' . $translation->key;
            })->toArray();

            if ($request->input('onlyMissing')) {
                $this->saveCollection($collection->reject(function($row) use ($existingTranslations) {
                    // filter out rows representing translations existing in the database (treat deleted_at as non-existing)
                    return $this->rowExistsInArray($row, $existingTranslations);
                }));

                // return success
                return [];

            } else {
                $collection = $collection->map(function ($row) use ($request, $existingTranslations) {
                    if(!$this->rowValueEqualsValueInArray($row, $existingTranslations)){
                        $row['has_conflict'] = true;
                        return $row;
                    }
                });

                $conflicts = $conflicts->filter(function($row){
                    return $row['has_conflict'];
                })->count();

                if($conflicts == 0){
                    $this->saveCollection($collection);
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

    private function rowValueEqualsValueInArray($row, $array)
    {
        $chooseLanguage = strtolower($request->importLanguage);
        return $this->rowExistsInArray($row, $array) && strval($row[$chooseLanguage]) === strval($array[$this->buildKeyForArray($row)]);
    }

    public function importResolvedConflicts(UpdateTranslation $request) // FIXME create separate request class
    {
        $collection = $request->input('resolved_translations');

        // FIXME map collection and make sure it has correct structure (validation)

        $this->saveCollection($collection);
    }

    protected function saveCollection(Collection $collection)
    {

        $collection->each(function($item){
            $this->createOrUpdate($item['namespace'], $item['group'], $item['default']);
        });
    }

    protected function createOrUpdate($namespace, $group, $key) {
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
                'text' => [],
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

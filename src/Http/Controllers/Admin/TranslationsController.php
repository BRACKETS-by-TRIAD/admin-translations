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
use Carbon\Carbon;

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

    /**
     * @param UpdateTranslation $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function export(UpdateTranslation $request)
    {
        $currentTime = Carbon::now()->toDateTimeString();
        $nameOfExportedFile = 'translations' . $currentTime . '.xlsx';
        return Excel::download(new TranslationsExport($request), $nameOfExportedFile);
    }


    /**
     * @param ImportTranslation $request
     * @return array|\Illuminate\Http\JsonResponse|mixed
     */
    public function import(ImportTranslation $request) // FIXME create separate request class
    {
        if ($request->hasFile('fileImport')){
            // FIXME extract all this code with helper private methods
            $chooseLanguage = strtolower($request->importLanguage);
            // let's grab only the first sheet

            if($request->file('fileImport')->getClientOriginalExtension() != "xlsx"){
                return response()->json("Unsupported file type", 409);
            }

            try{
                $collection = (new TranslationsImport())->toCollection($request->file('fileImport'))->first();
            } catch(\Exception $e) {
                return response()->json("Unsupported file type", 409);
            }

            $requiredHeaders = ['namespace', 'group', 'default', $chooseLanguage];

            foreach ($requiredHeaders as $item){
                if(!isset($collection->first()[$item])) return response()->json("Wrong syntax in your import" ,409);
            }

            $existingTranslations = $this->getAllTranslationsForGivenLang($chooseLanguage);

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
                    $row['has_conflict'] = false;
                    if(!$this->rowValueEqualsValueInArray($row, $existingTranslations, $request)){
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

                $conflicts = $collection->filter(function($row){
                    return $row['has_conflict'];
                })->count();

                if($conflicts == 0){
                    return $this->checkAndUpdateTranslations($chooseLanguage, $existingTranslations, $collection);
                } else {
                    // let user decide how to resolve conflicts
                    return $collection;
                }
            }

        }
        return response()->json("No file imported", 409);
    }

    private function buildKeyForArray($row): string
    {
        return $row['namespace'] . '.' . $row['group'] . '.' . $row['default'];
    }

    private function rowExistsInArray($row, $array): bool
    {
        return array_key_exists($this->buildKeyForArray($row), $array);
    }

    private function rowValueEqualsValueInArray($row, $array, $request): bool
    {
        $chooseLanguage = strtolower($request->importLanguage);

        if(!empty($array[$this->buildKeyForArray($row)]['text'])){
            if(isset($array[$this->buildKeyForArray($row)]['text'][$chooseLanguage])){
                return $this->rowExistsInArray($row, $array) && strval($row[$chooseLanguage]) === strval($array[$this->buildKeyForArray($row)]['text'][$chooseLanguage]);
            } else {
                return false;
            }

        }
        return true;
    }

    /**
     * @param UpdateTranslation $request
     * @return array|\Illuminate\Http\JsonResponse
     */
    public function importResolvedConflicts(UpdateTranslation $request)
    {
        $resolvedConflicts = collect($request->input('resolvedTranslations'));
        $chooseLanguage = strtolower($request->importLanguage);
        $existingTranslations = $this->getAllTranslationsForGivenLang($chooseLanguage);

        $requiredHeaders = ['namespace', 'group', 'default', $chooseLanguage];

        foreach ($requiredHeaders as $item){
            if(!isset($resolvedConflicts->first()[$item])) return response()->json("Wrong syntax in your import");
        }

        return $this->checkAndUpdateTranslations($chooseLanguage, $existingTranslations, $resolvedConflicts);
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

    private function isCurrentTransForTranslationArray(Translation $translation, $locale): bool {
        if ($translation->group == '*') {
            return is_array(__($translation->key, [], $locale));
        } elseif ($translation->namespace == '*') {
            return is_array(trans($translation->group.'.'.$translation->key, [], $locale));
        } else {
            return is_array(trans($translation->namespace . '::' . $translation->group . '.' . $translation->key, [], $locale));
        }
    }

    private function getAllTranslationsForGivenLang($chooseLanguage){
        return Translation::all()->filter(function($translation ) use ($chooseLanguage){
            if(isset($translation->text->{$chooseLanguage})){
                return array_key_exists($chooseLanguage, $translation->text) && strlen(strval($translation->text->{$chooseLanguage}) > 0);
            }
            return true;
        })->keyBy(function($translation){
            return $translation->namespace . '.' . $translation->group . '.' . $translation->key;
        })->toArray();
    }

    private function checkAndUpdateTranslations($chooseLanguage, $existingTranslations, $collection){

        $numberOfImportedTranslations = 0;
        $numberOfUpdatedTranslations = 0;

        $collection->map(function($item) use ($chooseLanguage, $existingTranslations, &$numberOfUpdatedTranslations, &$numberOfImportedTranslations){
            if(isset($existingTranslations[$this->buildKeyForArray($item)]['id'])){
                $id = $existingTranslations[$this->buildKeyForArray($item)]['id'];
                $existringTraslationInDatabase = Translation::find($id);
                $textArray = $existringTraslationInDatabase->text;
                if(isset($textArray[$chooseLanguage])){
                    if($textArray[$chooseLanguage]!= $item[$chooseLanguage]){
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

        return ['numberOfImportedTranslations' => $numberOfImportedTranslations,'numberOfUpdatedTranslations' => $numberOfUpdatedTranslations];
    }

}

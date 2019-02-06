<?php

namespace Brackets\AdminTranslations\Http\Controllers\Admin;

use Brackets\AdminGenerator\Generate\UpdateRequest;
use Brackets\AdminTranslations\Http\Requests\Admin\Translation\UpdateTranslation;
use Brackets\AdminTranslations\Http\Responses\TranslationsAdminListingResponse;
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
use Brackets\AdminTranslations\Service\Import\TranslationService;
use Brackets\AdminTranslations\Translation;

class TranslationsController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected $translationService;

    public function __construct(
        TranslationService $translationService
    )
    {
        $this->translationService = $translationService;
    }

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

            function (Builder $query) use ($request) {
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
    public function import(ImportTranslation $request)
    {
        if ($request->hasFile('fileImport')) {
            $choosenLanguage = $request->getChoosenLanguage();

            try {
                $collectionFromImportedFile = $this->translationService->getCollectionFromImportedFile($request->file('fileImport'), $choosenLanguage);
            } catch (\Exception $e) {
                return response()->json($e->getMessage(), 409);
            }

            $existingTranslations = $this->translationService->getAllTranslationsForGivenLang($choosenLanguage);

            if ($request->input('onlyMissing') === 'true') {
                $filteredCollection = $this->translationService->getFilteredExistingTranslations($collectionFromImportedFile, $existingTranslations);
                $this->translationService->saveCollection($filteredCollection, $choosenLanguage);

                return ['numberOfImportedTranslations' => count($filteredCollection), 'numberOfUpdatedTranslations' => 0];
            } else {
                $collectionWithConflicts = $this->translationService->getCollectionWithConflicts($collectionFromImportedFile, $existingTranslations, $choosenLanguage);
                $numberOfConflicts = $this->translationService->getNumberOfConflicts($collectionWithConflicts);

                if ($numberOfConflicts == 0){
                    return $this->translationService->checkAndUpdateTranslations($choosenLanguage, $existingTranslations, $collectionWithConflicts);
                }

                return $collectionWithConflicts;
            }
        }
        return response()->json("No file imported", 409);
    }

    /**
     * @param UpdateTranslation $request
     * @return array|\Illuminate\Http\JsonResponse
     */
    public function importResolvedConflicts(UpdateTranslation $request)
    {
        $resolvedConflicts = collect($request->getResolvedConflicts());
        $choosenLanguage = $request->getChoosenLanguage();
        $existingTranslations = $this->translationService->getAllTranslationsForGivenLang($choosenLanguage);

        if(!$this->translationService->validImportFile($resolvedConflicts, $choosenLanguage)){
            return response()->json("Wrong syntax in your import", 409);
        }

        return $this->translationService->checkAndUpdateTranslations($choosenLanguage, $existingTranslations, $resolvedConflicts);
    }
}

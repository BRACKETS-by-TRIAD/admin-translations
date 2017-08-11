<?php

namespace Brackets\AdminTranslations\Http\Controllers\Admin;

use Brackets\AdminTranslations\Http\Requests\Admin\LanguageLine\UpdateLanguageLine;
use Brackets\AdminTranslations\Translation;
use Illuminate\Database\Eloquent\Builder;
use Brackets\AdminTranslations\Http\Requests\Admin\LanguageLine\IndexLanguageLine;
use Illuminate\Http\Response;
use Brackets\Admin\AdminListing;

// FIXME what do we do with this?
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class TranslationsController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * Display a listing of the resource.
     *
     * @param  IndexLanguageLine $request
     * @return Response|array
     */
    public function index(IndexLanguageLine $request)
    {

        // create and AdminListing instance for a specific model and
        $data = AdminListing::instance(Translation::class)->processRequestAndGet(
        // pass the request with params
            $request,

            // set columns to query
            ['id', 'group', 'key', 'text', 'created_at', 'updated_at'],

            // set columns to searchIn
            ['group', 'key', 'text->en', 'text->sk'],

            function(Builder $query) use ($request) {
                if ($request->has('group')) {
                    $query->whereGroup($request->group);
                }
            }
        );

        if ($request->ajax()) {
            return ['data' => $data];
        }

        return $data;

//        return view('admin.translations.index', ['data' => $data]);

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  UpdateLanguageLine $request
     * @param  Translation $languageLine
     * @return Response|array
     */
    public function update(UpdateLanguageLine $request, Translation $languageLine)
    {
        $languageLine->update($request->only('text'));

        if ($request->ajax()) {
            return [];
        }

        return redirect('admin/translations');
    }

}

<?php

namespace Brackets\AdminTranslations\Http\Controllers\Admin;

use Brackets\AdminTranslations\Http\Requests\Admin\Translation\UpdateTranslation;
use Brackets\AdminTranslations\Translation;
use Illuminate\Database\Eloquent\Builder;
use Brackets\AdminTranslations\Http\Requests\Admin\Translation\IndexTranslation;
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
     * @param  IndexTranslation $request
     * @return Response|array
     */
    public function index(IndexTranslation $request)
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

        return view('brackets/admin-translations::admin.translation.index', ['data' => $data]);

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
        $translation->update($request->only('text'));

        if ($request->ajax()) {
            return [];
        }

        return redirect('admin/translations');
    }

}

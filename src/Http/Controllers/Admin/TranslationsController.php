<?php

namespace Brackets\AdminTranslations\Http\Controllers\Admin;

use Brackets\AdminTranslations\Http\Requests\Admin\Translation\UpdateTranslation;
use Brackets\AdminTranslations\Translation;
use Illuminate\Database\Eloquent\Builder;
use Brackets\AdminTranslations\Http\Requests\Admin\Translation\IndexTranslation;
use Illuminate\Http\Response;
use Brackets\Admin\AdminListing;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Config;

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
        $data = AdminListing::create(Translation::class)->processRequestAndGet(
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

        // TODO ked sa PPE a DBH dohodnu, tak sa toto bude tahat z view composera
        $locales = collect((array) Config::get('translatable.locales'))->map(function($val, $key){
            return is_array($val) ? $key : $val;
        });

        /* FIXME how to fix this:
            PPE thought that if Translation for specific key exists but not for a specific locale,
            but it exists in resources/lang source files, it will fallback to the srouce file, but it does not work like that.
            It fallbacks only if the Translation model does not exists. And that makes me sad :(

            So following code does not work as expected
        */
//        $data->getCollection()->map(function($translation) use ($locales) {
//            $locales->each(function($locale) use ($translation) {
//                /** @var Translation $translation */
//                $translation->setTranslation($locale, trans($translation->key, [], $locale));
//            });
//
//            return $translation;
//        });

        if ($request->ajax()) {
            return ['data' => $data, 'locales' => $locales];
        }

        return view('brackets/admin-translations::admin.translation.index', [
            'data' => $data,
            'locales' => $locales,
            'groups' => $this->getUsedGroups(), // FIXME move to custom Responsable object when Laravel 5.5 is out
        ]);

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

        return redirect('admin/translation');
    }

    private function getUsedGroups() {
        return \DB::table('translations')
            ->whereNull('deleted_at')
            ->groupBy('group')
            ->pluck('group');
    }

}

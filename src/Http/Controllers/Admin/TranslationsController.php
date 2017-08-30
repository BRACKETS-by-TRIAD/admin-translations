<?php

namespace Brackets\AdminTranslations\Http\Controllers\Admin;

use Brackets\AdminTranslations\Http\Requests\Admin\Translation\UpdateTranslation;
use Brackets\AdminTranslations\Translation;
use Brackets\Translatable\Facades\Translatable;
use Illuminate\Database\Eloquent\Builder;
use Brackets\AdminTranslations\Http\Requests\Admin\Translation\IndexTranslation;
use Illuminate\Http\Response;
use Brackets\AdminListing\AdminListing;
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

        $locales = Translatable::getLocales();

        $data->getCollection()->map(function($translation) use ($locales) {
            $locales->each(function($locale) use ($translation) {
                /** @var Translation $translation */
                $translation->setTranslation($locale, $this->getCurrentTransForTranslation($translation, $locale));
            });

            return $translation;
        });

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
        $translation->update($request->validated());

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

    public function getCurrentTransForTranslation(Translation $translation, $locale) {
        if ($translation->group == '*') {
            return __($translation->key, [], $locale);
        } elseif ($translation->namespace == '*') {
            return trans($translation->group.'.'.$translation->key, [], $locale);
        } else {
            return trans($translation->namespace . '::' . $translation->group . '.' . $translation->key, [], $locale);
        }
    }
}

<?php

namespace Brackets\AdminTranslations\Http\Responses;

use Brackets\AdminTranslations\Translation;
use Illuminate\Contracts\Support\Responsable;
use Brackets\Translatable\Facades\Translatable;
use Illuminate\Pagination\LengthAwarePaginator;

class TranslationsAdminListingResponse implements Responsable
{
    /**
     * @var LengthAwarePaginator
     */
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function toResponse($request)
    {
        $locales = Translatable::getLocales();

        $this->data->getCollection()->map(function($translation) use ($locales) {
            $locales->each(function($locale) use ($translation) {
                /** @var Translation $translation */
                $translation->setTranslation($locale, $this->getCurrentTransForTranslation($translation, $locale));
            });

            return $translation;
        });

        if ($request->ajax()) {
            return ['data' => $this->data, 'locales' => $locales];
        }

        return view('brackets/admin-translations::admin.translation.index', [
            'data' => $this->data,
            'locales' => $locales,
            'groups' => $this->getUsedGroups(),
        ]);
    }

    private function getCurrentTransForTranslation(Translation $translation, $locale) {
        if ($translation->group == '*') {
            return __($translation->key, [], $locale);
        } elseif ($translation->namespace == '*') {
            return trans($translation->group.'.'.$translation->key, [], $locale);
        } else {
            return trans($translation->namespace . '::' . $translation->group . '.' . $translation->key, [], $locale);
        }
    }

    private function getUsedGroups() {
        return \DB::table('translations')
            ->whereNull('deleted_at')
            ->groupBy('group')
            ->pluck('group');
    }

}
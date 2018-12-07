<?php namespace Brackets\AdminTranslations\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Brackets\AdminTranslations\Translation;
use Illuminate\Support\Facades\Auth;
use Brackets\Translatable\Facades\Translatable;

class TranslationsExport implements FromCollection, WithMapping, WithHeadings
{

    private $exportLanguage;

    public function __construct($request)
    {
        $this->exportLanguage = $request->exportLanguage;
        $this->templateLanguage = $request->templateLanguage;
    }
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return Translation::all();
    }

    public function headings(): array
    {

        return [
            trans('brackets/admin-translations::admin.fields.group'),
            trans('brackets/admin-translations::admin.fields.default'),
            mb_strtoupper($this->exportLanguage),
            mb_strtoupper($this->templateLanguage),
        ];
    }

    /**
     * @param Translation $translation
     * @return array
     *
     */

    public function map($translation): array
    {
        $array = [
            $translation->group,
            $translation->key,
            trans($translation->group.'.'.$translation->key, [], $this->exportLanguage),
            trans($translation->group.'.'.$translation->key, [], $this->templateLanguage),
        ];

        return $array;
    }
}
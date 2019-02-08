<?php

namespace Brackets\AdminTranslations\Http\Requests\Admin\Translation;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class ImportTranslation extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return  bool
     */
    public function authorize()
    {
        return Gate::allows('admin.translation.edit');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return  array
     */
    public function rules()
    {
        return [
            'importLanguage' => 'string|required',
            'onlyMissing' => 'string',
            'fileImport' => 'required|file',
        ];
    }

    public function getChoosenLanguage()
    {
        return strtolower($this->importLanguage);
    }
}

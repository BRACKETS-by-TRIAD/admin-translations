<?php namespace Brackets\AdminTranslations\Http\Requests\Admin\Translation;

use Brackets\Translatable\TranslatableFormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateTranslation extends TranslatableFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return  bool
     */
    public function authorize()
    {
        return Gate::allows('admin.translation.edit', [$this->translation]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return  array
     */
    public function translatableRules($locale)
    {
        return [
            'text' => 'string|nullable',
        ];
    }
}

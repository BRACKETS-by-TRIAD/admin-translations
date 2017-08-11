<?php namespace Brackets\AdminTranslations\Http\Requests\Admin\Translation;

use Illuminate\Foundation\Http\FormRequest;
use Gate;

class IndexTranslation extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return  bool
     */
    public function authorize()
    {
        return Gate::allows('admin.translation.index');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return  array
     */
    public function rules()
    {
        return [
            'group' => 'string|nullable',
        ];
    }
}

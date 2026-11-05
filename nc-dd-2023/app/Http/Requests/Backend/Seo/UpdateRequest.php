<?php declare(strict_types=1);

namespace App\Http\Requests\Backend\Seo;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'title' => 'required',
            'description' => 'required',
            'keywords' => 'required',
        ];
    }

    /**
     * Messages for validation rules.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Навзание не заполнено',
            'description.required' => 'Описание не заполнено',
            'keywords.required' => 'Необходимо указать ключевые слова',
        ];
    }
}

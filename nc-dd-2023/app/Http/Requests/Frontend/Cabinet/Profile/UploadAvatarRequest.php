<?php declare(strict_types=1);

namespace App\Http\Requests\Frontend\Cabinet\Profile;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Валидатор запроса на загрузку аватара пользователя
 */
class UploadAvatarRequest extends FormRequest
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
            'file' => 'required|file|mimes:jpg,jpeg,png|max:1024'
        ];
    }

    /**
     * Сообщения об ошибках в валидации
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'file.required' => 'Файл не выбран',
            'file.max'      => 'Размер файла не должен превышать 1 МБ',
            'file.mimes'    => 'Формат файла должен быть: jpg, jpeg, png',
        ];
    }
}

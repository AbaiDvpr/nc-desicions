<?php declare(strict_types=1);

namespace App\Http\Requests\Frontend\Registration;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RestorePasswordRequest extends FormRequest
{
    /**
     * Определите, авторизован ли пользователь для выполнения этого запроса.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Возвращает правила проверки, применимые к запросу.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'password'              => [
                'required',
                Password::min(6)->mixedCase()->symbols(),
                'confirmed'
            ],
            'password_confirmation' => 'required',
        ];
    }

    /**
     * Сообщения для правил проверки
     *
     * @return array
     */
    public function messages(): array
    {
        return trans('validation.registration');
    }
}

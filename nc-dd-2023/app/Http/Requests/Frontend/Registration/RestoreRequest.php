<?php declare(strict_types=1);

namespace App\Http\Requests\Frontend\Registration;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Валидатор запроса на восстановление пароля
 */
class RestoreRequest extends FormRequest
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
            'email' => [
                'required',
                'email:rfc,dns',
                'exists:users,email',
            ],
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

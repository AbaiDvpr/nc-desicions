<?php declare(strict_types=1);

namespace App\Http\Requests\Frontend\Cabinet\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

/**
 * Валидатор запроса на сохранение профиля пользователя
 */
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
        $year = Carbon::now()->subYears(18)->format('Y-m-d');

        return [
            'name'                  => 'required|min:2',
            'email'                 => 'required|email|unique:users,email,' . auth()->id(),
            'mobile'                => 'required|regex:"^(\+\d{1}) \(\d{3}\) (\d{3})-(\d{2})-(\d{2})$"',
            'birthday'              => sprintf('required|before:%s', $year),
            'password'              => 'sometimes|nullable|min:6|confirmed',
            'password_confirmation' => 'sometimes|nullable',
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
            'name.required'                  => 'ФИО не указано',
            'name.min'                       => 'ФИО не должно быть менее :min символов',
            'email.required'                 => 'E-mail не введен',
            'email.email'                    => 'E-mail введен не верно',
            'email.unique'                   => 'E-mail уже зарегистрирован',
            'mobile.required'                => 'Номер телефона не указан',
            'mobile.regex'                   => 'Номер телефона указан не верно',
            'birthday.required'              => 'Дата рождения обязательна к заполнению',
            'birthday.before'                => 'Вам еще не исполнилось 18 лет',
            'password.required'              => 'Пароль не указан',
            'password.min'                   => 'Пароль должен быть не менее :min символов',
            'password.confirmed'             => 'Пароли не совпадают',
            'password_confirmation.required' => 'Подтвердите пароль',
        ];
    }
}

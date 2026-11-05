<?php declare(strict_types=1);

namespace App\Http\Requests\Backend\Users;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
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
            'name' => 'required|min:2',
            'admin' => 'required|boolean',
            'email' => 'required|email|unique:users,email',
            'mobile' => '',
            'roles' => 'required',
            'password' => 'required|min:6|confirmed',
            'password_confirmation' => 'required',
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
            'name.required' => 'ФИО не указано',
            'name.min' => 'ФИО не должно быть менее :min символов',

            'admin.required' => 'Поле "Активировать" не выбрано',
            'admin.boolean' => 'Поле "Активировать" должно быть булиевым значением',

            'email.required' => 'E-mail не введен',
            'email.email' => 'E-mail введен не верно',
            'email.unique' => 'E-mail уже зарегистрирован',

            'roles.required' => 'Роль не выбрана',

            'password.required' => 'Пароль не указан',
            'password.min' => 'Пароль должн быть не менее :min символов',
            'password.confirmed' => 'Пароли не совпадают',

            'password_confirmation.required' => 'Повторите пароль',
        ];
    }
}

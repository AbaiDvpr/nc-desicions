<?php declare(strict_types=1);

namespace App\Http\Requests\Backend\Roles;

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
            'name' => [
                'required',
                'unique:Spatie\Permission\Models\Role,name',
                'regex:/^[a-zA-Z-]+$/u',
                'min:5'
            ],
            'permissions' => 'required|array',
            'guard_name' => 'required|in:' . implode(',', array_keys(config('auth.guards')))
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
            'name.required' => 'Имя роли не указано',
            'name.unique' => 'Роль с таким именем уже имеется',
            'name.regex' => 'Имя роли должно содержать только буквы английского алфавита',

            'permissions.required' => 'Правила не выбраны',
            'permissions.array' => 'Правила не выбраны',

            'guard_name.required' => 'Охранник не выбран',
            'guard_name.in' => 'Охранник выбран не верно',
        ];
    }
}

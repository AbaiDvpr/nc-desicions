<?php declare(strict_types=1);

namespace App\Http\Requests\Backend\Roles\Permissions;

use Illuminate\Foundation\Http\FormRequest;
use Spatie\Permission\Models\Role;

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
                'unique:Spatie\Permission\Models\Permission,name',
                'regex:/^[a-zA-Z-]+$/u',
            ],
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
            'name.required' => 'Имя правила не указано',
            'name.unique' => 'Правило с таким именем уже имеется',
            'name.regex' => 'Правило должно содержать только буквы английского алфавита',

            'guard_name.required' => 'Охранник не выбран',
            'guard_name.in' => 'Охранник выбран не верно',
        ];
    }
}

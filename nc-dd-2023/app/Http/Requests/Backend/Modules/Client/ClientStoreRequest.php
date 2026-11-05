<?php declare(strict_types=1);

namespace App\Http\Requests\Backend\Modules\Client;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

/**
 * Валидатор запроса на создание пользователя
 */
class ClientStoreRequest extends FormRequest
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
            'fullAccess'            => 'boolean',
            'courseAccess'          => 'boolean',
            'name'                  => 'required|min:2',
            'email'                 => 'required|email:rfc,dns|unique:users,email',
            'mobile'                => 'required|regex:"^(\+\d{1}) \(\d{3}\)-(\d{3})-(\d{2})-(\d{2})$"',
            'birthday'              => sprintf('nullable|before:%s', $year),
            'tariff'                => 'required|integer',
            'password'              => 'required|min:6|confirmed',
            'password_confirmation' => 'required',
        ];
    }

    /**
     * Сообщения об ошибках в валидации
     *
     * @return array
     */
    public function messages(): array
    {
        return __('backend/client/validation');
    }
}
<?php declare(strict_types=1);

namespace App\Http\Requests\Frontend\Registration;

use App\Rules\Recaptcha;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Правила валидации для формы регистрации
 */
class RegistrationRequest extends FormRequest
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
            'name'                  => 'required|string|min:2|max:100',
            'email'                 => 'required|email:rfc,dns|unique:users,email',
            'password'              => [
                'required',
                Password::min(6)->mixedCase()->symbols(),
                'confirmed',
            ],
            'password_confirmation' => 'required',
            'g-recaptcha-response'  => ['required', new Recaptcha],
        ];
    }

    /**
     * @inheritDoc
     *
     * @return array
     */
    public function messages(): array
    {
        return trans('validation.registration');
    }
}

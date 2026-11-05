<?php declare(strict_types=1);

namespace App\Http\Requests\Frontend\Feedback;

use App\Rules\Recaptcha;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Правила валидации для формы Обратной связи
 */
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
            'name'                 => 'required',
            'phone'                => 'required',
            'policy'               => 'accepted',
            'g-recaptcha-response' => ['required', new Recaptcha],
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
            'name.required'                 => 'Имя не указано',
            'name.min'                      => 'Имя не должно быть менее :min символов',
            'phone.required'                => 'Номер телефона не указан',
            'policy.accepted'               => 'Примите политику обработки персональных данных',
            'g-recaptcha-response.required' => 'Подтвердите, что вы не робот',
        ];
    }
}

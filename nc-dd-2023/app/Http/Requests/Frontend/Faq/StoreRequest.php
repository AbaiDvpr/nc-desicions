<?php declare(strict_types=1);

namespace App\Http\Requests\Frontend\Faq;

use App\Rules\Recaptcha;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Правила валидации для формы FAQ
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
            'surname'              => 'required',
            'email'                => 'required|email',
            'phone'                => 'required',
            'question'             => 'required|min:15',
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
            'surname.required'              => 'Фамилия не указана',
            'surname.min'                   => 'Фамилия не должна быть менее :min символов',
            'email.required'                => 'Email не указан',
            'email.email'                   => 'Email указан не корректно',
            'phone.required'                => 'Номер телефона не указан',
            'question.required'             => 'Вопрос не указан',
            'question.min'                  => 'Вопрос не должен быть менее :min символов',
            'policy.accepted'               => 'Примите политику обработки персональных данных',
            'g-recaptcha-response.required' => 'Подтвердите, что вы не робот',
        ];
    }
}

<?php declare(strict_types=1);

namespace App\Http\Requests\Frontend\Cabinet\Payment;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Обработчик и валидатор запроса на заявку пополнения через Kaspi
 */
class RequestRefill extends FormRequest
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
            'fullName' => 'required|max:100',
            'phone'    => 'required|regex:"^(\+\d{1}) \(\d{3}\) (\d{3})-(\d{2})-(\d{2})$"',
            'comment'  => 'sometimes|nullable|max:200',
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
            'fullName.required' => 'ФИО не указано',
            'name.max'          => 'Имя не должно превышать :max символов',
            'phone.required'    => 'Номер телефона не указан',
            'phone.regex'       => 'Номер телефона указан не верно',
            'comment.max'       => 'Комментарий не должен превышать :max символов',
        ];
    }
}

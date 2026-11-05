<?php declare(strict_types=1);

namespace App\Http\Requests\Frontend\Cabinet\Payment;

use Illuminate\Foundation\Http\FormRequest;

class PayRequest extends FormRequest
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
            'email'        => 'required|email',
            'phone'        => 'required|regex:"^(\+\d{1}) \(\d{3}\) (\d{3})-(\d{2})-(\d{2})$"',
            'description'  => 'sometimes|nullable|max:200',
            'verification' => 'accepted',
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
            'email.required'        => 'E-mail не введен',
            'email.email'           => 'E-mail введен не верно',
            'phone.required'        => 'Номер телефона не указан',
            'phone.regex'           => 'Номер телефона указан не верно',
            'description.max'       => 'Комментарий не должен превышать :max символов',
            'verification.accepted' => 'Вы не согласились с правилами сайта',
        ];
    }
}

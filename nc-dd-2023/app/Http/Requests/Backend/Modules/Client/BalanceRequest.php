<?php declare(strict_types=1);

namespace App\Http\Requests\Backend\Modules\Client;

use Illuminate\Foundation\Http\FormRequest;

class BalanceRequest extends FormRequest
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
            'balance' => 'required|numeric',
            'comment' => 'max:255',
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
            'balance.required' => 'Баланс не указан',
            'balance.numeric'  => 'Баланс должен быть числом',
            'comment.max'      => 'Комментарий не может превышать :max символов',
        ];
    }
}

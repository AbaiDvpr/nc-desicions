<?php declare(strict_types=1);

namespace App\Http\Requests\Backend\Rates;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    /**
     * @var mixed
     */
    private $relevant;

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
            'relevant' => 'required',
            'amount_USD' => 'required',
            'amount_EUR' => 'required',
            'amount_RUB' => 'required',
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
            'relevant.required' => 'Актуальная дата не указана',
            'amount_USD.required' => 'Курс доллара не указан',
            'amount_EUR.required' => 'Курс евро не указан',
            'amount_RUB.required' => 'Курс рубля не указан',
        ];
    }
}

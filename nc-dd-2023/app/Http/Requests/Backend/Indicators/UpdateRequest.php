<?php declare(strict_types=1);

namespace App\Http\Requests\Backend\Indicators;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Валидатор запроса на обновление показателей
 */
class UpdateRequest extends FormRequest
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
            'inflation'       => 'required',
            'annualInflation' => 'required',
            'baseRate'        => 'required',
            'tonia'           => 'required',
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
            'inflation.required'       => 'Пожалуйста, укажите цель по инфляции',
            'annualInflation.required' => 'Пожалуйста, укажите годовую инфляцию',
            'baseRate.required'        => 'Пожалуйста, укажите базовую ставку',
            'tonia.required'           => 'Пожалуйста, укажите TONIA',
        ];
    }
}

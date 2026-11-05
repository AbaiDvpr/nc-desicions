<?php declare(strict_types=1);

namespace App\Http\Requests\Frontend\Cabinet\Company;

use App\Rules\CompanyUuid;
use App\Rules\Currency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Валидатор запроса на сохранение компании
 */
class SaveRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'uuid'        => [
                'sometimes',
                'required',
                'string',
                new CompanyUuid,
            ],
            'name'        => 'required|string|min:3|max:256',
            'form'        => 'required|in:1,2',
            'tax_regime'  => [
                'required',
                Rule::in($this->getTaxRegime()),
            ],
            'nds'         => 'required|in:0,1',
            'currency'    => [
                'required',
                new Currency(),
            ],
            'description' => 'string|nullable|max:1000',
        ];
    }

    /**
     * Messages for validation rules.
     *
     * @return array
     */
    public function messages(): array
    {
        return __('company/validation');
    }

    /**
     * Возвращает режим налогообложения
     *
     * @return array
     */
    protected function getTaxRegime(): array
    {
        return array_keys(config('form-data.company.taxRegime'));
    }
}

<?php declare(strict_types=1);

namespace App\Http\Requests\Frontend\Cabinet\Company\Indicators;

use App\Rules\CompanyUuid;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Валидатор запроса на сохранение показателей
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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'companyUuid'             => ['required', new CompanyUuid],
            'proportionVariableCosts' => 'required|numeric|max:1000',
            'longTermAssets'          => 'required|numeric|max:1000',
            'workingCapital'          => 'required|numeric|max:1000',
            'needCash'                => 'required|numeric|max:1000',
            'interestRateOnLoans'     => 'required|numeric|max:1000',
            'incomeTaxRate'           => 'required|numeric|max:1000',
            'dividendPayoutRatio'     => 'required|numeric|max:1000',
            'inflationIndex'          => 'required|numeric|max:1000',
            'revenueGrowthRate'       => 'required|numeric|max:1000',
            'loanVolume'              => 'required|numeric|max:1000',
            'acquisitionAssets'       => 'required|numeric',
        ];
    }

    /**
     * Messages for validation rules.
     *
     * @return array
     */
    public function messages(): array
    {
        return __('company/indicators');
    }
}

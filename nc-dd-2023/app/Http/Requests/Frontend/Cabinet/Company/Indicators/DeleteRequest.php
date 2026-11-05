<?php declare(strict_types=1);

namespace App\Http\Requests\Frontend\Cabinet\Company\Indicators;

use App\Rules\CompanyUuid;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Валидатор запроса на удаление показателей
 */
class DeleteRequest extends FormRequest
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
            'companyUuid' => ['required', new CompanyUuid]
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

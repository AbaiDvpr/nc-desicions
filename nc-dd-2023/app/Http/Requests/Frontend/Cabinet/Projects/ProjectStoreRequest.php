<?php declare(strict_types=1);

namespace App\Http\Requests\Frontend\Cabinet\Projects;

use Illuminate\Foundation\Http\FormRequest;

class ProjectStoreRequest extends FormRequest
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
            'tariff' => 'required'
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
            'tariff.balance_accessibly' => 'У вас недостаточно средств на счёте',
        ];
    }
}

<?php declare(strict_types=1);

namespace App\Http\Requests\Frontend\Cabinet\Company;

use App\Rules\CompanyImport;
use App\Rules\CompanyUuid;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Валидатор запроса на сохранение импорта
 */
class ImportRequest extends FormRequest
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
            'file' => [
                'required',
                'mimes:xls,xlsx',
                /**
                 * TODO: Сделать валидиацию файла
                 */
                new CompanyImport,
            ],
            'companyUuid' => [
                'required',
                new CompanyUuid
            ]
        ];
    }
}

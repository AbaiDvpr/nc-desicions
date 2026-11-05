<?php declare(strict_types=1);
namespace App\Http\Requests\Api\Handbook\Direction;

use App\Rules\CompanyUuid;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Валидатор запроса на получение справочника направлений компании
 */
class CreateRequest extends FormRequest
{
    /**
     * Определите, авторизован ли пользователь для выполнения этого запроса.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Возвращает правила проверки запроса.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title'       => 'required|max:150',
            'companyUuid' => [
                'required',
                'string',
                new CompanyUuid(),
            ],
        ];
    }

    /**
     * Сообщения об ошибках
     *
     * @return array
     */
    public function messages(): array
    {
        return trans('company/accounting');
    }
}

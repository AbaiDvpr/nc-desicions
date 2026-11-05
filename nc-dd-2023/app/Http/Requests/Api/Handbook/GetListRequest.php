<?php declare(strict_types=1);
namespace App\Http\Requests\Api\Handbook;

use App\Rules\CompanyUuid;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Валидатор запроса на получение справочника
 */
class GetListRequest extends FormRequest
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
            'companyUuid' => [
                'required',
                'string',
                new CompanyUuid(),
            ],
            'type'        => ['required', Rule::in($this->getTypes())],
        ];
    }

    /**
     * Возвращает типы справочника
     *
     * @return array
     */
    protected function getTypes(): array
    {
        return array_keys(config('form-data.handbookTypes'));
    }

    /**
     * Сообщения об ошибках
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'companyUuid.required' => 'Не указан uuid компании',
            'type.required'        => 'Тип не указан',
        ];
    }
}

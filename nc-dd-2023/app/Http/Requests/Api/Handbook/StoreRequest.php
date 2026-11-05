<?php declare(strict_types=1);
namespace App\Http\Requests\Api\Handbook;

use App\Rules\CompanyUuid;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Валидатор запроса на создание справочника
 */
class StoreRequest extends FormRequest
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
            'type'        => ['required', Rule::in($this->getTypes())],
            'companyUuid' => [
                'required',
                'string',
                new CompanyUuid(),
            ],
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
            'companyUuid.required' => 'UUID компании не указан',
            'title.required'       => 'Наименование не указано',
            'title.max'            => 'Наименование не должно быть больше :max символов',
            'type.required'        => 'Тип не указан',
        ];
    }
}

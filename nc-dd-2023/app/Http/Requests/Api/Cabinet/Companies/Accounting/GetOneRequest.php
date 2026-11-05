<?php declare(strict_types=1);
namespace App\Http\Requests\Api\Cabinet\Companies\Accounting;

use App\Models\Accounting;
use App\Models\Company;
use App\Rules\Accounting\AcceptCompany;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

/**
 * Валидатор запроса на сохранение/добавление доходной/расходной части
 */
class GetOneRequest extends FormRequest
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
            'uuid'        => [
                new Exists(Accounting::class, '_id'),
                new AcceptCompany($this->request->get('companyUuid'))
            ],
            'companyUuid' => [
                'required',
                'string',
                Rule::exists(Company::class, 'uuid')->where(function ($query) {
                    return $query->where('userId', auth()->id());
                }),
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

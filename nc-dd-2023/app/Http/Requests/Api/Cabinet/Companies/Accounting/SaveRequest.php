<?php declare(strict_types=1);
namespace App\Http\Requests\Api\Cabinet\Companies\Accounting;

use App\Forms\Company\Base;
use App\Models\Company;
use App\Models\Handbook\Article;
use App\Rules\Accounting\Operation;
use App\Rules\Currency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\In;
use Illuminate\Validation\Rules\RequiredIf;

/**
 * Валидатор запроса на сохранение/добавление доходной/расходной части
 */
class SaveRequest extends FormRequest
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
            'uuid'         => 'sometimes|required|string',
            'operation'    => ['required', new Operation()],
            'companyUuid'  => [
                'required',
                Rule::exists(Company::class, 'uuid')->where(function ($query) {
                    return $query->where('userId', auth()->id());
                }),
            ],
            'accountCash'  => 'required|integer',
            'bankId'       => 'required_if:accountCash,' . Base::BANK_TRANSFER,
            'kindActivity' => 'sometimes|nullable|required|integer',
            'type'         => 'accountingType',
            'active'       => 'sometimes|nullable|required|integer',
            'activeType'   => 'activeType',
            'articleId'    => 'articleId',
            'price'        => 'required|numeric',
            'currency'     => ['required', new Currency()],
            'receiptDate'  => 'required|date_format:"Y-m-d H:i"',
            'dealDate'     => 'required|date_format:"Y-m-d H:i"',
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

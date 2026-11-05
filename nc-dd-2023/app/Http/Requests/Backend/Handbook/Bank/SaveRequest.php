<?php declare(strict_types=1);

namespace App\Http\Requests\Backend\Handbook\Bank;

use App\Models\Handbook\Bank;
use App\Rules\Validator\Bik;
use App\Rules\Validator\Bin;
use App\Rules\Validator\Iban;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Unique;

/**
 * Валидатор формы создания банка
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
     * @return array
     */
    public function rules(): array
    {
        return [
            'name'    => 'required',
            'address' => '',
            'phones'  => '',
            'iban'    => [
                'required',
                new Iban(),
                (new Unique(Bank::class, 'requisites.iban'))->ignore($this->id, '_id'),
            ],
            'rnn'     => '',
            'bin'     => [
                'required',
                new Bin(),
            ],
            'license' => '',
            'bik'     => [
                'required',
                new Bik(),
            ],
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
            'name.required'    => 'Укажите наименование банка',
            'address.required' => 'Адрес банка не указан',
            'phones.required'  => 'Телефоны банка не указаны',
            'iban.required'    => 'Международный номер банковского счета (IBAN) - не указан',
            'iban.unique'      => 'Банк с таким номером уже существует',
            'rnn.required'     => 'Регистрационный номер налогоплательщика (РНН) - не указан',
            'bin.required'     => 'Бизнес-Идентификационный Номер (БИН) - не указан',
            'license.required' => 'Лицензия не указана',
            'bik.required'     => 'SWIFT код или БИК - не указан',
        ];
    }
}

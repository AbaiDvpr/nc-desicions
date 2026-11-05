<?php declare(strict_types = 1);

namespace App\Http\Requests\Api\Cabinet\Counterparty;

use App\Models\Handbook\Bank;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Exists;

/**
 * Валидатор запроса на сохранение/добавление контрагента
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
            'uuid'          => 'nullable',
            'name'          => 'required|min:2',
            'legalAddress'  => 'required|min:3',
            'actualAddress' => 'nullable|min:3',
            'email'         => 'required|email',
            'phone'         => 'nullable',
            'director'      => 'required|min:3',
            'bin'           => 'required|min:11',
            'iik'           => 'required',
            'bankId'        => [
                'required', new Exists(Bank::class, '_id'),
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
            'name.required'         => 'Пожалуйста, укажите наименование компании',
            'name.min'              => 'Наименование не должно быть менее :min символов',
            'legalAddress.required' => 'Пожалуйста, укажите юридический адрес',
            'legalAddress.min'      => 'Юридический адрес не должно быть менее :min символов',
            'actualAddress.min'     => 'Фактический адрес не должно быть менее :min символов',
            'email.required'        => 'Пожалуйста, укажите e-mail',
            'email.email'           => 'E-mail введен не верно',
            'director.required'     => 'Пожалуйста, укажите директора',
            'director.min'          => 'ФИО Директора не должно быть менее :min символов',
            'bin.required'          => 'Пожалуйста, укажите БИН или ИИН',
            'bin.min'               => 'БИН или ИИН не должно быть менее :min символов',
            'iik.required'          => 'Пожалуйста, укажите ИИК',
            'bankId.required'       => 'Пожалуйста, укажите банк',
            'bankId.exists'         => 'Банк не найден',
        ];
    }
}
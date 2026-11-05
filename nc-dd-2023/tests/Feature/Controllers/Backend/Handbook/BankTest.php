<?php declare(strict_types=1);

namespace Tests\Feature\Controllers\Backend\Handbook;

use App\Http\Controllers\Backend\Handbook\Bank as BankController;
use App\Http\Requests\Backend\Handbook\Bank\SaveRequest;
use App\Models\Handbook\Bank;
use Helpers\PaymentData;
use Tests\Feature\Controllers\Backend\BaseController;

class BankTest extends BaseController
{
    /**
     * Проверяем сохранения формы справочника банков
     *
     * @dataProvider invalidBankFields
     * @return void
     */
    public function testInvalidFieldsByCreateBank(array $fields, array $errors)
    {
        $response = $this->get(route('backend.handbook.bank.index'));

        $response->assertStatus(200);

        $this->assertActionUsesFormRequest(BankController::class, 'save', SaveRequest::class);

        $responsePost = $this->post(route('backend.handbook.bank.save'), $fields);

        $responsePost->assertInvalid($errors);
    }

    /**
     * Тест на корректное создание банковских реквизитов
     *
     * @return void
     */
    public function testCreatedBank()
    {
        $response = $this->get(route('backend.handbook.bank.index'));

        $response->assertStatus(200);

        $this->assertActionUsesFormRequest(BankController::class, 'save', SaveRequest::class);

        $responsePost = $this->post(route('backend.handbook.bank.save'), [
            'name' => fake()->company,
            'iban' => PaymentData::getIik(),
            'bin'  => PaymentData::getBin(),
            'bik'  => PaymentData::getBik(),
        ]);

        $responsePost->assertSessionHas('success', 'Данные сохранены');

        $lastInserted = (new Bank())->latest('created_at')->first();
        if ($lastInserted) {
            $this->bankDestroyed[] = $lastInserted->getId();
        }
    }

    /**
     * Дата провайдер для теста с негативной авторизацией
     *
     * @return array
     */
    public static function invalidBankFields(): array
    {
        return [
            [
                'fields' => [
                    'name' => null,
                    'iban' => null,
                    'bin'  => null,
                    'bik'  => null,
                ],
                'errors' => [
                    'name' => 'Укажите наименование банка',
                    'iban' => 'Международный номер банковского счета (IBAN) - не указан',
                    'bin'  => 'Бизнес-Идентификационный Номер (БИН) - не указан',
                    'bik'  => 'SWIFT код или БИК - не указан',
                ],
            ],
            [
                'fields' => [
                    'name' => fake()->company,
                    'iban' => fake()->text(20),
                    'bin'  => fake()->text(20),
                    'bik'  => fake()->text(20),
                ],
                'errors' => [
                    'iban' => 'Номер счёта IBAN указан неверно',
                    'bin'  => 'БИН указан неверно',
                    'bik' => 'БИК указан неверно',
                ],
            ],
        ];
    }
}
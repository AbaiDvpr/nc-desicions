<?php declare(strict_types=1);

namespace Controllers\Frontend\Cabinet;

use App\Models\Counterparty;
use App\Models\Handbook\Bank;
use Helpers\PaymentData;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\Feature\Controllers\Frontend\BaseController;
use Tests\Helpers\UserHelper;

/**
 * Тесты для контрагента в кабинете
 */
class CounterpartyControllerTest extends BaseController
{
    /**
     * Создание клиента пользователя перед тестами
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->authCabinetUser();
    }

    /**
     * Проверяем создание контрагента с негативными данными
     *
     * @dataProvider invalidCounterpartyFields
     * @param  array $fields
     * @param  array $errors
     * @return void
     */
    public function testCreateCounterpartyWithInvalidFields(array $fields, array $errors): void
    {
        $response = $this->postJson(route('api.cabinet.counterparty.save'), $fields);

        $response->assertInvalid($errors);
    }

    /**
     * Проверяем создание контрагента
     *
     * @return void
     */
    public function testCreateCounterparty(): void
    {
        $response = $this->postJson(route('api.cabinet.counterparty.save'), $this->getValidCounterpartyFields());

        $response
            ->assertStatus(200)
            ->assertJson(fn (AssertableJson $json) => $json->has('id'));

        $lastInserted = (new Counterparty())->latest('created_at')->first();

        if ($lastInserted) {
            $this->counterpartyDestroyed[] = $lastInserted->getId();
        }
    }

    /**
     * Проверяем обновление контрагента
     *
     * @return void
     */
    public function testUpdateCounterparty(): void
    {
        $responsePost = $this->postJson(route('api.cabinet.counterparty.save'), $this->getValidCounterpartyFields());

        $responsePost
            ->assertStatus(200)
            ->assertJson(fn (AssertableJson $json) => $json->has('id'));

        $lastInserted = (new Counterparty())->latest('created_at')->first();

        $responseUpdate = $this->postJson(route('api.cabinet.counterparty.save', array_merge(
            ['uuid' => $lastInserted?->getId()],
            $this->getValidCounterpartyFields(),
        )));

        $responseUpdate
            ->assertStatus(200)
            ->assertJson(fn (AssertableJson $json) => $json->has('id'));

        if ($lastInserted) {
            $this->counterpartyDestroyed[] = $lastInserted->getId();
        }
    }

    /**
     * Проверяем удаление контрагента
     *
     * @return void
     */
    public function testDeleteCounterparty(): void
    {
        $responsePost = $this->postJson(route('api.cabinet.counterparty.save'), $this->getValidCounterpartyFields());

        $responsePost
            ->assertStatus(200)
            ->assertJson(fn (AssertableJson $json) => $json->has('id'));

        $lastInserted = (new Counterparty())->latest('created_at')->first();

        $responseDelete = $this->deleteJson(route('api.cabinet.counterparty.delete', [
            'uuid' => $lastInserted?->getId()
        ]));

        $responseDelete
            ->assertStatus(200)
            ->assertJson(['status' => true]);
    }

    /**
     * Возвращает корректные данные для формы контрагента
     *
     * @return array
     */
    protected function getValidCounterpartyFields(): array
    {
        return [
            'name'          => fake()->company,
            'legalAddress'  => fake()->address,
            'actualAddress' => fake()->address,
            'email'         => fake()->email,
            'director'      => fake()->name,
            'phone'         => (new UserHelper())->getRandomPhone(),
            'bin'           => PaymentData::getBin(),
            'iik'           => PaymentData::getIik(),
            'bankId'        => (new Bank())->latest('created_at')->first()?->getId(),
        ];
    }

    /**
     * Дата провайдер для теста с негативными данными
     *
     * @return array
     */
    public static function invalidCounterpartyFields(): array
    {
        return [
            [
                'fields' => [
                    'name'          => null,
                    'legalAddress'  => null,
                    'email'         => null,
                    'director'      => null,
                    'bin'           => null,
                    'iik'           => null,
                    'bankId'        => null,
                ],
                'errors' => [
                    'name'          => 'Пожалуйста, укажите наименование компании',
                    'legalAddress'  => 'Пожалуйста, укажите юридический адрес',
                    'email'         => 'Пожалуйста, укажите e-mail',
                    'director'      => 'Пожалуйста, укажите директора',
                    'bin'           => 'Пожалуйста, укажите БИН или ИИН',
                    'iik'           => 'Пожалуйста, укажите ИИК',
                    'bankId'        => 'Пожалуйста, укажите банк',
                ],
            ],
            [
                'fields' => [
                    'name'          => fake()->asciify(1),
                    'legalAddress'  => fake()->asciify(2),
                    'actualAddress' => fake()->asciify(2),
                    'email'         => fake()->text(10),
                    'director'      => fake()->asciify(2),
                    'bin'           => fake()->text(10),
                    'bankId'        => fake()->text(10),
                ],
                'errors' => [
                    'name'          => 'Наименование не должно быть менее 2 символов',
                    'legalAddress'  => 'Юридический адрес не должно быть менее 3 символов',
                    'actualAddress' => 'Фактический адрес не должно быть менее 3 символов',
                    'email'         => 'E-mail введен не верно',
                    'director'      => 'ФИО Директора не должно быть менее 3 символов',
                    'bin'           => 'БИН или ИИН не должно быть менее 11 символов',
                    'bankId'        => 'Банк не найден',
                ],
            ],
        ];
    }
}

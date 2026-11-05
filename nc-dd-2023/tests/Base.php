<?php declare(strict_types=1);
namespace Tests;

use App\DI;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Log;
use JMac\Testing\Traits\AdditionalAssertions;

/**
 * Базовый класс для запуска тестов
 */
abstract class Base extends BaseTestCase
{
    use CreatesApplication;
    use AdditionalAssertions;
    use WithFaker;

    /**
     * Идентификаторы пользователей которых нужно уничтожить после тестирования
     *
     * @var array
     */
    public array $userDestroyed = [];

    /**
     * Идентификаторы банков которые после теста необходимо удалять
     *
     * @var array
     */
    public array $bankDestroyed = [];

    /**
     * Идентификаторы контрагентов которые после теста необходимо удалять
     *
     * @var array
     */
    public array $counterpartyDestroyed = [];

    /**
     * @inheritdoc
     *
     * @return void
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    protected function callBeforeApplicationDestroyedCallbacks(): void
    {
        $this->clearCreatedUsers();
        $this->clearCreatedBanks();
        $this->clearCreatedCounterparties();
    }

    /**
     * Очистка пользователей созданных во время тестирования
     *
     * @return void
     */
    private function clearCreatedUsers(): void
    {
        if (count($this->userDestroyed)) {
            foreach ($this->userDestroyed as $userId) {
                try {
                    DI::getRepository()->getUser()->deleteUserById($userId);
                } catch (\Throwable $throwable) {
                    Log::error('[Test] User delete failure', [
                        'message' => $throwable->getMessage(),
                        'trace'   => $throwable->getTrace(),
                        'code'    => $throwable->getCode(),
                    ]);
                }
            }
        }
    }

    /**
     * Очистка созданных банков после прохода тестов
     *
     * @return void
     */
    private function clearCreatedBanks(): void
    {
        if (count($this->bankDestroyed)) {
            foreach ($this->bankDestroyed as $id) {
                try {
                    $bank = DI::getRepository()->getHandbook()->getBank()->getOne($id);

                    DI::getRepository()->getHandbook()->getBank()->delete($bank);
                } catch (\Throwable $throwable) {
                    Log::error('[Test] Bank delete failure', [
                        'message' => $throwable->getMessage(),
                        'trace'   => $throwable->getTrace(),
                        'code'    => $throwable->getCode(),
                    ]);
                }
            }
        }
    }

    /**
     * Очистка созданных контрагентов после прохода тестов
     *
     * @return void
     */
    private function clearCreatedCounterparties(): void
    {
        if (count($this->counterpartyDestroyed)) {
            foreach ($this->counterpartyDestroyed as $id) {
                try {
                    DI::getRepository()->getCounterparty()->delete(
                        DI::getRepository()->getCounterparty()->getOne($id)
                    );
                } catch (\Throwable $throwable) {
                    Log::error('[Test] Counterparty delete failure', [
                        'message' => $throwable->getMessage(),
                        'trace'   => $throwable->getTrace(),
                        'code'    => $throwable->getCode(),
                    ]);
                }
            }
        }
    }
}

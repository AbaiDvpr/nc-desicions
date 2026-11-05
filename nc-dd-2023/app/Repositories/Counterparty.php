<?php declare(strict_types = 1);

namespace App\Repositories;

use App\Models\Counterparty as CounterpartyModel;
use App\Models\Counterparty\Filter\PagerParams;
use App\Models\Counterparty\Forms\Counterparty as CounterpartyForm;
use App\Models\User as UserModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Collection;

/**
 * Репозиторий для работы с контрагентами
 */
class Counterparty extends BaseRepository
{
    /**
     * Возвращает счёт по его идентификатору
     *
     * @param  string $id
     * @return \App\Models\Counterparty|null
     */
    public function getOne(string $id): ?CounterpartyModel
    {
        return (new CounterpartyModel())->find($id);
    }

    /**
     * Получаем список контрагентов пользователя
     *
     * @param  \App\Models\Counterparty\Filter\PagerParams $params
     * @return \Illuminate\Database\Eloquent\Collection|null
     */
    public function getFromParams(PagerParams $params): ?Collection
    {
        $counterparties = new CounterpartyModel();

        if ($params->getUserId()) {
            $counterparties = $counterparties->where('userId', $params->getUserId());
        }

        if ($params->getBankId()) {
            $counterparties = $counterparties->where('bankId', $params->getBankId());
        }

        if ($params->getName()) {
            $counterparties = $counterparties->where('name', 'LIKE', '%' . $params->getName() . '%');
        }

        if ($params->getEmail()) {
            $counterparties = $counterparties->where('email', 'LIKE', '%' . $params->getEmail() . '%');
        }

        if ($params->getBin()) {
            $counterparties = $counterparties->where('bin', 'LIKE', '%' . $params->getBin() . '%');
        }

        return $counterparties
            ->offset($params->getOffset())
            ->limit($params->getLimit())
            ->latest()
            ->get();
    }

    /**
     * Сохраняет данные банка
     *
     * @param  \App\Models\User                            $user
     * @param  string|null                                 $id
     * @param  \App\Models\Counterparty\Forms\Counterparty $form
     * @return \App\Models\Counterparty|null
     */
    public function save(UserModel $user, ?string $id, CounterpartyForm $form): ?CounterpartyModel
    {
        return DB::transaction(function () use ($user, $id, $form) {
            $counterparty = $id ? $this->getOne($id) : new CounterpartyModel();
            $counterparty->setUserId($user->getAuthIdentifier());
            $counterparty->setName($form->getName());
            $counterparty->setLegalAddress($form->getLegalAddress());
            $counterparty->setActualAddress($form->getActualAddress());
            $counterparty->setEmail($form->getEmail());
            $counterparty->setPhone($form->getPhone());
            $counterparty->setDirector($form->getDirector());
            $counterparty->setBin($form->getBin());
            $counterparty->setIik($form->getIik());
            $counterparty->setBankId($form->getBankId());

            if (!$counterparty->save()) {
                Log::error('Ошибка сохранения контрагентов и его банковских реквизитов', [
                    'form' => $form->toArray(),
                ]);

                return null;
            }

            return $counterparty->refresh();
        }, 2);
    }

    /**
     * Удаление банка из реквизитов
     *
     * @param  \App\Models\Counterparty $counterparty
     * @return bool
     */
    public function delete(CounterpartyModel $counterparty): bool
    {
        return DB::transaction(function () use ($counterparty) {
            return (bool)$counterparty->delete();
        });
    }

}
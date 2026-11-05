<?php declare(strict_types=1);

namespace App\Repositories\Handbook;

use App\Models\Handbook\Bank as BankModel;
use App\Models\Handbook\Forms\Bank as BankForm;
use App\Repositories\Handbook;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Репозиторий для работы со справочником банков
 */
class Bank extends Handbook
{
    /**
     * Возвращает счёт по его идентификатору
     *
     * @param  string                        $id
     * @return \App\Models\Handbook\Bank|null
     */
    public function getOne(string $id): ?BankModel
    {
        return (new BankModel())->find($id);
    }

    /**
     * Возвращает список всех банков
     *
     * @return \App\Models\Handbook\Bank[]
     */
    public function getAll(): array
    {
        return (new BankModel())->get()->toArray();
    }

    /**
     * Сохраняет данные банка
     *
     * @param string|null                     $id
     * @param \App\Models\Handbook\Forms\Bank $form
     * @return \App\Models\Handbook\Bank|null
     */
    public function save(?string $id, BankForm $form): ?BankModel
    {
        return DB::transaction(function () use ($id, $form) {
            $bank = $id ? $this->getOne($id) : new BankModel();

            $bank->setName($form->getName());
            $bank->getRequisites()
                ->setAddress($form->getAddress())
                ->setPhones($form->getPhones())
                ->setIban($form->getIban())
                ->setRnn($form->getRnn())
                ->setBin($form->getBin())
                ->setLicense($form->getLicense())
                ->setBik($form->getBik());

            if (!$bank->save()) {
                Log::error('Ошибка сохранения банка и реквизитов', [
                    'form' => $form->toArray()
                ]);

                return null;
            }

            return $bank->refresh();
        }, 2);
    }

    /**
     * Удаление банка из реквизитов
     *
     * @param \App\Models\Handbook\Bank $bank
     * @return bool
     */
    public function delete(BankModel $bank): bool
    {
        return DB::transaction(function () use ($bank) {
            return (bool) $bank->delete();
        });
    }

    /**
     * Получить список банков из справочника с постраничной навигацией
     *
     * @param  array                                       $params
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getWithPaginate(array $params): LengthAwarePaginator
    {
        $banks = new BankModel();

        if (isset($params['name'])) {
            $banks = $banks->where('name', (string)$params['name']);
        }

        return $banks->latest('created_at')->paginate((int) $params['limit']);
    }
}

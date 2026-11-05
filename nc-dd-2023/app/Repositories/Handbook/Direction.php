<?php declare(strict_types=1);

namespace App\Repositories\Handbook;

use App\Models\Handbook\Direction as DirectionModel;
use App\Models\Handbook\Forms\Direction as DirectionForm;
use App\Repositories\Handbook;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Репозиторий для работы со справочником направлений компаний
 */
class Direction extends Handbook
{
    /**
     * Возвращает счёт по его идентификатору
     *
     * @param  string $id
     * @return \App\Models\Handbook\Direction|null
     */
    public function getOne(string $id): ?DirectionModel
    {
        return (new DirectionModel())->find($id);
    }

    /**
     * Возвращает список всех направлений компании
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAll(): Collection
    {
        return (new DirectionModel())->get();
    }

    /**
     * Возвращает список всех направлений компании
     *
     * @param  string                                   $companyUuid
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByCompany(string $companyUuid): Collection
    {
        return (new DirectionModel())->where('companyUuid', $companyUuid)->get();
    }

    /**
     * Сохраняет направления в справочник
     *
     * @param  string|null                          $id
     * @param  \App\Models\Handbook\Forms\Direction $form
     * @return \App\Models\Handbook\Direction|null
     */
    public function save(?string $id, DirectionForm $form): ?DirectionModel
    {
        return DB::transaction(function () use ($id, $form) {
            $direction = $id ? $this->getOne($id) : new DirectionModel();

            $direction->setTitle($form->getTitle());
            $direction->setCompanyUuid($form->getCompanyUuid());

            if (!$direction->save()) {
                Log::error('[Handbook] Ошибка сохранения направления', [
                    'form' => $form->toArray()
                ]);

                return null;
            }

            return $direction->refresh();
        }, 2);
    }

    /**
     * Удаление направления из справочника
     *
     * @param  \App\Models\Handbook\Direction $direction
     * @return bool
     */
    public function delete(DirectionModel $direction): bool
    {
        return DB::transaction(function () use ($direction) {
            return (bool) $direction->delete();
        });
    }
}

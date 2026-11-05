<?php declare(strict_types=1);

namespace App\Repositories\Handbook;

use App\Models\Handbook as HandbookModel;
use App\Models\Handbook\Forms\Handbook as HandbookForm;
use App\Repositories\Handbook;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Репозиторий для работы со справочником
 */
class Common extends Handbook
{
    /**
     * Возвращает один справочник
     *
     * @param  string                   $id
     * @return \App\Models\Handbook|null
     */
    public function getOne(string $id): ?HandbookModel
    {
        return (new HandbookModel())->find($id);
    }

    /**
     * Возвращает список справочников
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAll(): Collection
    {
        return (new HandbookModel())->get();
    }

    /**
     * Возвращает список справочников по типу
     *
     * @param  string                                  $companyUuid
     * @param  int                                     $type
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByType(string $companyUuid, int $type): Collection
    {
        return (new HandbookModel())
            ->where('companyUuid', $companyUuid)
            ->where('type', $type)
            ->get();
    }

    /**
     * Сохраняет справочник
     *
     * @param  string|null                          $id
     * @param  \App\Models\Handbook\Forms\Handbook $form
     * @return \App\Models\Handbook|null
     */
    public function save(?string $id, HandbookForm $form): ?HandbookModel
    {
        return DB::transaction(function () use ($id, $form) {
            $handbook = $id ? $this->getOne($id) : new HandbookModel();

            $handbook->setCompanyUuid($form->getCompanyUuid());
            $handbook->setTitle($form->getTitle());
            $handbook->setType($form->getType());

            if (!$handbook->save()) {
                Log::error('[Handbook] Ошибка сохранения справочника', [
                    'form' => $form->toArray()
                ]);

                return null;
            }

            return $handbook->refresh();
        }, 2);
    }

    /**
     * Удаляет справочник
     *
     * @param  \App\Models\Handbook $handbook
     * @return bool
     */
    public function delete(HandbookModel $handbook): bool
    {
        return DB::transaction(function () use ($handbook) {
            return (bool) $handbook->delete();
        });
    }
}

<?php declare(strict_types = 1);

namespace App\Models\Presenters\UserTariff;

use App\Models\UserTariff;
use Carbon\Carbon;

/**
 * Презентер для обработки публичных данных пользовательских тарифов
 */
class Data
{
    /**
     * @var \App\Models\UserTariff
     */
    private UserTariff $userTariff;

    /**
     * Конструктор
     *
     * @param \App\Models\UserTariff $userTariff
     */
    public function __construct(UserTariff $userTariff)
    {
        $this->userTariff = $userTariff;
    }

    /**
     * Возвращает публичные данные
     *
     * @return array
     */
    public function getPublicData(): array
    {
        $expire    = $this->userTariff->getExpire();
        $isExpired = false;

        if (!is_null($expire)) {
            $expire    = $expire->format(DATE_ATOM);
            $isExpired = Carbon::now()->gt($this->userTariff->getExpire());
        }

        return [
            'id'          => $this->userTariff->getId(),
            'name'        => $this->userTariff->getName(),
            'description' => $this->userTariff->getDescription(),
            'price'       => $this->userTariff->getPrice(),
            'company'     => $this->userTariff->getCompany(),
            'project'     => $this->userTariff->getProject(),
            'created'     => $this->userTariff->getCreatedAt()->format(DATE_ATOM),
            'expire'      => $expire,
            'isExpired'   => $isExpired
        ];
    }
}

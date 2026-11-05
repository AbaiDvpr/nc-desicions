<?php declare(strict_types=1);

namespace App\Models\Presenters\User;

use App\Models\User;
use App\Models\User as UserModel;
use Carbon\Carbon;

/**
 * Презентер для данных пользователя
 */
class Data
{
    /**
     * Экземпляр текущего пользователя
     *
     * @var \App\Models\User
     */
    private UserModel $user;

    /**
     * Конструктор
     *
     * @param \App\Models\User $user
     */
    public function __construct(UserModel $user)
    {
        $this->user = $user;
    }

    /**
     * Возвращает публичные данные пользователя в виде массива
     *
     * @return array<string, mixed>
     */
    public function getPublicData(): array
    {
        /** @var \App\Models\UserTariff $tariff */
        $tariff   = $this->user->tariff();
        $birthday = $this->user->getBirthday();
        $avatar   = $this->user->getFirstMedia(User::PROFILE_COLLECTION_NAME);

        return [
            'id'           => $this->user->getAuthIdentifier(),
            'cabinetId'    => $this->user->getCabinetId(),
            'name'         => $this->user->getName(),
            'email'        => $this->user->getEmail(),
            'mobile'       => formatPhone($this->user->getMobile()),
            'birthday'     => $birthday ? Carbon::parse($birthday)->format('Y.m.d') : null,
            'balance'      => $this->user->getBalance(),
            'tariff'       => $tariff?->getPresenter()->getPublicData(),
            'avatar'       => !is_null($avatar) ? $avatar->getUrl('largeThumb') : null,
            'fullAccess'   => $this->user->hasFullAccess(),
            'courseAccess' => $this->user->hasCourseAccess(),
            'courseEula'   => $this->user->hasCourseEula(),
            'apiToken'     => $this->user->getApiToken(),
        ];
    }
}

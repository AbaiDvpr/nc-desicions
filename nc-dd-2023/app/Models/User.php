<?php declare(strict_types=1);

namespace App\Models;

use App\DI;
use App\Models\Presenters\User\Data as UserData;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Notifications\Notifiable;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Permission\Traits\HasRoles;
use Spatie\MediaLibrary\HasMedia;

/**
 * @property mixed                  $id
 * @property mixed                  $name
 * @property mixed                  $email
 * @property bool                   admin
 * @property bool                   $fullAccess
 * @property bool                   $courseAccess
 * @property bool                   $courseEula
 * @property mixed                  email_verified_at
 * @property float|int|mixed        balance
 * @property \App\Models\UserTariff $tariff
 * @method static findOrFail(int|string|null $id)
 * @method role(string $string)
 * @method static find(int $id)
 * @method static create(array $attributes)
 * @method static where(string $string, $id)
 */
class User extends Authenticatable implements HasMedia
{
    use InteractsWithMedia, HasFactory, Notifiable, HasRoles, HasApiTokens;

    /**
     * Роль клиента
     */
    const ROLE_CLIENT = 'client';

    /**
     * Имя коллекции для фотографий профиля
     */
    const PROFILE_COLLECTION_NAME = 'profile-image';

    /**
     * @inheritdoc
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Презентер данных пользователя
     *
     * @var \App\Models\Presenters\User\Data
     */
    protected $userData;

    /**
     * Возвращает email пользователя
     *
     * @return string
     */
    public function getEmail(): string
    {
        return $this->getAttribute('email');
    }

    /**
     * Возвращает телефон пользователя
     *
     * @return string
     */
    public function getMobile(): string
    {
        return (string) $this->getAttribute('mobile');
    }

    /**
     * Возвращает телефон пользователя
     *
     * @return null|string
     */
    public function getBirthday(): ?string
    {
        return $this->getAttribute('birthday');
    }

    /**
     * @param mixed            $name
     * @return \App\Models\User
     */
    public function setName($name): User
    {
        $this->setAttribute('name', $name);

        return $this->user;
    }

    /**
     * Возвращает имя пользователя
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->getAttribute('name');
    }

    /**
     * Проверяет установлен ли токен для запросов к API
     *
     * @return string
     */
    public function getApiToken(): string
    {
        return (string) $this->getAttribute('apiToken');
    }

    /**
     * Устанавливает токен для авторизации запросов
     *
     * @param  string $token
     * @return $this
     */
    public function setApiToken(string $token): static
    {
        $this->setAttribute('apiToken', $token);

        return $this;
    }

    /**
     * Проверяет установлен ли токен для запросов к API
     *
     * @return bool
     */
    public function hasApiToken(): bool
    {
        return (bool) $this->getAttribute('apiToken');
    }

    /**
     * Возвращает номер личного кабинета
     *
     * @return \Illuminate\Support\Stringable
     */
    public function getCabinetId(): \Illuminate\Support\Stringable
    {
        return Str::of((string) $this->getKey())->padLeft(8, '0');
    }

    /**
     * Текущее значение баланса
     *
     * @return float
     */
    public function getBalance(): float
    {
        return $this->balance ?? 0;
    }

    /**
     * @return HasMany
     */
    public function balanceHistories(): HasMany
    {
        return $this->hasMany(BalanceHistory::class);
    }

    /**
     * Активирован ли аккаунт
     *
     * @return bool
     */
    public function isActivated(): bool
    {
        return $this->email_verified_at ? true : false;
    }

    /**
     * Возвращает ID тарифа
     *
     * @return int
     */
    public function getTariffId(): int
    {
        return (int) $this->getAttribute('tariff_id');
    }

    /**
     * Устанавливает ID тарифа
     *
     * @param  int  $id
     * @return \App\Models\User
     */
    public function setTariffId(int $id): self
    {
        $this->setAttribute('tariff_id', $id);

        return $this;
    }

    /**
     * Список проектов пользователя
     *
     * @return HasMany
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /**
     * @param $pass
     */
    public function setPasswordAttribute($pass)
    {
        $this->attributes['password'] = \Hash::make($pass);
    }

    /**
     * Возвращает объект для работы с данными пользователя
     *
     * @return \App\Models\Presenters\User\Data
     */
    public function getUserData(): UserData
    {
        if (!$this->userData) {
            $this->userData = new UserData($this);
        }

        return $this->userData;
    }

    /**
     * Возвращает пользовательский тариф
     *
     * @return \App\Models\UserTariff|null
     */
    public function tariff(): ?UserTariff
    {
        return DI::getRepository()->getUserTariff()->getOne($this->getTariffId());
    }

    /**
     * Возвращает данные для записи в лог
     *
     * @return array
     */
    public function getLogData(): array
    {
        return [
            'id'    => $this->getAuthIdentifier(),
            'email' => $this->getEmail(),
        ];
    }

    /**
     * Возвращает JWT токен для авторизации по токену
     *
     * @return string
     */
    public function getJWTToken(): string
    {
        return DI::getRepository()->getJwtToken()->encode([
            'id'  => $this->getAuthIdentifier(),
            'exp' => Carbon::now()->addYear()->getTimestamp()
        ]);
    }

    /**
     * Регистрирует обработчики для фотографий загружаемых к модели
     *
     * @param  Media|null                                   $media
     * @throws \Spatie\Image\Exceptions\InvalidManipulation
     */
    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('smallThumb')
            ->width(70)->height(70)->sharpen(10)
            ->performOnCollections('*')
            ->nonQueued();

        $this->addMediaConversion('mediumThumb')
            ->width(300)->height(300)->sharpen(10)
            ->performOnCollections('*')
            ->nonQueued();

        $this->addMediaConversion('largeThumb')
            ->width(600)->height(600)->sharpen(15)
            ->performOnCollections('*')
            ->nonQueued();
    }

    /**
     * Применяет выбранный тариф к пользователю
     *
     * @param  \App\Models\Tariff   $tariff
     * @return \App\Models\User|null
     */
    public function applyTariff(Tariff $tariff): ?self
    {
        return DI::getRepository()->getUserTariff()->apply($this, $tariff);
    }

    /**
     * @param  bool            $isUnlimited
     * @return \App\Models\User
     */
    public function setFullAccess(bool $isUnlimited): self
    {
        $this->setAttribute('fullAccess', $isUnlimited);

        return $this;
    }

    /**
     * @return bool
     */
    public function hasFullAccess(): bool
    {
        return (bool) $this->getAttribute('fullAccess');
    }

    /**
     * @return bool
     */
    public function hasCourseAccess(): bool
    {
        return (bool) $this->getAttribute('courseAccess');
    }

    /**
     * @return bool
     */
    public function hasCourseEula(): bool
    {
        return (bool) $this->getAttribute('courseEula');
    }
}

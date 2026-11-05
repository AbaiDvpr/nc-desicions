<?php declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Модель для хранения заявок на обратную связь
 */
class Feedback extends Model
{
    use SoftDeletes;

    /**
     * @var string
     */
    protected $table = 'feedbacks';

    /**
     * @inheritdoc
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Статусы
     */
    const STATUS_NEW          = 1; // Новая
    const STATUS_IN_PROCESS   = 2; // В работе
    const STATUS_CONSULTATION = 3; // Консультация
    const STATUS_DENIED       = 4; // Отказано
    const STATUS_TARGET       = 5; // Целевой

    /**
     * Возвращает ID
     *
     * @return int
     */
    public function getId(): int
    {
        return (int) $this->getAttribute('id');
    }

    /**
     * Устанавливает статус
     *
     * @param  int                 $status
     * @return \App\Models\Feedback
     */
    public function setStatus(int $status): self
    {
        $this->setAttribute('status', $status);

        return $this;
    }

    /**
     * Возвращает статус
     *
     * @return int
     */
    public function getStatus(): int
    {
        return (int) $this->getAttribute('status');
    }

    /**
     * Устанавливает имя
     *
     * @param  string              $name
     * @return \App\Models\Feedback
     */
    public function setName(string $name): self
    {
        $this->setAttribute('name', $name);

        return $this;
    }

    /**
     * Возвращает имя
     *
     * @return string
     */
    public function getName(): string
    {
        return (string) $this->getAttribute('name');
    }

    /**
     * Устанавливает телефон
     *
     * @param  string              $phone
     * @return \App\Models\Feedback
     */
    public function setPhone(string $phone): self
    {
        $this->setAttribute('phone', $phone);

        return $this;
    }

    /**
     * Возвращает телефон
     *
     * @return string
     */
    public function getPhone(): string
    {
        return (string) $this->getAttribute('phone');
    }

    /**
     * Устанавливает язык подачи заявки
     *
     * @param  string              $lang
     * @return \App\Models\Feedback
     */
    public function setLang(string $lang): self
    {
        $this->setAttribute('lang', $lang);

        return $this;
    }

    /**
     * Возвращает язык подачи заявки
     *
     * @return string
     */
    public function getLang(): string
    {
        return (string) $this->getAttribute('lang');
    }

    /**
     * Устанавливает IP адрес с которого поступила заявка
     *
     * @param  string              $ip
     * @return \App\Models\Feedback
     */
    public function setIp(string $ip): self
    {
        $this->setAttribute('ip', $ip);

        return $this;
    }

    /**
     * Возвращает IP адрес с которого поступила заявка
     *
     * @return string
     */
    public function getIp(): string
    {
        return (string) $this->getAttribute('ip');
    }
}

<?php declare(strict_types=1);

namespace App\Models\Company;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Сущность для работы с импортами
 */
class Import extends Model
{
    use SoftDeletes;

    /**
     * @inheritdoc
     *
     * @var string
     */
    protected $table = 'companies-imports';

    /**
     * @inheritdoc
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Путь к директории файлов
     */
    const DIRECTORY_PATH = '/company/imports';

    /**
     * Возвращает ID
     *
     * @return int
     */
    public function getId(): int
    {
        return (int) $this->id;
    }

    /**
     * Устанавливает uuid компании
     *
     * @param  string                    $uuid
     * @return \App\Models\Company\Import
     */
    public function setCompanyUuid(string $uuid): static
    {
        $this->setAttribute('companyUuid', $uuid);

        return $this;
    }

    /**
     * Возвращает uuid компании
     *
     * @return string
     */
    public function getCompanyUuid(): string
    {
        return (string) $this->getAttribute('companyUuid');
    }

    /**
     * Возвращает путь к файлу
     *
     * @return string
     */
    public function getPath(): string
    {
        return (string) $this->getAttribute('path');
    }

    /**
     * Устанавливает путь к файлу
     *
     * @param  string                    $path
     * @return \App\Models\Company\Import
     */
    public function setPath(string $path): static
    {
        $this->setAttribute('path', $path);

        return $this;
    }

    /**
     * Устанавливает статус загрузки файла
     *
     * @param  bool                      $status
     * @return \App\Models\Company\Import
     */
    public function setStatus(bool $status): static
    {
        $this->setAttribute('status', $status);

        return $this;
    }

    /**
     * Возвращает статус загрузки файла
     *
     * @return bool
     */
    public function getStatus(): bool
    {
        return (bool) $this->getAttribute('status');
    }

    /**
     * Устанавливает IP пользователя, который загрузил файл
     *
     * @param  string                    $ip
     * @return \App\Models\Company\Import
     */
    public function setIp(string $ip): static
    {
        $this->setAttribute('ip', $ip);

        return $this;
    }

    /**
     * Возвращает IP пользователя, который загрузил файл
     *
     * @return string
     */
    public function getIp(): string
    {
        return (string) $this->getAttribute('ip');
    }

    /**
     * Возвращает массив логируемых данных
     *
     * @return array
     */
    public function getLogData(): array
    {
        return [
            'id'          => $this->getId(),
            'companyUuid' => $this->getCompanyUuid(),
            'path'        => $this->getPath(),
            'status'      => $this->getStatus(),
            'ip'          => $this->getIp(),
        ];
    }
}

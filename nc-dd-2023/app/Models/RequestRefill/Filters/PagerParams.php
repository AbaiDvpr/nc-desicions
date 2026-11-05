<?php declare(strict_types=1);

namespace App\Models\RequestRefill\Filters;

use Illuminate\Http\Request;

/**
 * Класс для работы с параметрами запроса для получения списка заявок
 */
class PagerParams
{
    /**
     * Лимит получаемых записей по умолчанию
     */
    const DEFAULT_LIMIT = 10;

    /**
     * ID пользователя
     *
     * @var int
     */
    protected int $userId;

    /**
     * Номер телефона в заявке
     *
     * @var string
     */
    protected string $phone;

    /**
     * Номер страницы
     *
     * @var int
     */
    protected int $page = 1;

    /**
     * Лимит получаемых записей
     *
     * @var int
     */
    protected int $limit = self::DEFAULT_LIMIT;

    /**
     * Смещение для получения записей
     *
     * @var int
     */
    protected int $offset = 0;

    /**
     * Создает экзэмпляр класса заполняя свойства данными из запроса
     *
     * @param  \Illuminate\Http\Request                      $request
     * @return \App\Models\RequestRefill\Filters\PagerParams
     */
    public static function createFromRequest(Request $request): PagerParams
    {
        $params = new self();

        $params->userId = (int) $request->input('userId');
        $params->phone  = (string) $request->input('phone');
        $params->page   = (int) $request->input('page', 1);
        $params->limit  = (int) $request->input('limit', self::DEFAULT_LIMIT);
        $params->offset = ($params->page * $params->limit) - $params->limit;

        return $params;
    }

    /**
     * Устанавливает ID пользователя для запроса
     *
     * @param  int  $userId
     * @return void
     */
    public function setUserId(int $userId): void
    {
        $this->userId = $userId;
    }

    /**
     * Устанавливает лимит на выборку
     *
     * @param  int  $limit
     * @return void
     */
    public function setLimit(int $limit): void
    {
        $this->limit = $limit;
    }

    /**
     * Возвращает список заполненных свойств в виде массива
     *
     * @return array
     */
    public function toArray(): array
    {
        return array_merge(array_filter([
            'userId' => $this->userId,
            'phone'  => $this->phone,
        ]), [
            'offset' => $this->offset,
            'limit'  => $this->limit,
        ]);
    }
}

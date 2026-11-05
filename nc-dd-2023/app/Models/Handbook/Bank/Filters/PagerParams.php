<?php declare(strict_types=1);

namespace App\Models\Handbook\Bank\Filters;

use Illuminate\Http\Request;

/**
 * Класс для работы с параметрами запроса для получения списка компании
 */
class PagerParams
{
    /**
     * Лимит получаемых записей по умолчанию
     */
    const DEFAULT_LIMIT = 20;

    /**
     * Название компании
     *
     * @var string
     */
    protected string $name;

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
     * @param  \Illuminate\Http\Request                     $request
     * @return \App\Models\Handbook\Bank\Filters\PagerParams
     */
    public static function createFromRequest(Request $request): PagerParams
    {
        $params = new self();

        $params->name   = (string) $request->input('name');
        $params->page   = (int) $request->input('page', 1);
        $params->limit  = (int) $request->input('limit', self::DEFAULT_LIMIT);
        $params->offset = ($params->page * $params->limit) - $params->limit;

        return $params;
    }

    /**
     * Возвращает список заполненных свойств в виде массива
     *
     * @return array
     */
    public function toArray(): array
    {
        return array_merge(array_filter([
            'name'     => $this->name,
            'page'   => $this->page,
        ]), [
            'offset'   => $this->offset,
            'limit'    => $this->limit,
        ]);
    }
}

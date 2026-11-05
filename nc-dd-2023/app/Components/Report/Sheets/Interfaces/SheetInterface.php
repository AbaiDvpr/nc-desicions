<?php declare(strict_types=1);

namespace App\Components\Report\Sheets\Interfaces;


interface SheetInterface
{
    /**
     * Сгенерировать данные для заполнения листа
     *
     * @return mixed
     */
    public function generate();

    /**
     * Вставить сгенерированные данные на текущий лист
     *
     * @return mixed
     */
    public function push();
}

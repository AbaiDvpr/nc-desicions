<?php declare(strict_types=1);

namespace App\Models\Handbook\Bank;

use App\Models\Handbook\Bank;

/**
 * Базовый класс для работы со вложенными полями
 */
abstract class Base implements BaseInterface
{
    /**
     * @var \App\Models\Handbook\Bank
     */
    public Bank $bank;

    /**
     * Список свойств доступных для сохранения во вложенном поле
     *
     * @var array|string[]
     */
    protected array $validProperties = [];

    /**
     * Инициализации структуры с реквизитами банка
     *
     * @param \App\Models\Handbook\Bank $bank
     */
    public function __construct(Bank $bank)
    {
        $this->bank = $bank;
    }

    /**
     * Возвращает список сохраненных реквизитов банка
     *
     * @return array
     */
    public function getRawRequisites(): array
    {
        return (array) $this->bank->getAttribute($this->getFieldName());
    }

    /**
     * Возвращает свойство из реквизитов по имени
     *
     * @param  string    $name
     * @return mixed|null
     */
    public function getProperty(string $name): mixed
    {
        if (!isset($name, $this->validProperties)) {
            return null;
        }

        return $this->getRawRequisites()[$name] ?? null;
    }

    /**
     * Устанавливает значение в реквизиты
     *
     * @param  string                   $name
     * @param  mixed                    $value
     * @return \App\Models\Handbook\Bank
     */
    public function setProperty(string $name, mixed $value): Bank
    {
        if (!in_array($name, $this->validProperties, true)) {
            return $this->bank;
        }

        $rawData        = $this->getRawRequisites();
        $rawData[$name] = $value;

        // если приходит пустое значение в поле, то мы его удаляем
        if (empty($value)) {
            unset($rawData[$name]);
        }

        // если поле вообще пустое остается, то удаляем его
        if (empty($rawData)) {
            $this->bank->drop($this->getFieldName());

            return $this->bank;
        }

        $this->bank->setAttribute($this->getFieldName(), $rawData);

        return $this->bank;
    }

    /**
     * Возвращает наименование поля для вложенных значений
     *
     * @return string
     */
    protected function getFieldName(): string
    {
        return $this->fieldName;
    }
}

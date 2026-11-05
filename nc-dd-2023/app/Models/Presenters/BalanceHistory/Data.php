<?php declare(strict_types = 1);

namespace App\Models\Presenters\BalanceHistory;

use App\Models\BalanceHistory;

/**
 * Презентер для истории изменения баланса
 */
class Data
{
    /**
     * @var \App\Models\BalanceHistory
     */
    private BalanceHistory $history;

    /**
     * Конструктор
     *
     * @param \App\Models\BalanceHistory $history
     */
    public function __construct(BalanceHistory $history)
    {
        $this->history = $history;
    }

    /**
     * Возвращает публичные данные компании
     *
     * @return array
     */
    public function getPublicData(): array
    {
        return [
            'id'        => $this->history->getId(),
            'comment'   => $this->history->getComment(),
            'price'     => $this->history->getPaid(),
            'residue'   => $this->history->getResidue(),
            'paidAt'    => $this->history->getPaidAt(),
            'operation' => $this->history->getOperation(),
        ];
    }
}

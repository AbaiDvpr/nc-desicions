<?php declare(strict_types=1);
namespace App\Rules\Accounting;

use App\Forms\Company\Active;
use App\Forms\Company\Income;
use App\Forms\Company\Outcome;
use Illuminate\Contracts\Validation\Rule;

/**
 * Правило проверки операций доходной/расходной
 */
class Operation implements Rule
{
    /**
     * Определяет, проходит ли правило проверку.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        return in_array(strtolower($value), [
            Income::NAME,
            Outcome::NAME,
            Active::NAME,
        ], true);
    }

    /**
     * Возвращает сообщение об ошибке проверки.
     *
     * @return string
     */
    public function message(): string
    {
        return trans('company/accounting.operation.operation');
    }
}

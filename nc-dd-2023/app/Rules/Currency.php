<?php declare(strict_types=1);
namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

/**
 * Правило проверки курсов валют
 */
class Currency implements Rule
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
        if (!is_string($value)) {
            return false;
        }

        return in_array(strtolower($value), $this->getValidCurrencies(), true);
    }

    /**
     * Возвращает сообщение об ошибке проверки.
     *
     * @return string
     */
    public function message(): string
    {
        return trans('company/accounting.currency.currency');
    }

    /**
     * Возвращает список доступных курсов валют
     *
     * @return array
     */
    protected function getValidCurrencies(): array
    {
        return collect(config('form-data.currencies'))->keys()->toArray();
    }
}

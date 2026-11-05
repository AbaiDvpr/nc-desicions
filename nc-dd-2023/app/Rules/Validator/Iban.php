<?php declare(strict_types=1);
namespace App\Rules\Validator;

use App\Helpers\Banking as BankingHelper;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Валидация номер банковского счета
 *
 * @link http://iban.kz/
 */
class Iban implements ValidationRule
{
    /**
     * {@inheritDoc}
     *
     * @param  \Phalcon\Validation $validation
     * @param  string              $attribute
     * @return bool
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $isValid = strlen($value) === BankingHelper::IBAN_LENGTH && BankingHelper::checkIbanCountryCode($value)
            && BankingHelper::checkBankCode($value);

        if (!$isValid) {
            $fail('Номер счёта IBAN указан неверно');
        }
    }
}

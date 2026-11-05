<?php
namespace App\Rules\Validator;

use App\Helpers\Banking as BankingHelper;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Валидатор для БИКов
 *
 * @link http://iban.kz/
 */
class Bik implements ValidationRule
{
    /**
     * @param string   $attribute
     * @param mixed    $value
     * @param \Closure $fail
     * @return void
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $isValid = BankingHelper::checkBikCountryCode($value) && BankingHelper::checkBik($value);

        if (!$isValid) {
            $fail('БИК указан неверно');
        }
    }
}

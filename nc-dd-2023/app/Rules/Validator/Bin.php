<?php declare(strict_types=1);
namespace App\Rules\Validator;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Валидация БИН-номера по длине, соответствию разрядов и контрольной сумме
 *
 * @link http://adilet.zan.kz/rus/docs/P030000565_
 */
class Bin implements ValidationRule
{
    /**
     * Длина БИН-номера
     *
     * @const int
     */
    const BIN_LENGTH = 12;

    /**
     * Число-исключение, при котором контрольная сумма проверяется при второй последовательности разрядов
     *
     * @const int
     */
    const EXCEPTION_NUMBER = 10;

    /**
     * Первая последовательность весов разрядов для проверки контрольной суммы
     *
     * @const int
     */
    const FIRST_SEQUENCE = 1;

    /**
     * Вторая последовательность весов разрядов для проверки контрольной суммы
     *
     * @const int
     */
    const SECOND_SEQUENCE = 2;

    /**
     * @param string   $attribute
     * @param mixed    $value
     * @param \Closure $fail
     * @return void
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $isValid = true;

        if (strlen($value) !== self::BIN_LENGTH) {
            $isValid = false;
        } else {
            $numbers      = str_split($value);
            $controlDigit = (int) $numbers[11];

            unset($numbers[11]);

            if (!$this->isValidFifthDigit($numbers)) {
                $isValid = false;
            } else {
                $checkSum = $this->countCheckSum($numbers) % 11;

                if ($checkSum === self::EXCEPTION_NUMBER) {
                    $checkSum = $this->countCheckSum($numbers, self::SECOND_SEQUENCE) % 11;
                }

                if ($checkSum === self::EXCEPTION_NUMBER) {
                    $isValid = false;
                }

                if ($checkSum !== $controlDigit) {
                    $isValid = false;
                }
            }
        }

        if (!$isValid) {
            $fail('БИН указан неверно');
        }
    }

    /**
     * Проверка соответствия пятого разряда
     *
     * @param  array $numbers
     * @return bool
     */
    protected function isValidFifthDigit($numbers): bool
    {
        return $numbers[4] > 3 && $numbers[4] <= 6;
    }

    /**
     * Вычисление контрольной суммы согласно последовательности весов разрядов:
     * - для первой последовательности действует правило
     *   разряд БИН:  1 2 3 4 5 6 7 8 9 10 11
     *   вес разряда: 1 2 3 4 5 6 7 8 9 10 11
     *
     * - для второй последовательности — правило
     *   разряд ИИН:  1 2 3 4 5 6 7 8 9 10 11
     *   вес разряда: 3 4 5 6 7 8 9 10 11 1 2
     *
     * @param  array     $numbers
     * @param  int       $sequence
     * @return float|int
     */
    protected function countCheckSum(array $numbers, int $sequence = self::FIRST_SEQUENCE): float|int
    {
        $checkSum = 0;

        if ($sequence === self::FIRST_SEQUENCE) {
            foreach ($numbers as $digit => $number) {
                $checkSum += ($digit + 1) * $number;
            }
        } elseif ($sequence === self::SECOND_SEQUENCE) {
            foreach ($numbers as $digit => $number) {
                $weight   = $digit + 3 > 11 ? ($digit + 3) - 11 : ($digit + 3);
                $checkSum += $weight * $number;
            }
        }

        return $checkSum;
    }
}

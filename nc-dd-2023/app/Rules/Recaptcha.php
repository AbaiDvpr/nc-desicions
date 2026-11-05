<?php declare(strict_types=1);

namespace App\Rules;

use App\DI;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Log;

/**
 * Класс для валидации google captcha
 */
class Recaptcha implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param string                                                                $attribute
     * @param mixed                                                                 $value
     * @param \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (app()->environment('testing')) {
            return;
        }

        try {
            if (!DI::getRecaptcha()->getVerdict($value, request()->ip())) {
                $fail('Вы не подтвердили, что вы не робот!');
            }

            return;
        } catch (\Throwable $throwable) {
            Log::error('[Rules] Ошибка во время валидации recaptcha', [
                'exception' => $throwable,
                'response'  => $value,
                'ip'        => request()->ip(),
            ]);
        }

        $fail('Подтвердите, что вы не робот');
    }
}

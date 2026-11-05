<?php declare(strict_types=1);

namespace App\Rules;

use App\DI;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Класс для валидации компании на принадлежность текущему пользователю
 */
class CompanyUuid implements ValidationRule
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
        /** @var \App\Models\Company $company */
        $company = DI::getRepository()->getCompany()->getByUuid($value);

        if (!$company || $company->getUserId() !== auth()->id()) {
            $fail('Компания не принадлежит текущему пользователю');
        }
    }
}

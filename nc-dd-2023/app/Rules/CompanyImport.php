<?php declare(strict_types=1);

namespace App\Rules;

use Illuminate\Contracts\Validation\InvokableRule;

/**
 * Класс для валидации импортируемых файлов компании
 */
class CompanyImport implements InvokableRule
{
    /**
     * Run the validation rule.
     *
     * @param  string                                                                $attribute
     * @param  mixed                                                                 $value
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString $fail
     * @return void
     */
    public function __invoke($attribute, $value, $fail): void
    {
//        $fail('Test fail!');
    }
}

<?php declare(strict_types=1);

namespace App\Rules;

use App\DI;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Класс для валидации проекта на принадлежность текущему пользователю
 */
class ProjectUuid implements ValidationRule
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
        /** @var \App\Models\Project $project */
        $project = DI::getRepository()->getProject()->getOneByUuid($value);

        if (!$project || $project->getUserId() !== auth()->id()) {
            $fail('Финансовая модель не принадлежит текущему пользователю');
        }
    }
}

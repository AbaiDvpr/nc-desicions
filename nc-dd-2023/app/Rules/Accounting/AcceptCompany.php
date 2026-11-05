<?php declare(strict_types=1);
namespace App\Rules\Accounting;

use App\DI;
use Illuminate\Contracts\Validation\Rule;

/**
 * Правило проверки принадлежности записи переданной компании
 */
class AcceptCompany implements Rule
{
    /**
     * Uuid компании для которой проверяется принадлежность
     *
     * @var string
     */
    protected string $companyUuid;

    /**
     * @param string $companyUuid
     */
    public function __construct(string $companyUuid)
    {
        $this->companyUuid = $companyUuid;
    }

    /**
     * Определяет, проходит ли правило проверку.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        $accounting = DI::getRepository()->getAccounting()->getOne($value);

        if (!$accounting) {
            return false;
        }

        return $accounting->getCompanyUuid() === $this->companyUuid;
    }

    /**
     * Возвращает сообщение об ошибке проверки.
     *
     * @return string
     */
    public function message(): string
    {
        return trans('company/accounting.uuid.acceptCompany');
    }
}

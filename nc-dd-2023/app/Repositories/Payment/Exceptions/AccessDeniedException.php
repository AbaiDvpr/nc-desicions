<?php declare(strict_types=1);

namespace App\Repositories\Payment\Exceptions;

/**
 * Исключение выбрасываемое при попытке изменить счёт другого аккаунта
 */
class AccessDeniedException extends \Exception
{
}

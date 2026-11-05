<?php declare(strict_types=1);

namespace App\Repositories;

use App\Traits\Cache\Cacheable;

/**
 * Базовый репозиторий
 */
class BaseRepository
{
    use Cacheable;
}

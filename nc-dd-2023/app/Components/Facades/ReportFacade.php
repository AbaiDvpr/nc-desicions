<?php declare(strict_types=1);

namespace App\Components\Facades;

use Illuminate\Support\Facades\Facade;

class ReportFacade extends Facade
{
    /**
     * @inheritdoc
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'report';
    }
}

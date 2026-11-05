<?php

namespace App\Modules\Payment\Wooppay\Objects\Interfaces;

/**
 * Response interface.
 */
interface Response
{
    /**
     * Constructs object from response.
     *
     * @param $data
     * @return mixed
     */
    public static function factory($data);
}

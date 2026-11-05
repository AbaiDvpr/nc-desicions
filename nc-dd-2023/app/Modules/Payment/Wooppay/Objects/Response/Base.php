<?php
declare(strict_types=1);

namespace App\Modules\Payment\Wooppay\Objects\Response;

use App\Modules\Payment\Wooppay\Objects\Exceptions\BadCredentials;
use App\Modules\Payment\Wooppay\Objects\Exceptions\LowBalance;
use App\Modules\Payment\Wooppay\Objects\Exceptions\UnsuccessfulResponse;
use App\Modules\Payment\Wooppay\Objects\Interfaces\Response;

/**
 * Base response object.
 */
abstract class Base implements Response
{
    /**
     * Result code for success.
     */
    const ERROR_CODE_NONE = 0;

    /**
     * Result code for authentication error.
     */
    const ERROR_CODE_BAD_CREDENTIALS = 5;

    /**
     * Result code for low balance.
     */
    const ERROR_CODE_LOW_BALANCE = 226;

    /**
     * Checks response for errors.
     *
     * @param  \stdClass            $response
     * @return boolean
     * @throws BadCredentials
     * @throws LowBalance
     * @throws UnsuccessfulResponse
     */
    public static function checkResponse(\stdClass $response)
    {
        switch (true) {
            case isset($response->error_code) && $response->error_code == self::ERROR_CODE_NONE:
                return true;
                break;

            case isset($response->error_code) && $response->error_code == self::ERROR_CODE_BAD_CREDENTIALS:
                throw new BadCredentials();
                break;

            case isset($response->error_code) && $response->error_code == self::ERROR_CODE_LOW_BALANCE:
                throw new LowBalance();
                break;

            default:
                throw new UnsuccessfulResponse(
                    $response,
                    isset($response->error_code) ? $response->error_code : 500
                );

                break;
        }
    }
}

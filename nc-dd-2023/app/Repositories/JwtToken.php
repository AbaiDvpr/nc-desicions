<?php declare(strict_types=1);

namespace App\Repositories;

use Firebase\JWT\JWT as JwtEncode;
use Firebase\JWT\Key;

/**
 * Репозиторий для работы JWT токенами
 */
class JwtToken
{
    /**
     * Формирует JWT token на основе переданных данных
     *
     * @param  array  $data
     * @return string
     */
    public function encode(array $data): string
    {
        return JwtEncode::encode($data, config('app.jwt_token'), 'HS256');
    }

    /**
     * Декодирует токен и возвращает в виде объекта
     *
     * @param  string    $token
     * @return \stdClass
     */
    public function decode(string $token): \stdClass
    {
        return JwtEncode::decode($token, new Key(config('app.jwt_token'), 'HS256'));
    }
}

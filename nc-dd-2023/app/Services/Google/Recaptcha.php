<?php declare(strict_types=1);

namespace App\Services\Google;

use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Support\Facades\Log;

/**
 * Сервис для проверки recaptcha
 */
class Recaptcha
{
    /**
     * Конфигурация captcha
     *
     * @var array
     */
    protected array $config;

    /**
     * Конструктор
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Возвращает результат проверки captcha
     *
     * @param  string $response
     * @param  string $ip
     * @return bool
     */
    public function getVerdict(string $response, string $ip): bool
    {
        try {
            $response = $this->getResponse($response, $ip);

            return (bool) $response->success;
        } catch (\Throwable $throwable) {
            Log::error('[Recaptcha] Ошибка запроса', [
                'exception' => $throwable,
                'response'  => $response,
                'ip'        => $ip,
            ]);
        }

        return false;
    }

    /**
     * Выполняет запрос на проверку captcha
     *
     * @param  string                                $response
     * @param  string                                $ip
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Exception
     */
    private function getResponse(string $response, string $ip): mixed
    {
        $client = new GuzzleClient(['timeout' => $this->config['timeout']]);

        $result = $client->get(
            $this->config['url'],
            [
                'query' => [
                    'secret'   => $this->config['secretKey'],
                    'response' => $response,
                    'remoteip' => $ip,
                ]
            ]
        );

        if ($result->getStatusCode() !== 200) {
            throw new \Exception('Invalid status code');
        }

        return json_decode($result->getBody()->getContents());
    }
}

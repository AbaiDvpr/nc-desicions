<?php

namespace App\Modules\Http;

use App\Modules\Http\Exceptions\BaseException;
use App\Modules\Http\Exceptions\HttpBadRequestException;
use App\Modules\Http\Exceptions\HttpException;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\MultipartStream;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use Illuminate\Support\Facades\Log;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Log\LogLevel;

abstract class JsonServiceClient implements JsonClientInterface
{
    /**
     * Инстанс базового клиента HTTP
     *
     * @var \GuzzleHttp\Client
     */
    protected Client $httpClient;

    /**
     * Настройки подключения к сервису
     *
     * @var array{host: string, timeoutMs: int, branch: string, defaultHeaders: array, dynamicOptions: array}
     */
    protected array $options = [];

    /**
     * Конструктор
     *
     * @param array $options
     */
    public function __construct(array $options)
    {
        $this->setOptions($options);
        $this->setClient();
    }

    /**
     * Возвращает название сервиса
     *
     * @return string
     */
    public function getServiceName(): string
    {
        return $this->options['name'] ?? '';
    }

    protected function setClient()
    {
        $this->httpClient = new Client($this->getOptions());
    }

    /**
     * @return \GuzzleHttp\Client
     */
    protected function getClient(): Client
    {
        return $this->httpClient;
    }

    /**
     * Возвращает опции клиента
     *
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Устанавливает опции клиента
     *
     * @param  array{host: string, timeoutMs: int, branch: string, defaultHeaders: array} $options
     * @throws \InvalidArgumentException
     */
    public function setOptions(array $options): void
    {
        if (!isset($options['base_uri'])) {
            throw new \InvalidArgumentException('base_uri is required');
        }

        $this->options = $options;

        // сохраняем хост в исходном виде, чтобы не затереть подстановку переменной $branch
        $this->options['originalHost'] = $this->options['base_uri'];
    }

    /**
     * Создаёт запрос к сервису
     *
     * @param  string                                $method
     * @param  string|\Psr\Http\Message\UriInterface $uri
     * @param  \App\Modules\Http\RequestParameters   $params
     * @return \Psr\Http\Message\RequestInterface
     */
    public function createRequest(string $method, $uri, RequestParameters $params): RequestInterface
    {
        // if (!$params->getTimeoutMs()) {
        //     // если в запросе не указан таймаут, то используем таймаут из глобальных настроек
        //     if (isset($this->options['timeoutMs'])) {
        //         $params->setTimeoutMs($this->options['timeoutMs']);
        //     }
        //
        //     // автоматически устанавливаем readTimeout в GET запросы и writeTimeout в остальные
        //     if ($method === 'GET' && isset($this->options['readTimeoutMs'])) {
        //         $params->setTimeoutMs($this->options['readTimeoutMs']);
        //     } elseif ($method !== 'GET' && isset($this->options['writeTimeoutMs'])) {
        //         $params->setTimeoutMs($this->options['writeTimeoutMs']);
        //     }
        // }
        //
        if (isset($this->options['defaultHeaders'])) {
            $params->addHeaders($this->options['defaultHeaders']);
        }

        if (!($uri instanceof UriInterface)) {
            $uri = new Uri($uri);
        }

        $query = $params->getQuery();

        if ($query) {
            $uri = $uri->withQuery(http_build_query($query));
        }

        $body = $params->getBody();

        if ($params->isMultipart()) {
            $body = new MultipartStream($body);
        }

        $request = new Request(
            $method,
            $uri,
            $params->getHeaders(),
            $body,
            $options['version'] ?? '1.1'
        );

        if ($params->getTimeoutMs()) {
            $request = $request->withAddedHeader('x-guzzle-timeout-ms', $params->getTimeoutMs());
        }

        return $request;
    }

    /**
     * @inheritdoc
     *
     * @param  \Psr\Http\Message\RequestInterface                    $request
     * @return array
     * @throws \App\Modules\Http\Exceptions\BaseException
     * @throws \App\Modules\Http\Exceptions\HttpException
     * @throws \App\Modules\Http\Exceptions\InvalidResponseException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function requestJson(RequestInterface $request): array
    {
        try {

            $response = $this->httpClient->send($request);

            return Helper::getResponseJSON($response);
        } catch (HttpException $e) {
            // ошибки HTTP 4xx-5xx обрабатываем отдельно, чтобы дочерний класс мог переопределить поведение
            return $this->handleHttpException($e);
        } catch (BaseException $e) {
            if (!$e->getRequest()) {
                // чтобы ошибка залогировалась вместе с запросом
                $e->setRequest($request);
            }

            // остальные ошибки логируем с уровнем error
            $this->logException($e, LogLevel::ERROR);

            throw $e;
        }
    }

    /**
     * Возвращает URI на основе переданного пути
     *
     * @param  string|\Psr\Http\Message\UriInterface $uriOrPath
     * @return string|\Psr\Http\Message\UriInterface
     */
    protected function getUri($uriOrPath): UriInterface|string
    {
        if ($uriOrPath instanceof UriInterface) {
            if (isset($this->options['host']) && !$uriOrPath->getHost()) {
                $uriOrPath = $uriOrPath->withHost($this->options['host']);
            }

            return $uriOrPath;
        }

        if (isset($this->options['host'])) {
            return $this->options['host'] . '/' . ltrim($uriOrPath, '/');
        }

        return $uriOrPath;
    }

    /**
     * Логирует исключение, пойманное при выполнении запроса к API
     *
     * @param \Throwable $e
     * @param string $level
     * @param array      $context
     */
    protected function logException(\Throwable $e, string $level = LogLevel::ERROR, array $context = []): void
    {
        $context['exception'] = $e;

        if ($e instanceof BaseException) {
            if ($e->getRequest()) {
                $context['serviceHttpRequest'] = $this->httpClient->messageToString($e->getRequest());
            }

            if ($e->getResponse()) {
                $context['serviceHttpResponse'] = $this->httpClient->messageToString($e->getResponse());
            }

            $context = array_merge($context, $e->getLogContext());
        }

        Log::log($level,
            sprintf(
                '[%s] [%s] Ошибка при запросе к сервису',
                $this->getServiceName(),
                get_class($e)
            ),
            $context
        );
    }

    /**
     * Обрабатывает ошибку HTTP 4xx-5xx: логирует и выбрасывает её снова.
     * В дочернем классе метод также может вернуть массив данных, если сочтёт, что сценарий не является ошибочным.
     *
     * @param  \App\Modules\Http\Exceptions\HttpException $e
     * @return array
     * @throws \App\Modules\Http\Exceptions\HttpException
     */
    protected function handleHttpException(HttpException $e): array
    {
        $this->logException(
            $e,
            // ошибки 4хх логируем с уровнем notice, остальные - error
            $e instanceof HttpBadRequestException ? LogLevel::NOTICE : LogLevel::ERROR
        );

        throw $e;
    }
}

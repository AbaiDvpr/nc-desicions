<?php declare(strict_types=1);

namespace App\Modules\Http;

/**
 * Параметры HTTP запроса
 */
class RequestParameters
{
    /**
     * Параметры query string
     *
     * @var array
     */
    protected $query = [];

    /**
     * Тело запроса (может быть строкой, объектом, потоком, ресурсом)
     *
     * @var mixed
     */
    protected $body;

    /**
     * Передаём ли тело запроса в формате multipart form-data
     *
     * @var bool
     */
    protected $isMultipart = false;

    /**
     * Заголовки
     *
     * @var array
     */
    protected $headers = [];

    /**
     * Таймаут запроса в миллисекундах
     *
     * @var int|null
     */
    protected $timeoutMs;

    /**
     * Конструктор
     *
     * @param array      $query
     * @param array      $headers
     * @param null|mixed $body
     */
    public function __construct(array $query = [], array $headers = [], $body = null)
    {
        $this->query   = $query;
        $this->headers = $headers;
        $this->body    = $body;
    }

    /**
     * Возвращает параметры query string
     *
     * @return array
     */
    public function getQuery(): array
    {
        return $this->query;
    }

    /**
     * Устанавливает параметры query string
     *
     * @param  array                                $query
     * @return \App\Modules\Http\RequestParameters
     */
    public function setQuery(array $query): self
    {
        $this->query = $query;

        return $this;
    }

    /**
     * Устанавливает тело запроса в формате form-data
     *
     * @param  array                                $form
     * @return \App\Modules\Http\RequestParameters
     */
    public function setBodyFormData(array $form): self
    {
        $this->body                    = http_build_query($form, '', '&');
        $this->headers['content-type'] = 'application/x-www-form-urlencoded';

        return $this;
    }

    /**
     * Устанавливает тело запроса в формате json
     *
     * @param  array|object                         $data
     * @return \App\Modules\Http\RequestParameters
     */
    public function setBodyJson($data): self
    {
        $this->body                    = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $this->headers['content-type'] = 'application/json';

        return $this;
    }

    /**
     * Устанавливает тело запроса в формате multipart form-data.
     * Каждый элемент массива должен иметь ключи name и contents, в формате guzzle.
     * См. документацию guzzle https://docs.guzzlephp.org/en/stable/quickstart.html#sending-form-files
     *
     * @param  array                                $parts
     * @return \App\Modules\Http\RequestParameters
     */
    public function setBodyMultipart(array $parts): self
    {
        $this->body        = $parts;
        $this->isMultipart = true;

        return $this;
    }

    /**
     * Возвращает признак того, нужно ли передать тело запроса в формате multipart form-data
     *
     * @return bool
     */
    public function isMultipart(): bool
    {
        return $this->isMultipart;
    }

    /**
     * Возвращает заголовки
     *
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Устанавливает заголовки
     *
     * @param  array                                $headers
     * @return \App\Modules\Http\RequestParameters
     */
    public function setHeaders(array $headers): self
    {
        $this->headers = $headers;

        return $this;
    }

    /**
     * Добавляет заголовки к запросу
     *
     * @param array $headers
     * @return $this
     */
    public function addHeaders(array $headers): self
    {
        $this->headers = array_replace($this->headers, $headers);

        return $this;
    }

    /**
     * Устанавливает заголовок
     *
     * @param string $name
     * @param string $value
     * @return $this
     */
    public function setHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;

        return $this;
    }

    /**
     * Возвращает тело запроса
     * (может быть строкой, объектом, потоком, ресурсом)
     *
     * @return mixed
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Устанавливает тело запроса
     * (может быть строкой, объектом, потоком, ресурсом)
     *
     * @param  mixed                                $body
     * @return \App\Modules\Http\RequestParameters
     */
    public function setBody($body): self
    {
        $this->body = $body;

        return $this;
    }

    /**
     * Возвращает таймаут для выполнения запроса
     *
     * @return int|null
     */
    public function getTimeoutMs(): ?int
    {
        return $this->timeoutMs;
    }

    /**
     * Устанавливает таймаут для выполнения запроса
     *
     * @param  int|null                             $timeoutMs
     * @return \App\Modules\Http\RequestParameters
     */
    public function setTimeoutMs(int $timeoutMs = null): self
    {
        $this->timeoutMs = $timeoutMs;

        return $this;
    }
}

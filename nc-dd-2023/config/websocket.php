<?php

/**
 * Конфиги для подключения к Centrifuge
 */
return [
    /**
     * Базовый хост для подключения к centrifuge
     */
    'host' => env('WS_HOST', '127.0.0.1:8000'),

    /**
     * Секретный ключ
     */
    'secretKey' => env('WS_SECRET_KEY', ''),

    /**
     * URL для подключения с фронта
     */
    'apiUrl' => env('WS_API_URL', '/connection/websocket'),

    /**
     * API ключ для отправки сообщений
     */
    'apiKey' => env('WS_API_KEY', ''),
];

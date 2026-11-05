<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Collectors
    |--------------------------------------------------------------------------
    |
    | The collectors specified here will be auto-registered in the exporter.
    |
    */

    'collectors' => [
        // \Your\ExporterClass::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Buckets config
    |--------------------------------------------------------------------------
    |
    | The buckets config specified here will be passed to the histogram generator
    | in the prometheus client. You can configure it as an array of time bounds.
    | Default value is null.
    |
    */

    'routes_buckets' => null,
    'sql_buckets' => null,
    'guzzle_buckets' => null,
];

<?php

return [

    'domain' => env(
        'APP_BACKEND_DOMAIN',
        sprintf('admin-%s.nc-team.org', env('APP_USER', 'master'))
    )
];

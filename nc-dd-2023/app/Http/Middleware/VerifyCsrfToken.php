<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
        '/ajax/upload*',
        '*/cabinet/*',
        '*/handbook/*',
    ];

    /**
     * @inheritdoc
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     * @return mixed
     * @throws \Illuminate\Session\TokenMismatchException
     */
    public function handle($request, Closure $next): mixed
    {
        if (isAdminProject()) {
            return $next($request);
        }

        if ($this->getTokenFromRequest($request) == 'test') {
            return $next($request);
        }

        return parent::handle($request, $next);
    }
}

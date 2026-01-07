<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
        'api/*',  // Exclude all API routes
        // OR specific routes:
        // 'api/auth/login',
        // 'api/auth/verify-mfa',
        // 'api/auth/refresh-token',
    ];
}
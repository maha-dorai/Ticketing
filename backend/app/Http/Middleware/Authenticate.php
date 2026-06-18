<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Pure API project — never redirect, always return 401 JSON.
     */
    protected function redirectTo(Request $request): ?string
    {
        return null;
    }
}

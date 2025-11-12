<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthBoutiqueOrAdmin
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::guard('boutique')->check() || Auth::guard('admin')->check()) {
            return $next($request);
        }

        return response()->json(['message' => 'Non autorisé'], 403);
    }
}


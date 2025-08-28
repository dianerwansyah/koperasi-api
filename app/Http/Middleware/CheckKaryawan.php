<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckKaryawan
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->user()->role !== 'karyawan') {
            return response()->json(['message' => 'Forbidden. Karyawan only.'], 403);
        }
        return $next($request);
    }
}

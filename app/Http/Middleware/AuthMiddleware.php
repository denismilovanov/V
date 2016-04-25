<?php namespace App\Http\Middleware;

use Closure;

class AuthMiddleware {
    public function handle($request, Closure $next)
    {
        $url = $request->path();

        if (strpos($url, "admin/login") !== 0) {
            if (! \Auth::check()) {
                return redirect(env('ADMIN_RELATIVE_URL') . '/login');
            }
        } else {
            if (\Auth::check()) {
                return redirect(env('ADMIN_RELATIVE_URL') . '/users');
            }
        }

        return $next($request);
    }
}

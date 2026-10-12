<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BasicAuthOwner
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = (string) config('worklog.auth_user');
        $pass = (string) config('worklog.auth_password');

        if ($user === '' || $pass === '') {
            // Production tanpa kredensial: tolak, jangan dibiarkan terbuka.
            if (app()->environment('production')) {
                abort(503, 'Login belum dikonfigurasi (WORKLOG_AUTH_USER / WORKLOG_AUTH_PASSWORD).');
            }

            return $next($request);
        }

        if (hash_equals($user, (string) $request->getUser())
            && hash_equals($pass, (string) $request->getPassword())) {
            return $next($request);
        }

        return response('Perlu login.', 401, ['WWW-Authenticate' => 'Basic realm="Worklog"']);
    }
}

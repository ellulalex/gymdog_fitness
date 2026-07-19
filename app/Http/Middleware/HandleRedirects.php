<?php

namespace App\Http\Middleware;

use App\Models\Redirect as RedirectModel;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies the redirects table before routing, so old WordPress URLs 301 to
 * their new home (spec §10). Matches with and without a trailing slash.
 */
class HandleRedirects
{
    public function handle(Request $request, Closure $next): Response
    {
        $path = $request->getPathInfo(); // includes leading slash, preserves trailing

        $candidates = array_unique([$path, rtrim($path, '/'), rtrim($path, '/').'/']);

        $redirect = RedirectModel::whereIn('from_path', $candidates)->first();

        if ($redirect) {
            return redirect($redirect->to_path, $redirect->status_code);
        }

        return $next($request);
    }
}

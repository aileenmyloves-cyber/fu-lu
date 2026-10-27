<?php

namespace App\Http\Middleware\Admin;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleGuard
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
       if(auth_has_no_role()) {
            Auth::logout();
            return back()->with(['error' => ['Sorry! You don\'t have permission to access admin dashboard.']]);
            exit;
        }
        $request_route = $request->route()->getName();


        if(auth_is_super_admin() === false) {

            if(!in_array($request_route, permission_skip()) && auth_admin_incomming_permission() === false)  abort(404);
        }

        return $next($request);
    }
}

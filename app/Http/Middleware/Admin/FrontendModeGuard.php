<?php

namespace App\Http\Middleware\Admin;

use App\Providers\Admin\BasicSettingsProvider;
use Closure;
use Illuminate\Http\Request;

class FrontendModeGuard
{
    public function handle(Request $request, Closure $next)
    {
        $basic_settings = BasicSettingsProvider::get();
        $frontend_mode  = $basic_settings->frontend_mode ?? 0; // 0 = Classic, 1 = Next.js

        // Web request
        if ($frontend_mode == 1) {
            // Next.js is active — redirect away from Blade routes
            $next_js_url = route('admin.login');
            return redirect($next_js_url);
        }

        return $next($request);
    }
}

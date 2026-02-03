<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        // اللغات المدعومة
        $supported = ['ar', 'en'];
        $fallback  = config('app.fallback_locale', 'en');

        // أولوية تحديد اللغة: Session → حقل المستخدم → Cookie → تفضيل المتصفح → Fallback
        $locale =
            Session::get('locale') ??
            optional($request->user())->locale ??
            $request->cookie('locale') ??
            $request->getPreferredLanguage($supported) ??
            $fallback;

        if (! in_array($locale, $supported, true)) {
            $locale = $fallback;
        }

        App::setLocale($locale);
        // إن أردت تعريب العبارات النسبية للتواريخ
        \Carbon\Carbon::setLocale($locale);

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * لغة الرسائل حسب نوع الحساب:
 *   الأدمن (وسوبر أدمن)  => عربي  (رسائل التحقق + الإشعارات)
 *   التاجر والزائر        => إنجليزي
 * الرسائل المشتركة بتتكتب بالإنجليزي في الكود وبتتترجم من lang/ar.json.
 */
class SetLocaleByRole
{
    public function handle(Request $request, Closure $next): Response
    {
        app()->setLocale($request->user()?->isAdmin() ? 'ar' : 'en');

        return $next($request);
    }
}

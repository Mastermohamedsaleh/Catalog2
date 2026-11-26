<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * هيدرز أمان بسيطة على كل الردود.
 *  - X-Frame-Options: الموقع مايتفتحش جوه iframe في موقع تاني (clickjacking).
 *  - X-Content-Type-Options: المتصفح مايخمّنش نوع الملف (يمنع تنفيذ ملف مرفوع على إنه سكريبت).
 *  - Referrer-Policy: مانبعتش لينكات الصفحات الداخلية لمواقع تانية.
 *  - HSTS: بس في production وعلى HTTPS، عشان المتصفح يفضل يفتح الموقع بـ https.
 * مفيش Content-Security-Policy عن قصد: الصفحات فيها سكريبتات inline و CDN فهيكسرها.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        if (app()->isProduction() && $request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000');
        }

        return $response;
    }
}

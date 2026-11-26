<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Observers\CatalogObserver;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        // كلمة السر في كل مكان (تسجيل، تغيير، إعادة تعيين، أدمن جديد): 8 خانات على الأقل + حروف + أرقام.
        \Illuminate\Validation\Rules\Password::defaults(fn () => \Illuminate\Validation\Rules\Password::min(8)->letters()->numbers());

        // خلف Cloudflare السيرفر بيشوف طلبات HTTP عادية جاية من البروكسي. من غير السطور دي
        // روابط الصور بتطلع http:// (mixed content) وكل الزوار بيظهروا بنفس الـ IP.
        if ($proxies = config('app.trusted_proxies')) {
            \Illuminate\Http\Middleware\TrustProxies::at($proxies === '*' ? '*' : array_map('trim', explode(',', $proxies)));
        }

        // أي تعديل من الأدمن على المنتجات أو الأقسام أو المقاسات أو الصور = كاش الكتالوج يتمسح تلقائياً.
        foreach ([Product::class, ProductVariant::class, ProductImage::class, Category::class] as $model) {
            $model::observe(CatalogObserver::class);
        }
    }
}

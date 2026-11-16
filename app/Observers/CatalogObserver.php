<?php

namespace App\Observers;

use App\Support\CatalogCache;
use Illuminate\Database\Eloquent\Model;

/**
 * بيراقب المنتجات والمقاسات والصور والأقسام.
 * أي حفظ أو حذف على أي واحد فيهم = مسح كاش الكتالوج تلقائياً.
 *
 * ليه Observer؟ عشان مش لازم نعدّل كل كنترولرات الأدمن ونفتكر نمسح الكاش في كل واحد،
 * ولو حد أضاف كنترولر جديد بكرة، الكاش هيتمسح لوحده.
 *
 * ملحوظة: تغيير المخزون من الأوردرات بيتم بأوامر مباشرة على الداتابيز (decrement/increment) ومبيشغّلش الـ Observer،
 * وده مقصود، لأنه بيتعامل بشكل منفصل في StockService (مسح منتج واحد بس).
 */
class CatalogObserver
{
    public function saved(Model $model): void
    {
        CatalogCache::flushAll();
    }

    public function deleted(Model $model): void
    {
        CatalogCache::flushAll();
    }
}
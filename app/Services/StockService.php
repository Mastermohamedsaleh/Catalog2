<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\CatalogCache;
use Illuminate\Validation\ValidationException;

/**
 * المكان الوحيد اللي بيغيّر المخزون.
 *
 * - reserve(): أمر UPDATE واحد "...WHERE stock >= qty" فالمخزون عمره ما ينزل تحت الصفر،
 *   وطلبين في نفس اللحظة مستحيل ياخدوا آخر قطعة سوا.
 * - release(): زيادة مباشرة (increment) من غير ما نقرا الرقم القديم ونرجع نكتبه.
 * الاتنين لازم يتنادوا جوه DB transaction.
 *
 * الجديد: كل تغيير في المخزون بيمسح كاش المنتج ده بس (مش الكتالوج كله) من هنا.
 * ليه هنا؟ عشان المخزون بيتغير من أكتر من مكان (أوردر جديد، إلغاء من التاجر، إلغاء من الأدمن)
 * وكلهم بيعدّوا على الكلاس ده، فمفيش مكان ممكن حد ينسى فيه.
 * المسح نفسه بيحصل بعد الـ commit وجوه try/catch في CatalogCache، فلو Redis وقع الأوردر ما يفشلش.
 */
class StockService
{
    public function reserve(int $productId, ?int $variantId, int $qty): void
    {
        $now = now();

        if ($variantId) {
            $affected = ProductVariant::whereKey($variantId)
                ->where('product_id', $productId)
                ->where('stock', '>=', $qty)
                ->decrement('stock', $qty, ['updated_at' => $now]);

            if ($affected === 0) {
                throw ValidationException::withMessages([
                    'stock' => 'عذراً، نفد مخزون هذا المقاس بالكمية المطلوبة حالياً!',
                ]);
            }

            $this->syncParentStock($productId);
            return;
        }

        $affected = Product::whereKey($productId)
            ->where('stock', '>=', $qty)
            ->decrement('stock', $qty, ['updated_at' => $now]);

        if ($affected === 0) {
            throw ValidationException::withMessages([
                'stock' => 'عذراً، نفد مخزون هذا المنتج بالكمية المطلوبة حالياً!',
            ]);
        }

        CatalogCache::forgetProduct($productId);
    }

    public function release(Order $order): void
    {
        $now = now();

        if ($order->product_variant_id) {
            ProductVariant::whereKey($order->product_variant_id)
                ->increment('stock', $order->stock, ['updated_at' => $now]);
            $this->syncParentStock($order->product_id);
            return;
        }

        Product::whereKey($order->product_id)
            ->increment('stock', $order->stock, ['updated_at' => $now]);

        CatalogCache::forgetProduct($order->product_id);
    }

    /** مخزون المنتج اللي له مقاسات = مجموع مخزون المقاسات دايماً. */
    public function syncParentStock(int $productId): void
    {
        $sum = (int) ProductVariant::where('product_id', $productId)->sum('stock');

        Product::whereKey($productId)->update(['stock' => $sum, 'updated_at' => now()]);

        // المنتج اللي له مقاسات بيعدّي من هنا في الحجز والإرجاع، فالمسح هنا بيغطي الحالتين.
        CatalogCache::forgetProduct($productId);
    }
}

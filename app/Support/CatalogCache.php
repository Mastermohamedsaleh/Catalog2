<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * المكان الوحيد اللي بيتحكم في كاش الكتالوج (الأقسام وصفحات المنتجات).
 *
 * ليه الكلاس ده موجود؟
 *  1) بدل ما كل كنترولر يكتب Cache::forget بطريقته (ودايماً حد بينسى واحد)، كل حاجة بتعدّي من هنا.
 *  2) بنستخدم "رقم نسخة" (version) في اسم كل مفتاح بدل الـ tags. السبب: الـ tags بتشتغل مع Redis بس،
 *     ولو الكاش اتغيّر لـ database أو file كل الأوردرات كانت هتفشل. رقم النسخة بيشتغل على أي نوع كاش.
 *  3) الكاش عمره ما يوقّف الموقع: لو Redis وقع، الصفحات بتتحمّل من الداتابيز عادي والأوردرات بتكمّل.
 */
class CatalogCache
{
    private const VERSION_KEY = 'catalog:version';

    /** رقم النسخة الحالي. لما يتغيّر، كل المفاتيح القديمة بتبقى "يتيمة" وتنتهي لوحدها بالـ TTL. */
    private static function version(): string
    {
        try {
            $v = Cache::get(self::VERSION_KEY);
            if ($v === null) {
                $v = (string) microtime(true);
                Cache::forever(self::VERSION_KEY, $v);
            }
            return (string) $v;
        } catch (Throwable $e) {
            return '0'; // الكاش واقع: نكمّل بنسخة ثابتة، ومفيش حاجة هتتخزّن أصلاً
        }
    }

    private static function key(string $suffix): string
    {
        return 'catalog:' . self::version() . ':' . $suffix;
    }

    /** مفتاح صفحة تفاصيل منتج واحد. */
    public static function productKey(int|string $id): string
    {
        return 'product:' . $id;
    }

    /**
     * جيب من الكاش أو احسب واحفظ.
     * اتكتبت يدوي (get ثم put) بدل Cache::remember عشان:
     *  - لو Redis وقع نرجع نحسب من الداتابيز بدل ما الصفحة تدّي 500.
     *  - لو الدالة نفسها رمت خطأ (زي 404 firstOrFail) الخطأ يطلع زي ما هو وما يتخزّنش.
     */
    public static function remember(string $suffix, $ttl, Closure $callback): mixed
    {
        $key = self::key($suffix);

        try {
            $hit = Cache::get($key);
            if ($hit !== null) {
                return $hit;
            }
        } catch (Throwable $e) {
            Log::warning('Catalog cache read failed', ['error' => $e->getMessage()]);
            return $callback();
        }

        $value = $callback();

        try {
            Cache::put($key, $value, $ttl);
        } catch (Throwable $e) {
            Log::warning('Catalog cache write failed', ['error' => $e->getMessage()]);
        }

        return $value;
    }

    /**
     * امسح كاش منتج واحد بس. بتتنادى مع كل أوردر/إلغاء (المخزون بيتغير) فمينفعش نمسح الكتالوج كله كل مرة.
     * afterCommit: المسح بيحصل بعد ما الترانزكشن يخلص فعلاً، عشان طلب تاني ما يكتبش النسخة القديمة تاني في الكاش.
     */
    public static function forgetProduct(int|string $id): void
    {
        DB::afterCommit(function () use ($id) {
            try {
                Cache::forget(self::key(self::productKey($id)));
            } catch (Throwable $e) {
                Log::warning('Catalog cache forget failed', ['error' => $e->getMessage()]);
            }
        });
    }

    /**
     * امسح كل الكتالوج (الأقسام + كل المنتجات) بتغيير رقم النسخة.
     * بتتنادى لما الأدمن يعدّل/يحذف منتج أو قسم أو مقاس أو صورة. دي حاجة نادرة فمفيش مشكلة إنها تمسح الكل.
     */
    public static function flushAll(): void
    {
        DB::afterCommit(function () {
            try {
                Cache::forever(self::VERSION_KEY, (string) microtime(true) . bin2hex(random_bytes(3)));
            } catch (Throwable $e) {
                Log::warning('Catalog cache flush failed', ['error' => $e->getMessage()]);
            }
        });
    }
}

<?php

namespace App\Support;

use App\Models\Order;
use Illuminate\Support\Collection;

/**
 * رسوم التوصيل (30 درهم) بتتحسب مرة واحدة لكل "توصيلة" مش لكل أوردر.
 *
 * التوصيلة الواحدة = نفس التاجر + نفس رقم العميل + نفس الإمارة + نفس العنوان، وأوردرات لسه شغالة (pending / processing)
 * ومتسجلة خلال المدة المحددة (الافتراضي 24 ساعة، من config/shop.php). أول أوردر بيدفع الرسوم، والأوردرات اللي بعده في نفس التوصيلة رسومها 0
 * (لأن المندوب هيوصّلهم كلهم في نفس الزيارة).
 *
 * لو الأوردر اللي دافع الرسوم اتلغى أو اتعمله مرتجع، الرسوم بتتنقل لأقدم أوردر شغّال لسه في نفس التوصيلة،
 * عشان العميل ميتوصلش له أوردر من غير رسوم توصيل.
 */
class DeliveryFee
{
    /** مدة التوصيلة بالدقايق (من config/shop.php). */
    public static function windowMinutes(): int
    {
        return max(1, (int) config('shop.delivery_window_minutes', 1440));
    }

    /** مفتاح العنوان: حروف وأرقام بس وصغيرة، فاختلاف المسافات والعلامات مبيفرقش. */
    public static function addressKey(?string $address): string
    {
        return (string) preg_replace('/[^\p{L}\p{N}]+/u', '', mb_strtolower((string) $address));
    }

    /** آخر 9 أرقام من الموبايل (بيتجاهل 0 / 971 / +). */
    public static function phoneKey(?string $phone): string
    {
        $d = preg_replace('/\D+/', '', (string) $phone);

        return substr($d, -9);
    }

    public static function groupKey(?string $phone, ?string $emirate, ?string $address): string
    {
        return self::phoneKey($phone) . '|' . mb_strtolower(trim((string) $emirate)) . '|' . self::addressKey($address);
    }

    /** أوردرات التاجر الشغّالة اللي بتدفع رسوم توصيل (آخر 24 ساعة). */
    public static function activePayers(int $userId): Collection
    {
        return Order::where('user_id', $userId)
            ->whereIn('status', ['pending', 'processing'])
            ->where('delivery_fee', '>', 0)
            ->where('created_at', '>=', now()->subMinutes(self::windowMinutes()))
            ->get(['id', 'customer_phone', 'emirate', 'customer_address']);
    }

    /** مفاتيح التوصيلات اللي رسومها اتدفعت فعلاً (للمعاينة في فورم التاجر). */
    public static function paidKeys(int $userId): array
    {
        return self::activePayers($userId)
            ->map(fn ($o) => self::groupKey($o->customer_phone, $o->emirate, $o->customer_address))
            ->unique()->values()->all();
    }

    /** الرسوم اللي لازم تتسجل على أوردر جديد (بالسنت-فورمات العادي "30.00" أو "0.00"). */
    public static function forNewOrder(int $userId, ?string $phone, ?string $emirate, ?string $address): string
    {
        $key = self::groupKey($phone, $emirate, $address);
        $paid = in_array($key, self::paidKeys($userId), true);

        return $paid ? Money::fromCents(0) : Money::fromCents(Money::toCents(config('shop.delivery_fee')));
    }

    /**
     * الأوردر ده اتلغى/اترجع: لو كان هو اللي دافع الرسوم، انقلها لأقدم أوردر شغّال في نفس التوصيلة.
     * لازم تتنادى جوه نفس الـ transaction بعد ما حالة الأوردر تتغير.
     */
    public static function releaseFeeFrom(Order $order): void
    {
        if (Money::toCents($order->delivery_fee ?? 0) <= 0) {
            return;
        }

        $key = self::groupKey($order->customer_phone, $order->emirate, $order->customer_address);

        $heir = Order::where('user_id', $order->user_id)
            ->where('id', '!=', $order->id)
            ->whereIn('status', ['pending', 'processing'])
            ->where('delivery_fee', '<=', 0)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->first(fn ($o) => self::groupKey($o->customer_phone, $o->emirate, $o->customer_address) === $key);

        if ($heir) {
            $heir->delivery_fee = $order->delivery_fee;
            $heir->save();
            $order->delivery_fee = Money::fromCents(0);
        }
    }
}

<?php

namespace App\Support;

/**
 * التعامل مع أرقام الموبايل الإماراتية في مكان واحد.
 *
 * ليه كلاس لوحده؟
 *  - الناس بتكتب الرقم بطرق مختلفة: 0501234567 أو +971 50 123 4567 أو 00971501234567 أو بأرقام عربية.
 *    لو خزّنا كل شكل زي ما اتكتب، نفس الرقم هيتسجل مرتين ("unique" مش هيمسكه) والبحث في الأوردرات هيتلخبط.
 *  - فبنحوّل أي شكل للصيغة المحلية الموحّدة 05XXXXXXXX (10 أرقام) قبل التحقق والحفظ.
 *  - تحقق الصيغة (regex) واحد لكل المشروع: التسجيل، البروفايل، أوردر التاجر، سحب المحفظة.
 */
class Phone
{
    /** موبايل إماراتي محلي: 05 + (0 أو 2 أو 4 أو 5 أو 6 أو 8) + 7 أرقام. (اتصالات 50/54/56، دو 52/55/58) */
    public const MOBILE_REGEX = '/^05[024568][0-9]{7}$/';

    /** القاعدة الجاهزة للـ validation: ['required', 'string', Phone::rule()] */
    public static function rule(): string
    {
        return 'regex:' . self::MOBILE_REGEX;
    }

    /** الرسالة اللي بتظهر لو الرقم غلط. */
    public static function message(): string
    {
        return 'رقم الموبايل غير صحيح. اكتب رقماً إماراتياً مثل 0501234567 أو +971501234567.';
    }

    /**
     * حوّل أي شكل مكتوب للصيغة المحلية 05XXXXXXXX.
     * لو الرقم مش شبه موبايل إماراتي نرجّعه منضّف بس (من غير مسافات)، والـ validation هو اللي يرفضه.
     */
    public static function normalize(?string $input): ?string
    {
        if ($input === null) {
            return null;
        }

        // أرقام عربية (٠١٢...) وفارسية (۰۱۲...) لأرقام لاتينية
        $s = strtr($input, [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        ]);

        // شيل المسافات والشرطات والنقط والأقواس
        $s = preg_replace('/[\s\-\.\(\)]/u', '', $s) ?? '';

        if (str_starts_with($s, '+')) {
            $s = substr($s, 1);
        } elseif (str_starts_with($s, '00')) {
            $s = substr($s, 2);
        }

        // 971XXXXXXXXX  ->  0XXXXXXXXX  (ولو كتب الصفر بعد الكود: +971 (0)50... نشيله)
        if (str_starts_with($s, '971')) {
            $rest = substr($s, 3);
            if (str_starts_with($rest, '0')) {
                $rest = substr($rest, 1);
            }
            return '0' . $rest;
        }

        // 501234567 (من غير الصفر)  ->  0501234567
        if (preg_match('/^5[0-9]{8}$/', $s)) {
            return '0' . $s;
        }

        return $s;
    }

    public static function isMobile(?string $normalized): bool
    {
        return $normalized !== null && preg_match(self::MOBILE_REGEX, $normalized) === 1;
    }

    /** 0501234567 -> 971501234567 (للواتساب wa.me ولروابط الاتصال). */
    public static function international(string $local): string
    {
        return '971' . ltrim($local, '0');
    }

    /** 971501234567 -> "+971 50 123 4567" لعرض الرقم في الموقع. أي شكل تاني بيرجع زي ما هو مع +. */
    public static function display(string $digits): string
    {
        if (preg_match('/^971([0-9]{2})([0-9]{3})([0-9]{4})$/', $digits, $m)) {
            return "+971 {$m[1]} {$m[2]} {$m[3]}";
        }

        return '+' . ltrim($digits, '+');
    }
}

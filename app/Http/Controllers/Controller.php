<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

abstract class Controller
{
    /**
     * العمليات الحساسة (حذف حساب، تصفير محفظة) لازم الأدمن يكتب كلمة السر بتاعته تاني.
     * ليه؟ لو حد فتح الجهاز والأدمن سايب الجلسة مفتوحة، ماقدرش يمسح حاجة بضغطة زرار.
     * بتفشل برسالة عربي (الأدمن بس بيشوف الشاشات دي): "كلمة السر الحالية غير صحيحة".
     */
    protected function requirePasswordConfirmation(Request $request): void
    {
        $request->validate(['confirm_password' => ['required', 'current_password']]);
    }
}

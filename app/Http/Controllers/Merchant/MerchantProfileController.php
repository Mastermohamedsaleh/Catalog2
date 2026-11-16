<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MerchantProfileController extends Controller
{
    // عرض صفحة بروفايل التاجر مع الإحصائيات والرصيد
    public function edit()
    {
        $user = auth()->user();

        // حساب رصيد المحفظة وإحصائيات الطلبات الخاصة بالتاجر
        // (تأكد من تعديل أسماء الـ Relations أو الجداول حسب هيكل مشروعك)
        $balance = $user->wallet_balance ?? 0.00; // أو من جدول المحفظة
        $ordersTotal = $user->orders()->count();
        $ordersDelivered = $user->orders()->where('status', 'delivered')->count();

        // شروط حذف الحساب (مثلاً: لا يوجد أوردرات سابقة أو رصيد في المحفظة)
        $canDelete = ($ordersTotal === 0 && $balance <= 0);

        return view('frontend.merchant-profile', compact('user', 'balance', 'ordersTotal', 'ordersDelivered', 'canDelete'));
    }

    // تحديث المعلومات الأساسية
    public function updateInfo(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            // لو حابب تضيف رقم الهاتف لو موجود في الـ _form-info
            'phone' => ['nullable', 'string', 'max:20'],
        ], [
            'name.required'  => 'الاسم مطلوب.',
            'email.required' => 'البريد الإلكتروني مطلوب.',
            'email.email'    => 'صيغة البريد الإلكتروني غير صحيحة.',
            'email.unique'   => 'هذا البريد مستخدم مسبقاً.',
        ]);

        $user->update($request->only(['name', 'email', 'phone']));

        return back()->with('success', 'تم تحديث معلومات الحساب بنجاح!');
    }

    // تغيير كلمة المرور
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', function ($attribute, $value, $fail) {
                if (!Hash::check($value, auth()->user()->password)) {
                    $fail('كلمة المرور الحالية غير صحيحة.');
                }
            }],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'current_password.required' => 'كلمة المرور الحالية مطلوبة.',
            'password.required'         => 'كلمة المرور الجديدة مطلوبة.',
            'password.min'              => 'كلمة المرور يجب ألا تقل عن 8 أحرف.',
            'password.confirmed'        => 'تأكيد كلمة المرور غير متطابق.',
        ]);

        auth()->user()->update([
            'password' => Hash::make($request->password),
        ]);

        return back()->with('success', 'تم تغيير كلمة المرور بنجاح!');
    }

    // حذف الحساب (في حال انطبقت الشروط)
    public function destroy(Request $request)
    {
        $user = auth()->user();

        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ], [
            'password.required' => 'يرجى إدخال كلمة المرور لتأكيد الحذف.',
            'current_password'  => 'كلمة المرور غير صحيحة.',
        ]);

        // التحقق مرة أخرى من إمكانية الحذف أماناً
        $canDelete = ($user->orders()->count() === 0 && ($user->wallet_balance ?? 0) <= 0);

        if (!$canDelete) {
            return back()->with('error', 'لا يمكن حذف الحساب لوجود سجلات مالية أو أوردرات سابقة.');
        }

        auth()->logout();
        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('success', 'تم حذف الحساب بنجاح.');
    }
}
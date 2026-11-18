<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     *
     * الكود الأصلي كان بيعمل back() وبيحط رسالة بمفتاح "status" مفيش صفحة بتعرضه، فالمستخدم كان بيتحوّل
     * من غير أي رسالة (وأحياناً للصفحة الرئيسية لو المتصفح ما بعتش Referer).
     * دلوقتي:
     *  - بنرجّع المستخدم صراحةً لصفحة البروفايل بتاعته (أدمن أو تاجر) بدل back().
     *  - رسالة النجاح بمفتاح "success" وبتظهر toast أخضر في الـ layout.
     *  - لو فيه خطأ: بيظهر تحت الحقل (bag اسمه updatePassword) + toast أحمر بأول خطأ.
     */
    public function update(Request $request): RedirectResponse
    {
        $profile = $request->user()->isAdmin() ? 'admin.profile' : 'merchant.profile';

        $validator = Validator::make($request->all(), [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ], [
            'current_password.current_password' => 'The current password is incorrect.',
            'password.confirmed' => 'The new password and its confirmation do not match.',
        ]);

        if ($validator->fails()) {
            return redirect()->route($profile)
                ->withErrors($validator, 'updatePassword')
                ->with('error', $validator->errors()->first());
        }

        $request->user()->update([
            'password' => Hash::make($request->input('password')),
        ]);

        return redirect()->route($profile)->with('success', 'Password updated successfully.');
    }
}
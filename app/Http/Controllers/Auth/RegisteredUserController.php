<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Phone;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        // نوحّد شكل الرقم قبل التحقق: +971 50 123 4567 و 00971501234567 و 0501234567 كلهم نفس الرقم.
        // من غير ده نفس الشخص يقدر يسجّل مرتين بأشكال مختلفة و"unique" مش هيمسكه.
        $request->merge(['phone' => Phone::normalize($request->input('phone'))]);

        // الاسم الأول والأخير: نشيل المسافات الزيادة، وبعدين نتحقق إنهم حروف بس (عربي أو إنجليزي) من غير أرقام أو رموز.
        $request->merge([
            'first_name' => preg_replace('/\s+/u', ' ', trim((string) $request->input('first_name'))),
            'last_name'  => preg_replace('/\s+/u', ' ', trim((string) $request->input('last_name'))),
        ]);

        $nameRule = ['required', 'string', 'min:2', 'max:50', "regex:/^[\p{L}\p{M}][\p{L}\p{M} .'’\-]*$/u"];

        $request->validate([
            'first_name' => $nameRule,
            'last_name'  => $nameRule,
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'phone' => ['required', 'string', Phone::rule(), 'unique:'.User::class],
            'password' => [
                'required', 
                'confirmed', 
                Rules\Password::min(8)
                    ->letters()
                    ->numbers()
            ],
        ], [
            'first_name.required' => 'Please enter your first name.',
            'first_name.min'      => 'First name must be at least 2 letters.',
            'first_name.regex'    => 'First name can only contain letters.',
            'last_name.required'  => 'Please enter your last name.',
            'last_name.min'       => 'Last name must be at least 2 letters.',
            'last_name.regex'     => 'Last name can only contain letters.',
            'phone.required' => 'رقم الهاتف مطلوب.',
            'phone.regex'    => Phone::message(),
            'phone.unique'   => 'رقم الهاتف مستخدم من قبل، يرجى استخدام رقم آخر.',
        ]);

        $user = User::create([
            'name' => $request->first_name . ' ' . $request->last_name,
            'email' => $request->email,
            'phone' => $request->phone, // محفوظ بالصيغة الموحدة 05XXXXXXXX
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user));

        Auth::login($user);

        if ($user->isAdmin()) {
            return redirect()->intended(route('admin.dashboard'));
        }

        return redirect()->intended(route('catalog.index'));
    }
}
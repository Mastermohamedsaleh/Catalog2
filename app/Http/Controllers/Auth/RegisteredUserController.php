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

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
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
            'phone.required' => 'رقم الهاتف مطلوب.',
            'phone.regex'    => Phone::message(),
            'phone.unique'   => 'رقم الهاتف مستخدم من قبل، يرجى استخدام رقم آخر.',
        ]);

        $user = User::create([
            'name' => $request->name,
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
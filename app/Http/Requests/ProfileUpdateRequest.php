<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Support\Phone;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /** نوحّد شكل الرقم قبل التحقق (نفس منطق التسجيل) عشان نفس الرقم ما يتسجلش بشكلين. */
    protected function prepareForValidation(): void
    {
        if ($this->has('phone')) {
            $this->merge(['phone' => Phone::normalize((string) $this->input('phone'))]);
        }
    }

    /**
     * Only name, email and phone can be changed here. Role / is_super are never accepted.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'phone' => [
                'required',
                'string',
                Phone::rule(),
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => Phone::message(),
            'phone.unique' => 'This phone number is already used by another account.',
            'email.unique' => 'This email is already used by another account.',
        ];
    }
}
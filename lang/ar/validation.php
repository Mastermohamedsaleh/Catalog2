<?php

/*
| رسائل التحقق بالعربي. بتظهر لحسابات الأدمن بس (SetLocaleByRole بيحط اللغة 'ar' للأدمن).
| التاجر والزوار بيشوفوا الإنجليزي الافتراضي. أي رسالة ناقصة هنا بترجع للإنجليزي تلقائياً.
*/
return [
    'accepted'    => 'يجب قبول :attribute.',
    'array'       => 'حقل :attribute يجب أن يكون قائمة.',
    'boolean'     => 'قيمة :attribute يجب أن تكون صح أو خطأ.',
    'confirmed'   => 'تأكيد :attribute غير مطابق.',
    'current_password' => 'كلمة السر الحالية غير صحيحة.',
    'date'        => ':attribute ليس تاريخاً صحيحاً.',
    'dimensions'  => 'أبعاد الصورة في :attribute غير مسموحة (الحد الأقصى 6000×6000 بكسل).',
    'distinct'    => 'حقل :attribute فيه قيمة مكررة.',
    'email'       => 'يرجى إدخال بريد إلكتروني صحيح في :attribute.',
    'exists'      => ':attribute المختار غير صحيح.',
    'image'       => 'يجب أن يكون :attribute صورة.',
    'in'          => ':attribute المختار غير صحيح.',
    'integer'     => 'يجب أن يكون :attribute رقماً صحيحاً.',
    'lowercase'   => 'يجب أن يكون :attribute بحروف صغيرة.',
    'mimes'       => 'يجب أن يكون :attribute بصيغة: :values.',
    'numeric'     => 'يجب أن يكون :attribute رقماً.',
    'regex'       => 'صيغة :attribute غير صحيحة.',
    'required'    => 'حقل :attribute مطلوب.',
    'required_if' => 'حقل :attribute مطلوب في هذه الحالة.',
    'same'        => 'يجب أن يتطابق :attribute مع :other.',
    'string'      => 'يجب أن يكون :attribute نصاً.',
    'unique'      => ':attribute مستخدم من قبل.',
    'url'         => 'يجب أن يكون :attribute رابطاً صحيحاً.',
    'uploaded'    => 'فشل رفع :attribute.',

    'between' => [
        'numeric' => 'يجب أن يكون :attribute بين :min و :max.',
        'string'  => 'يجب أن يكون :attribute بين :min و :max حرفاً.',
    ],
    'min' => [
        'numeric' => 'يجب ألا يقل :attribute عن :min.',
        'string'  => 'يجب ألا يقل :attribute عن :min حرفاً.',
        'array'   => 'يجب ألا يقل :attribute عن :min عناصر.',
        'file'    => 'يجب ألا يقل حجم :attribute عن :min كيلوبايت.',
    ],
    'max' => [
        'numeric' => 'يجب ألا يزيد :attribute عن :max.',
        'string'  => 'يجب ألا يزيد :attribute عن :max حرفاً.',
        'array'   => 'يجب ألا يزيد :attribute عن :max عناصر.',
        'file'    => 'يجب ألا يزيد حجم :attribute عن :max كيلوبايت.',
    ],

    'password' => [
        'letters'       => 'يجب أن تحتوي :attribute على حرف واحد على الأقل.',
        'mixed'         => 'يجب أن تحتوي :attribute على حرف كبير وحرف صغير.',
        'numbers'       => 'يجب أن تحتوي :attribute على رقم واحد على الأقل.',
        'symbols'       => 'يجب أن تحتوي :attribute على رمز واحد على الأقل.',
        'uncompromised' => ':attribute ظهرت في تسريب بيانات. اختر كلمة سر مختلفة.',
    ],

    'attributes' => [
        'name'                 => 'الاسم',
        'email'                => 'البريد الإلكتروني',
        'phone'                => 'رقم الهاتف',
        'password'             => 'كلمة السر',
        'current_password'     => 'كلمة السر الحالية',
        'confirm_password'     => 'كلمة السر للتأكيد',
        'category_id'          => 'القسم',
        'parent_id'            => 'القسم الرئيسي',
        'price'                => 'السعر',
        'stock'                => 'المخزون',
        'sku'                  => 'الكود',
        'description'          => 'الوصف',
        'main_image'           => 'الصورة الرئيسية',
        'images'               => 'صور المعرض',
        'image'                => 'الصورة',
        'video_url'            => 'لينك الفيديو',
        'video_download_url'   => 'لينك التنزيل',
        'status'               => 'الحالة',
        'cancellation_reason'  => 'السبب',
        'variants'             => 'المقاسات',
        'variants.*.size'      => 'المقاس',
        'variants.*.color'     => 'اللون',
        'variants.*.stock'     => 'مخزون المقاس',
        'variants.*.price_modifier' => 'فرق سعر المقاس',
    ],
];

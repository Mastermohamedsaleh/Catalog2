<?php

/*
|--------------------------------------------------------------------------
| إعدادات المتجر (العملة وبيانات التواصل)
|--------------------------------------------------------------------------
| كل حاجة بتتغير من الـ .env من غير ما تلمس أي صفحة:
|   SHOP_CURRENCY   العملة اللي بتظهر جنب كل سعر في الموقع (الدرهم الإماراتي AED).
|   SHOP_PHONE      رقم التليفون بالصيغة الدولية من غير + ولا مسافات (971501234567).
|   SHOP_WHATSAPP   رقم الواتساب بنفس الصيغة. لو سبته فاضي بيستخدم نفس رقم التليفون.
|
| ملحوظة: الأسعار بتتخزن في الداتابيز بأرقام عشرية (خانتين) وده مناسب للدرهم لأنه بيتقسم لـ 100 فلس.
*/

return [
    'currency' => env('SHOP_CURRENCY', 'AED'),

    'phone'    => env('SHOP_PHONE', '971500000000'),
    'whatsapp' => env('SHOP_WHATSAPP') ?: env('SHOP_PHONE', '971500000000'),
    'email'    => env('SHOP_EMAIL', 'info@catalogpro.com'),

    'country'   => env('SHOP_COUNTRY', 'the UAE'),
    'city'      => env('SHOP_CITY', 'Dubai, UAE'),
    'address'   => env('SHOP_ADDRESS', 'Office / Street name, Dubai, United Arab Emirates'),
    'map_query' => env('SHOP_MAP_QUERY', 'Dubai, United Arab Emirates'),
];
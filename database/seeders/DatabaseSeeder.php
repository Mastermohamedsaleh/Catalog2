<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * نقطة البداية على الموقع الحقيقي: السوبر أدمن بس. مفيش منتجات ولا تجار ولا بيانات وهمية.
 *   php artisan db:seed --force
 * (البيانات التجريبية للتطوير المحلي موجودة في DemoSeeder وبترفض تشتغل على production.)
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(AdminUserSeeder::class);
    }
}

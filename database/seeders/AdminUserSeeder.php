<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * بيعمل السوبر أدمن الأول مرة واحدة بس. لو الحساب موجود مبيتلمسش (يعني إعادة التشغيل مش هتصفّر باسورده).
 *
 * البيانات من الـ .env (بتتقرا من config/shop.php عشان تشتغل حتى بعد config:cache):
 *   ADMIN_SEED_EMAIL     إيميل الدخول (لو فاضي بيستخدم SHOP_EMAIL)
 *   ADMIN_SEED_PASSWORD  الباسورد. لو فاضي بيتولّد باسورد عشوائي ويتطبع مرة واحدة في الـ terminal.
 *   ADMIN_SEED_NAME      الاسم (اختياري)
 * مفيش باسورد افتراضي نهائياً. بعد أول دخول غيّر الباسورد وامسح ADMIN_SEED_PASSWORD من .env.
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $cfg   = config('shop.super_admin');
        $email = strtolower(trim((string) ($cfg['email'] ?: config('shop.email'))));

        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->command?->error('Set ADMIN_SEED_EMAIL (or SHOP_EMAIL) in .env to a valid email first.');
            return;
        }

        if (User::where('email', $email)->exists()) {
            $this->command?->info("Super admin {$email} already exists, left unchanged.");
            return;
        }

        $password = $cfg['password'] ?: Str::password(20, symbols: false);

        // forceFill: بنحدد الصلاحيات صراحةً هنا بدل ما نعتمد على $fillable.
        $user = new User();
        $user->forceFill([
            'name'              => $cfg['name'] ?: 'Super Admin',
            'email'             => $email,
            'password'          => Hash::make($password),
            'role'              => 'catalog_master_root',
            'is_super'          => true,
            'email_verified_at' => now(),
        ])->save();

        $this->command?->info("Super admin created: {$email}");
        if (! $cfg['password']) {
            $this->command?->warn("Generated password (shown ONCE, change it after login): {$password}");
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    /**
     * Creates the first super admin ONCE (an existing account is never touched, so
     * re-running the seeder cannot reset its password).
     *
     * Set ADMIN_SEED_EMAIL / ADMIN_SEED_PASSWORD in .env. If no password is given, a
     * random one is generated and printed a single time. There is no default password.
     */
    public function run(): void
    {
        $email = env('ADMIN_SEED_EMAIL', 'sec_master@catalogpro.com');

        if (User::where('email', $email)->exists()) {
            $this->command?->info("Super admin {$email} already exists, left unchanged.");
            return;
        }

        $password = env('ADMIN_SEED_PASSWORD') ?: Str::password(20, symbols: false);

        User::create([
            'name'              => 'System Master',
            'email'             => $email,
            'password'          => Hash::make($password),
            'role'              => 'catalog_master_root',
            'is_super'          => true,
            'email_verified_at' => now(),
        ]);

        if (! env('ADMIN_SEED_PASSWORD')) {
            $this->command?->warn("Super admin created: {$email}  password: {$password}  (shown once, change it after login)");
        }
    }
}

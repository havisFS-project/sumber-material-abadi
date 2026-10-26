<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Akun demo. Ganti password sebelum dipakai di luar komputer sendiri.
        $akun = [
            ['name' => 'Admin Toko', 'email' => 'admin@sumbermaterial.test', 'role' => 'admin'],
            ['name' => 'Rina (Kasir)', 'email' => 'kasir@sumbermaterial.test', 'role' => 'kasir'],
        ];

        foreach ($akun as $a) {
            $user = User::firstOrNew(['email' => $a['email']]);
            $user->forceFill([
                'name' => $a['name'],
                'password' => Hash::make('password'),
                'role' => $a['role'],
                'is_active' => true,
                'email_verified_at' => now(),
            ])->save();
        }
    }
}

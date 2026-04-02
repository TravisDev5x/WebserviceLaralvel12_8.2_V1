<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Crea o actualiza el usuario administrador inicial.
     *
     * En producción defina en .env: ADMIN_EMAIL, ADMIN_PASSWORD (y opcionalmente
     * ADMIN_NAME, ADMIN_EMPLOYEE_NUMBER). Si faltan email o contraseña en producción, no se crea nada.
     *
     * En local, si no hay variables, se usa test@example.com / password (solo desarrollo).
     */
    public function run(): void
    {
        $isProduction = app()->environment('production');

        $email = env('ADMIN_EMAIL');
        if ($email === null || $email === '') {
            if ($isProduction) {
                $this->command?->warn('AdminUserSeeder: ADMIN_EMAIL no está definido; no se creó usuario admin.');

                return;
            }
            $email = 'test@example.com';
        }

        $password = env('ADMIN_PASSWORD');
        if ($password === null || $password === '') {
            if ($isProduction) {
                $this->command?->warn('AdminUserSeeder: ADMIN_PASSWORD no está definido; no se creó usuario admin.');

                return;
            }
            $password = 'password';
        }

        $name = env('ADMIN_NAME', 'Administrador');
        $employeeNumber = env('ADMIN_EMPLOYEE_NUMBER', 'ADMIN001');

        User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'employee_number' => $employeeNumber,
                'role' => 'admin',
                'is_active' => true,
                'password' => $password,
                'email_verified_at' => now(),
            ],
        );
    }
}

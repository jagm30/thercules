<?php

namespace Database\Seeders;

use App\Actions\Teams\CreateTeam;
use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Crea la sucursal principal y el primer administrador.
 * Configura en .env: TALLER_SUCURSAL, TALLER_PREFIJO_FOLIO, ADMIN_NAME, ADMIN_EMAIL, ADMIN_PASSWORD.
 * Si no hay ADMIN_PASSWORD se genera una aleatoria y se muestra una sola vez en consola.
 */
class SucursalYAdministradorSeeder extends Seeder
{
    public function run(): void
    {
        Sucursal::firstOrCreate(
            ['nombre' => config('taller.sucursal', 'Matriz')],
            ['prefijo_folio' => strtoupper((string) config('taller.prefijo_folio', 'MTZ'))],
        );

        $email = (string) config('taller.admin_email', 'admin@taller.local');

        if (User::withTrashed()->where('email', $email)->exists()) {
            return;
        }

        $password = config('taller.admin_password') ?: Str::password(14, symbols: false);

        $admin = User::create([
            'name' => config('taller.admin_nombre', 'Administrador'),
            'email' => $email,
            'password' => $password,
            'sucursal_id' => null, // acceso a todas las sucursales
            'activo' => true,
        ]);
        $admin->forceFill(['email_verified_at' => now()])->save();
        $admin->assignRole('administrador');

        // El login redirige al equipo actual; sin uno responde 403.
        app(CreateTeam::class)->handle($admin, $admin->name, isPersonal: true);

        if (! config('taller.admin_password')) {
            $this->command?->warn("Administrador creado: {$email} / {$password}  (cámbiala al entrar)");
        }
    }
}

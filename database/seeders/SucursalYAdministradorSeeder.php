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
            ['nombre' => env('TALLER_SUCURSAL', 'Matriz')],
            ['prefijo_folio' => strtoupper(env('TALLER_PREFIJO_FOLIO', 'MTZ'))],
        );

        $email = env('ADMIN_EMAIL', 'admin@taller.local');

        if (User::withTrashed()->where('email', $email)->exists()) {
            return;
        }

        $password = env('ADMIN_PASSWORD') ?: Str::password(14, symbols: false);

        $admin = User::create([
            'name' => env('ADMIN_NAME', 'Administrador'),
            'email' => $email,
            'password' => $password,
            'sucursal_id' => null, // acceso a todas las sucursales
            'activo' => true,
        ]);
        $admin->forceFill(['email_verified_at' => now()])->save();
        $admin->assignRole('administrador');

        // El login redirige al equipo actual; sin uno responde 403.
        app(CreateTeam::class)->handle($admin, $admin->name, isPersonal: true);

        if (! env('ADMIN_PASSWORD')) {
            $this->command?->warn("Administrador creado: {$email} / {$password}  (cámbiala al entrar)");
        }
    }
}

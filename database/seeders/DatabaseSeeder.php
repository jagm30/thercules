<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Datos iniciales del taller. Todos los seeders son idempotentes:
     * se pueden volver a correr sin duplicar ni sobrescribir lo que el administrador cambió.
     */
    public function run(): void
    {
        $this->call([
            RolesYPermisosSeeder::class,
            SucursalYAdministradorSeeder::class,
            PreferenciasOperacionSeeder::class,
            MarcasYModelosSeeder::class,
            ChecklistRecepcionSeeder::class,
            AngulosFotoSeeder::class,
            ZonasVehiculoSeeder::class,
        ]);
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Tres roles con la matriz de permisos del diseño.
 * El administrador puede ajustar los permisos de un rol desde la aplicación.
 */
class RolesYPermisosSeeder extends Seeder
{
    public const PERMISOS = [
        'usuarios.gestionar',     // alta, baja y roles de usuarios
        'preferencias.editar',    // modo con/sin precios y demás preferencias
        'catalogos.gestionar',    // marcas, modelos, servicios, refacciones, checklist, zonas
        'catalogos.alta_rapida',  // crear marca o modelo no verificado durante la recepción
        'clientes.gestionar',     // clientes y vehículos
        'recepcion.realizar',     // inspección de recepción
        'entrega.realizar',       // inspección de entrega
        'ordenes.ver_todas',
        'ordenes.trabajar',       // diagnóstico, trabajos y refacciones
        'ordenes.cambiar_estado', // qué transiciones: EstadoOrden::rolesQuePuedenEntrar()
        'ordenes.reabrir',
        'precios.ver',            // mecánico: lo decide la preferencia mostrar_precios_a_mecanicos
        'costos.ver',             // costo de compra y utilidad
        'qr.regenerar',
        'reportes.ver',
    ];

    public const MATRIZ = [
        'administrador' => self::PERMISOS,
        'recepcionista' => [
            'catalogos.alta_rapida',
            'clientes.gestionar',
            'recepcion.realizar',
            'entrega.realizar',
            'ordenes.ver_todas',
            'ordenes.cambiar_estado',
            'precios.ver',
        ],
        'mecanico' => [
            'ordenes.trabajar',
            'ordenes.cambiar_estado',
        ],
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISOS as $permiso) {
            Permission::findOrCreate($permiso, 'web');
        }

        foreach (self::MATRIZ as $rol => $permisos) {
            Role::findOrCreate($rol, 'web')->syncPermissions($permisos);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}

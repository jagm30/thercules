<?php

return [
    /*
     * Nombre de la sucursal principal creada por el seeder.
     * Configura en .env: TALLER_SUCURSAL
     */
    'sucursal' => env('TALLER_SUCURSAL', 'Matriz'),

    /*
     * Prefijo del folio para la sucursal principal.
     * Configura en .env: TALLER_PREFIJO_FOLIO
     */
    'prefijo_folio' => env('TALLER_PREFIJO_FOLIO', 'MTZ'),

    /*
     * Datos del primer administrador.
     * Configura en .env: ADMIN_NAME, ADMIN_EMAIL, ADMIN_PASSWORD
     */
    'admin_nombre' => env('ADMIN_NAME', 'Administrador'),
    'admin_email' => env('ADMIN_EMAIL', 'admin@taller.local'),
    'admin_password' => env('ADMIN_PASSWORD'),
];

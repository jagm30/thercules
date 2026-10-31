<?php

namespace App\Support;

use App\Models\PreferenciaOperacion;

/**
 * Lee las preferencias de operación del taller.
 *
 * El valor de la sucursal tiene prioridad sobre el valor general (sucursal_id NULL).
 * Se memoriza por petición; PreferenciaOperacion limpia la memoria al guardarse.
 *
 *   Preferencias::bool('usar_precios', $user->sucursal_id)
 */
final class Preferencias
{
    /** @var array<string, mixed> */
    private static array $memo = [];

    public static function get(string $clave, ?int $sucursalId = null, mixed $default = null): mixed
    {
        $llave = ($sucursalId ?? 'general').'|'.$clave;

        if (! array_key_exists($llave, self::$memo)) {
            $registro = PreferenciaOperacion::query()
                ->where('clave', $clave)
                ->where(function ($q) use ($sucursalId) {
                    $q->whereNull('sucursal_id');
                    if ($sucursalId !== null) {
                        $q->orWhere('sucursal_id', $sucursalId);
                    }
                })
                ->orderByRaw('sucursal_id IS NULL') // primero la de la sucursal
                ->first();

            self::$memo[$llave] = $registro?->valorTipado();
        }

        return self::$memo[$llave] ?? $default;
    }

    public static function bool(string $clave, ?int $sucursalId = null, bool $default = false): bool
    {
        return (bool) self::get($clave, $sucursalId, $default);
    }

    public static function int(string $clave, ?int $sucursalId = null, int $default = 0): int
    {
        return (int) self::get($clave, $sucursalId, $default);
    }

    public static function decimal(string $clave, ?int $sucursalId = null, float $default = 0.0): float
    {
        return (float) self::get($clave, $sucursalId, $default);
    }

    public static function olvidar(): void
    {
        self::$memo = [];
    }
}

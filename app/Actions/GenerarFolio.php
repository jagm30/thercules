<?php

namespace App\Actions;

use App\Models\Sucursal;
use Illuminate\Support\Facades\DB;

/**
 * Folio consecutivo por sucursal (ej. MTZ-000123).
 * El bloqueo de fila evita folios duplicados si dos recepcionistas crean órdenes a la vez.
 * Llamar dentro de la misma transacción que crea la orden.
 */
class GenerarFolio
{
    public function para(Sucursal|int $sucursal): string
    {
        $id = $sucursal instanceof Sucursal ? $sucursal->id : $sucursal;

        return DB::transaction(function () use ($id) {
            $s = Sucursal::query()->lockForUpdate()->findOrFail($id);
            $s->increment('ultimo_folio');

            return sprintf('%s-%06d', $s->prefijo_folio, $s->ultimo_folio);
        });
    }
}

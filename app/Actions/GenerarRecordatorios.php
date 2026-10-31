<?php

namespace App\Actions;

use App\Enums\EstadoRecordatorio;
use App\Models\OrdenServicio;
use App\Support\Preferencias;
use Illuminate\Support\Facades\DB;

/**
 * Al entregar una orden: marca como realizados los recordatorios pendientes de los
 * servicios que se hicieron y programa el siguiente para cada servicio con intervalo.
 */
class GenerarRecordatorios
{
    public function ejecutar(OrdenServicio $orden): void
    {
        if (! Preferencias::bool('recordatorios_activos', $orden->sucursal_id, true)) {
            return;
        }

        $kmSalida = $orden->entrega?->km ?? $orden->recepcion?->km ?? $orden->vehiculo->km_actual;

        DB::transaction(function () use ($orden, $kmSalida) {
            $trabajos = $orden->trabajos()->with('servicio')->whereNotNull('servicio_id')->get();

            foreach ($trabajos as $trabajo) {
                $servicio = $trabajo->servicio;

                $orden->vehiculo->recordatorios()
                    ->pendientes()
                    ->where('servicio_id', $servicio->id)
                    ->update([
                        'estado' => EstadoRecordatorio::Realizado->value,
                        'orden_cumplimiento_id' => $orden->id,
                    ]);

                if (! $servicio->generaRecordatorio()) {
                    continue;
                }

                $orden->vehiculo->recordatorios()->create([
                    'orden_origen_id' => $orden->id,
                    'servicio_id' => $servicio->id,
                    'descripcion' => $servicio->nombre,
                    'fecha_programada' => $servicio->intervalo_meses
                        ? now()->addMonths($servicio->intervalo_meses)->toDateString()
                        : null,
                    'km_programado' => $servicio->intervalo_km ? $kmSalida + $servicio->intervalo_km : null,
                    'estado' => EstadoRecordatorio::Pendiente,
                ]);
            }
        });
    }
}

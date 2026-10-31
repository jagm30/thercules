<?php

namespace App\Actions;

use App\Enums\EstadoInspeccion;
use App\Enums\EstadoOrden;
use App\Enums\TipoInspeccion;
use App\Enums\TipoOrden;
use App\Exceptions\ReglaDeNegocioException;
use App\Models\OrdenServicio;
use App\Models\User;
use App\Models\Vehiculo;
use App\Support\Preferencias;
use Illuminate\Support\Facades\DB;

/**
 * Abre una orden para un vehículo y crea su inspección de recepción en borrador.
 * La orden no podrá avanzar de "recibido" hasta completar esa inspección.
 */
class AbrirOrden
{
    public function __construct(private GenerarFolio $folios = new GenerarFolio) {}

    public function ejecutar(
        Vehiculo $vehiculo,
        User $recepcionista,
        string $motivoIngreso,
        TipoOrden $tipo = TipoOrden::Servicio,
        ?OrdenServicio $garantiaDe = null,
        ?int $sucursalId = null,
    ): OrdenServicio {
        $sucursalId ??= $recepcionista->sucursal_id;

        if ($sucursalId === null) {
            throw new ReglaDeNegocioException('Indica la sucursal que recibe el vehículo.');
        }

        if ($vehiculo->ordenAbierta()->exists()) {
            throw new ReglaDeNegocioException('El vehículo ya tiene una orden abierta.');
        }

        if ($tipo === TipoOrden::Garantia && $garantiaDe === null) {
            throw new ReglaDeNegocioException('Una orden de garantía debe indicar la orden original.');
        }

        return DB::transaction(function () use ($vehiculo, $recepcionista, $motivoIngreso, $tipo, $garantiaDe, $sucursalId) {
            $orden = OrdenServicio::create([
                'sucursal_id' => $sucursalId,
                'folio' => $this->folios->para($sucursalId),
                'vehiculo_id' => $vehiculo->id,
                'cliente_id' => $vehiculo->cliente_id,
                'tipo' => $tipo,
                'orden_garantia_de_id' => $garantiaDe?->id,
                'estado' => EstadoOrden::Recibido,
                // Se congela el modo del taller en este momento.
                'con_precios' => Preferencias::bool('usar_precios', $sucursalId, true),
                'recibido_por_id' => $recepcionista->id,
                'fecha_ingreso' => now(),
                'motivo_ingreso' => $motivoIngreso,
            ]);

            $orden->bitacoraEstados()->create([
                'estado_anterior' => null,
                'estado_nuevo' => EstadoOrden::Recibido,
                'user_id' => $recepcionista->id,
            ]);

            $orden->inspecciones()->create([
                'tipo' => TipoInspeccion::Recepcion,
                'estado' => EstadoInspeccion::Borrador,
                'realizada_por_id' => $recepcionista->id,
                'iniciada_at' => now(),
                'tipo_carroceria' => $vehiculo->modelo->tipo_carroceria,
                'placas' => $vehiculo->placas,
                'sin_placas' => $vehiculo->placas === null,
            ]);

            return $orden;
        });
    }
}

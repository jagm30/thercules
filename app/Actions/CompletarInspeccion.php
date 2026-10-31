<?php

namespace App\Actions;

use App\Enums\EstadoInspeccion;
use App\Enums\TipoAdjunto;
use App\Enums\TipoRespuesta;
use App\Exceptions\ReglaDeNegocioException;
use App\Models\AnguloFoto;
use App\Models\ChecklistItem;
use App\Models\Inspeccion;
use App\Support\Preferencias;
use Illuminate\Support\Facades\DB;

/**
 * Completa una inspección (recepción o entrega) solo si no falta nada obligatorio.
 *
 *   $faltantes = (new CompletarInspeccion)->faltantes($inspeccion); // para pintar la pantalla
 *   (new CompletarInspeccion)->ejecutar($inspeccion);               // lanza excepción con la lista si falta algo
 */
class CompletarInspeccion
{
    /** @return list<string> lo que falta, en lenguaje de recepción. Vacío = se puede completar. */
    public function faltantes(Inspeccion $inspeccion): array
    {
        $f = [];
        $orden = $inspeccion->orden;
        $vehiculo = $orden->vehiculo;
        $sucursal = $orden->sucursal_id;

        // 1. Kilometraje y gasolina
        if ($inspeccion->km === null) {
            $f[] = 'Captura el kilometraje.';
        } elseif ($inspeccion->esRecepcion() && $inspeccion->km < $vehiculo->km_actual) {
            if (! Preferencias::bool('recepcion_permite_km_menor', $sucursal, true)) {
                $f[] = "El kilometraje ({$inspeccion->km}) es menor al registrado ({$vehiculo->km_actual}).";
            } elseif (blank($inspeccion->km_justificacion)) {
                $f[] = "El kilometraje es menor al anterior ({$vehiculo->km_actual}); escribe la justificación.";
            }
        }
        if ($inspeccion->nivel_combustible === null) {
            $f[] = 'Indica el nivel de gasolina.';
        } elseif ($inspeccion->nivel_combustible < 0 || $inspeccion->nivel_combustible > 8) {
            $f[] = 'El nivel de gasolina debe estar entre 0 y 8 octavos.';
        }

        // 2. Placas y persona
        if (blank($inspeccion->placas) && ! $inspeccion->sin_placas) {
            $f[] = 'Captura las placas o marca que el vehículo llegó sin placas.';
        }
        if (blank($inspeccion->persona_nombre)) {
            $f[] = $inspeccion->esRecepcion() ? 'Indica quién entrega el vehículo.' : 'Indica quién recoge el vehículo.';
        }

        // 3. Checklist: todos los obligatorios que aplican
        $respuestas = $inspeccion->respuestas()->get()->keyBy('checklist_item_id');
        foreach (ChecklistItem::aplicables($inspeccion->tipo, $inspeccion->tipo_carroceria) as $item) {
            $r = $respuestas->get($item->id);
            if ($r === null || blank($r->valor)) {
                if ($item->obligatorio) {
                    $f[] = "Checklist: falta «{$item->nombre}».";
                }

                continue;
            }
            $permitidos = $item->valoresPermitidos();
            if ($permitidos !== null && ! in_array($r->valor, $permitidos, true)) {
                $f[] = "Checklist: «{$item->nombre}» tiene un valor no válido ({$r->valor}).";
            }
            if ($item->tipo_respuesta === TipoRespuesta::Numero && ! is_numeric($r->valor)) {
                $f[] = "Checklist: «{$item->nombre}» debe ser un número.";
            }
        }

        // 4. Fotos por ángulo
        if (Preferencias::bool('recepcion_fotos_obligatorias', $sucursal, true)) {
            $angulosConFoto = $inspeccion->fotos()->whereNotNull('angulo_foto_id')->pluck('angulo_foto_id')->all();
            foreach (AnguloFoto::obligatoriosPara($inspeccion->tipo_carroceria) as $angulo) {
                if (! in_array($angulo->id, $angulosConFoto)) {
                    $f[] = "Falta la foto: {$angulo->nombre}.";
                }
            }
        }

        // 5. Daños: declaración explícita y foto por daño
        $danios = $inspeccion->danios()->withCount('fotos')->get();
        if ($inspeccion->sin_danios === null) {
            $f[] = 'Registra los daños o marca «Sin daños visibles».';
        } elseif ($inspeccion->sin_danios && $danios->isNotEmpty()) {
            $f[] = 'Marcaste «Sin daños visibles» pero hay daños registrados.';
        } elseif (! $inspeccion->sin_danios && $danios->isEmpty()) {
            $f[] = 'Indicaste que hay daños pero no registraste ninguno.';
        }
        if (Preferencias::bool('recepcion_foto_por_danio', $sucursal, true)) {
            foreach ($danios->where('fotos_count', 0) as $d) {
                $f[] = "El daño «{$d->tipo->label()}» en la vista {$d->vista->label()} no tiene foto.";
            }
        }

        // 6. Objetos de valor: siempre se declara
        if ($inspeccion->cliente_retiro_valores === null) {
            $f[] = 'Pregunta al cliente si retiró sus objetos de valor.';
        }

        // 7. Términos y firma
        if (Preferencias::bool('recepcion_firma_obligatoria', $sucursal, true)) {
            if (! $inspeccion->acepta_terminos) {
                $f[] = 'El cliente debe aceptar los términos.';
            }
            if (blank($inspeccion->firmante_nombre)) {
                $f[] = 'Captura el nombre de quien firma.';
            }
            if (! $inspeccion->adjuntos()->where('tipo', TipoAdjunto::Firma->value)->exists()) {
                $f[] = 'Falta la firma.';
            }
        }

        return $f;
    }

    public function ejecutar(Inspeccion $inspeccion): Inspeccion
    {
        if ($inspeccion->estaCompletada()) {
            throw new ReglaDeNegocioException('La inspección ya está completada.');
        }

        $faltantes = $this->faltantes($inspeccion);
        if ($faltantes !== []) {
            throw new ReglaDeNegocioException('La inspección no está completa.', $faltantes);
        }

        return DB::transaction(function () use ($inspeccion) {
            $sucursal = $inspeccion->orden->sucursal_id;

            $inspeccion->update([
                'estado' => EstadoInspeccion::Completada,
                'completada_at' => now(),
                'firmado_at' => $inspeccion->firmado_at ?? now(),
                'terminos_version' => $inspeccion->terminos_version
                    ?? Preferencias::int('recepcion_terminos_version', $sucursal, 1),
            ]);

            // La recepción actualiza los datos vivos del vehículo.
            if ($inspeccion->esRecepcion()) {
                $vehiculo = $inspeccion->orden->vehiculo;
                $cambios = [];
                if ($inspeccion->km > $vehiculo->km_actual || filled($inspeccion->km_justificacion)) {
                    $cambios['km_actual'] = $inspeccion->km;
                }
                if (filled($inspeccion->placas) && $inspeccion->placas !== $vehiculo->placas) {
                    $cambios['placas'] = $inspeccion->placas;
                }
                if ($cambios !== []) {
                    $vehiculo->update($cambios);
                }
            }

            return $inspeccion->refresh();
        });
    }
}

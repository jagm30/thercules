<?php

namespace App\Models;

use App\Actions\GenerarRecordatorios;
use App\Enums\EstadoInspeccion;
use App\Enums\EstadoOrden;
use App\Enums\TipoInspeccion;
use App\Enums\TipoOrden;
use App\Exceptions\ReglaDeNegocioException;
use App\Models\Concerns\RegistraActividad;
use App\Support\Preferencias;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class OrdenServicio extends Model
{
    use RegistraActividad, SoftDeletes;

    protected $table = 'ordenes_servicio';

    protected $fillable = [
        'sucursal_id',
        'folio',
        'vehiculo_id',
        'cliente_id',
        'tipo',
        'orden_garantia_de_id',
        'estado',
        'con_precios',
        'recibido_por_id',
        'mecanico_responsable_id',
        'entregado_por_id',
        'fecha_ingreso',
        'fecha_promesa',
        'fecha_entrega',
        'motivo_ingreso',
        'diagnostico',
        'observaciones',
        'motivo_cancelacion',
        'descuento',
    ];

    protected function casts(): array
    {
        return [
            'tipo' => TipoOrden::class,
            'estado' => EstadoOrden::class,
            'con_precios' => 'boolean',
            'fecha_ingreso' => 'datetime',
            'fecha_promesa' => 'datetime',
            'fecha_entrega' => 'datetime',
            'subtotal' => 'decimal:2',
            'descuento' => 'decimal:2',
            'iva' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    // ── Relaciones ───────────────────────────────────────────────

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(Vehiculo::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function recibidoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recibido_por_id');
    }

    public function mecanicoResponsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mecanico_responsable_id');
    }

    public function entregadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'entregado_por_id');
    }

    /** Orden original cuando esta es una garantía. */
    public function ordenOriginal(): BelongsTo
    {
        return $this->belongsTo(self::class, 'orden_garantia_de_id');
    }

    public function garantias(): HasMany
    {
        return $this->hasMany(self::class, 'orden_garantia_de_id');
    }

    public function bitacoraEstados(): HasMany
    {
        return $this->hasMany(OrdenEstado::class)->orderBy('created_at')->orderBy('id');
    }

    public function inspecciones(): HasMany
    {
        return $this->hasMany(Inspeccion::class);
    }

    public function recepcion(): HasOne
    {
        return $this->hasOne(Inspeccion::class)->where('tipo', TipoInspeccion::Recepcion->value);
    }

    public function entrega(): HasOne
    {
        return $this->hasOne(Inspeccion::class)->where('tipo', TipoInspeccion::Entrega->value);
    }

    /** Trabajos realizados (tabla orden_servicios). */
    public function trabajos(): HasMany
    {
        return $this->hasMany(OrdenServicioItem::class);
    }

    public function refacciones(): HasMany
    {
        return $this->hasMany(OrdenRefaccion::class);
    }

    public function adjuntos(): MorphMany
    {
        return $this->morphMany(Adjunto::class, 'adjuntable');
    }

    public function recordatoriosGenerados(): HasMany
    {
        return $this->hasMany(RecordatorioServicio::class, 'orden_origen_id');
    }

    // ── Scopes ───────────────────────────────────────────────────

    public function scopeAbiertas(Builder $query): Builder
    {
        return $query->whereIn('estado', EstadoOrden::abiertos());
    }

    public function scopeAsignadasA(Builder $query, User $mecanico): Builder
    {
        return $query->where(function ($q) use ($mecanico) {
            $q->where('mecanico_responsable_id', $mecanico->id)
                ->orWhereHas('trabajos.mecanicos', fn ($m) => $m->where('users.id', $mecanico->id));
        });
    }

    // ── Reglas ───────────────────────────────────────────────────

    public function estaCerrada(): bool
    {
        return $this->estado->esCerrado();
    }

    /**
     * Cambia el estado validando el ciclo de vida, el rol y las reglas de recepción/entrega,
     * y deja el registro en la bitácora.
     */
    public function cambiarEstado(EstadoOrden $nuevo, User $usuario, ?string $comentario = null): void
    {
        $actual = $this->estado;
        $errores = [];

        if (! $actual->puedePasarA($nuevo)) {
            throw new ReglaDeNegocioException("No se puede pasar de «{$actual->label()}» a «{$nuevo->label()}».");
        }

        if (! $usuario->hasAnyRole($nuevo->rolesQuePuedenEntrar())) {
            throw new ReglaDeNegocioException("Tu rol no puede mover la orden a «{$nuevo->label()}».");
        }

        // Se consulta en vivo (no la relación en caché): la inspección pudo completarse hace un momento.
        if ($actual === EstadoOrden::Recibido && $nuevo !== EstadoOrden::Cancelado
            && $this->recepcion()->first()?->estado !== EstadoInspeccion::Completada) {
            $errores[] = 'La inspección de recepción no está completada.';
        }

        if ($nuevo === EstadoOrden::Entregado
            && Preferencias::bool('entrega_inspeccion_obligatoria', $this->sucursal_id, true)
            && $this->entrega()->first()?->estado !== EstadoInspeccion::Completada) {
            $errores[] = 'La inspección de entrega no está completada.';
        }

        if ($nuevo === EstadoOrden::Cancelado && blank($comentario ?? $this->motivo_cancelacion)) {
            $errores[] = 'Indica el motivo de cancelación.';
        }

        if ($errores !== []) {
            throw new ReglaDeNegocioException('No se puede cambiar el estado.', $errores);
        }

        DB::transaction(function () use ($actual, $nuevo, $usuario, $comentario) {
            $cambios = ['estado' => $nuevo];

            if ($nuevo === EstadoOrden::Cancelado) {
                $cambios['motivo_cancelacion'] = $comentario ?? $this->motivo_cancelacion;
            }
            if ($nuevo === EstadoOrden::Entregado) {
                $cambios['fecha_entrega'] = now();
                $cambios['entregado_por_id'] = $usuario->id;
            }

            $this->update($cambios);

            $this->bitacoraEstados()->create([
                'estado_anterior' => $actual,
                'estado_nuevo' => $nuevo,
                'user_id' => $usuario->id,
                'comentario' => $comentario,
            ]);

            if ($nuevo === EstadoOrden::Entregado) {
                (new GenerarRecordatorios)->ejecutar($this);
            }
        });
    }

    /**
     * Recalcula importes con IVA incluido.
     * subtotal = suma de importes; total = subtotal − descuento;
     * iva = IVA contenido en el total (solo para reportes internos).
     */
    public function recalcularTotales(): void
    {
        if (! $this->con_precios) {
            $this->forceFill(['subtotal' => null, 'descuento' => null, 'iva' => null, 'total' => null])->save();

            return;
        }

        $subtotal = (float) $this->trabajos()->sum('importe') + (float) $this->refacciones()->sum('importe');
        $descuento = (float) ($this->descuento ?? 0);
        $total = max($subtotal - $descuento, 0);
        $pct = Preferencias::decimal('iva_porcentaje', $this->sucursal_id, 16.0);

        $this->forceFill([
            'subtotal' => round($subtotal, 2),
            'descuento' => round($descuento, 2),
            'total' => round($total, 2),
            'iva' => round($total - $total / (1 + $pct / 100), 2),
        ])->save();
    }
}

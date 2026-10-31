<?php

namespace App\Models;

use App\Enums\EstadoRecordatorio;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Próximo servicio de un vehículo, por fecha y/o kilometraje (lo que ocurra primero).
 *
 * @property int $id
 * @property int $vehiculo_id
 * @property int|null $orden_origen_id
 * @property int|null $servicio_id
 * @property string|null $descripcion
 * @property Carbon|null $fecha_programada
 * @property int|null $km_programado
 * @property EstadoRecordatorio $estado
 * @property int|null $orden_cumplimiento_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class RecordatorioServicio extends Model
{
    protected $table = 'recordatorios_servicio';

    protected $fillable = [
        'vehiculo_id',
        'orden_origen_id',
        'servicio_id',
        'descripcion',
        'fecha_programada',
        'km_programado',
        'estado',
        'orden_cumplimiento_id',
    ];

    protected function casts(): array
    {
        return [
            'estado' => EstadoRecordatorio::class,
            'fecha_programada' => 'date',
            'km_programado' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (RecordatorioServicio $r) {
            if ($r->fecha_programada === null && $r->km_programado === null) {
                throw new \InvalidArgumentException('El recordatorio necesita fecha o kilometraje.');
            }
        });
    }

    /** @return BelongsTo<Vehiculo, $this> */
    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(Vehiculo::class);
    }

    /** @return BelongsTo<Servicio, $this> */
    public function servicio(): BelongsTo
    {
        return $this->belongsTo(Servicio::class)->withTrashed();
    }

    /** @return BelongsTo<OrdenServicio, $this> */
    public function ordenOrigen(): BelongsTo
    {
        return $this->belongsTo(OrdenServicio::class, 'orden_origen_id');
    }

    /** @return BelongsTo<OrdenServicio, $this> */
    public function ordenCumplimiento(): BelongsTo
    {
        return $this->belongsTo(OrdenServicio::class, 'orden_cumplimiento_id');
    }

    /**
     * @param  Builder<RecordatorioServicio>  $query
     * @return Builder<RecordatorioServicio>
     */
    public function scopePendientes(Builder $query): Builder
    {
        return $query->where('estado', EstadoRecordatorio::Pendiente->value);
    }

    /**
     * Pendientes cuya fecha vence dentro de $dias días (para el tablero).
     *
     * @param  Builder<RecordatorioServicio>  $query
     * @return Builder<RecordatorioServicio>
     */
    public function scopePorVencer(Builder $query, int $dias = 15): Builder
    {
        return $query->where('estado', EstadoRecordatorio::Pendiente->value)
            ->whereNotNull('fecha_programada')
            ->whereDate('fecha_programada', '<=', now()->addDays($dias))
            ->orderBy('fecha_programada');
    }
}

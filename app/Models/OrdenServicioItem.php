<?php

namespace App\Models;

use App\Enums\EstadoTrabajo;
use App\Enums\TipoCobro;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Un trabajo realizado dentro de una orden (tabla orden_servicios).
 * Se llama "Item" porque OrdenServicio ya es el modelo de la orden;
 * por eso su llave foránea en otras tablas es orden_servicio_item_id.
 *
 * @property int $id
 * @property int $orden_servicio_id
 * @property int|null $servicio_id
 * @property string|null $descripcion
 * @property TipoCobro $tipo_cobro
 * @property string $cantidad
 * @property string|null $precio_unitario
 * @property string|null $descuento
 * @property string|null $importe
 * @property EstadoTrabajo $estado
 * @property string|null $notas
 * @property bool $con_precios
 * @property-read OrdenServicio $orden
 * @property-read Servicio|null $servicio
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class OrdenServicioItem extends Model
{
    protected $table = 'orden_servicios';

    protected $fillable = [
        'orden_servicio_id',
        'servicio_id',
        'descripcion',
        'tipo_cobro',
        'cantidad',
        'precio_unitario',
        'descuento',
        'estado',
        'notas',
    ];

    protected function casts(): array
    {
        return [
            'tipo_cobro' => TipoCobro::class,
            'estado' => EstadoTrabajo::class,
            'cantidad' => 'decimal:3',
            'precio_unitario' => 'decimal:2',
            'descuento' => 'decimal:2',
            'importe' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (OrdenServicioItem $item) {
            // Snapshot desde el catálogo cuando no viene capturado.
            if ($item->servicio_id && $item->servicio) {
                $item->descripcion ??= $item->servicio->nombre;
                $item->tipo_cobro ??= $item->servicio->tipo_cobro;
                $item->precio_unitario ??= $item->servicio->precio;
            }

            $item->tipo_cobro ??= TipoCobro::Fijo;
            $item->cantidad ??= '1';
            $item->estado ??= EstadoTrabajo::Pendiente;

            // Orden sin precios: ningún importe se guarda.
            if (! $item->orden->con_precios) {
                $item->precio_unitario = null;
                $item->descuento = null;
                $item->importe = null;

                return;
            }

            $item->importe = $item->precio_unitario === null
                ? null
                : number_format(round((float) $item->cantidad * (float) $item->precio_unitario - (float) ($item->descuento ?? 0), 2), 2, '.', '');
        });
    }

    /** @return BelongsTo<OrdenServicio, $this> */
    public function orden(): BelongsTo
    {
        return $this->belongsTo(OrdenServicio::class, 'orden_servicio_id');
    }

    /** @return BelongsTo<Servicio, $this> */
    public function servicio(): BelongsTo
    {
        return $this->belongsTo(Servicio::class)->withTrashed();
    }

    /** @return BelongsToMany<User, $this> */
    public function mecanicos(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'orden_servicio_mecanico', 'orden_servicio_item_id', 'user_id')
            ->withPivot('horas')
            ->withTimestamps();
    }

    /** @return HasMany<OrdenRefaccion, $this> */
    public function refacciones(): HasMany
    {
        return $this->hasMany(OrdenRefaccion::class, 'orden_servicio_item_id');
    }

    /** En cobro por hora, toma como cantidad la suma de horas de los mecánicos. */
    public function sincronizarHorasDeMecanicos(): void
    {
        if ($this->tipo_cobro === TipoCobro::PorHora) {
            $this->cantidad = number_format((float) $this->mecanicos()->sum('orden_servicio_mecanico.horas'), 3, '.', '');
            $this->save();
        }
    }
}

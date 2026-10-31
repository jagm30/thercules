<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $orden_servicio_id
 * @property int|null $orden_servicio_item_id
 * @property int|null $refaccion_id
 * @property string|null $numero_parte
 * @property string|null $descripcion
 * @property string $cantidad
 * @property string|null $costo_unitario
 * @property string|null $precio_unitario
 * @property string|null $importe
 * @property bool $proporcionada_por_cliente
 * @property int|null $garantia_dias
 * @property-read OrdenServicio $orden
 * @property-read Refaccion|null $refaccion
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class OrdenRefaccion extends Model
{
    protected $table = 'orden_refacciones';

    protected $fillable = [
        'orden_servicio_id',
        'orden_servicio_item_id',
        'refaccion_id',
        'numero_parte',
        'descripcion',
        'cantidad',
        'costo_unitario',
        'precio_unitario',
        'proporcionada_por_cliente',
        'garantia_dias',
    ];

    protected $hidden = ['costo_unitario'];

    protected function casts(): array
    {
        return [
            'cantidad' => 'decimal:3',
            'costo_unitario' => 'decimal:2',
            'precio_unitario' => 'decimal:2',
            'importe' => 'decimal:2',
            'proporcionada_por_cliente' => 'boolean',
            'garantia_dias' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (OrdenRefaccion $r) {
            if ($r->refaccion_id && $r->refaccion) {
                $r->descripcion ??= $r->refaccion->descripcion;
                $r->numero_parte ??= $r->refaccion->numero_parte;
                $r->costo_unitario ??= $r->refaccion->costo;
                $r->precio_unitario ??= $r->refaccion->precio_venta;
            }

            if (! $r->orden->con_precios) {
                $r->costo_unitario = null;
                $r->precio_unitario = null;
                $r->importe = null;

                return;
            }

            if ($r->proporcionada_por_cliente) {
                $r->costo_unitario = '0.00';
                $r->precio_unitario = '0.00';
            }

            $r->importe = $r->precio_unitario === null
                ? null
                : number_format(round((float) $r->cantidad * (float) $r->precio_unitario, 2), 2, '.', '');
        });
    }

    /** @return BelongsTo<OrdenServicio, $this> */
    public function orden(): BelongsTo
    {
        return $this->belongsTo(OrdenServicio::class, 'orden_servicio_id');
    }

    /** Trabajo al que pertenece la pieza.
     * @return BelongsTo<OrdenServicioItem, $this>
     */
    public function trabajo(): BelongsTo
    {
        return $this->belongsTo(OrdenServicioItem::class, 'orden_servicio_item_id');
    }

    /** @return BelongsTo<Refaccion, $this> */
    public function refaccion(): BelongsTo
    {
        return $this->belongsTo(Refaccion::class)->withTrashed();
    }
}

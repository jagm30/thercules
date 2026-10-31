<?php

namespace App\Models;

use App\Enums\EstadoOrden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Bitácora inmutable: solo se insertan registros.
 *
 * @property int $id
 * @property int $orden_servicio_id
 * @property EstadoOrden|null $estado_anterior
 * @property EstadoOrden $estado_nuevo
 * @property int $user_id
 * @property string|null $comentario
 * @property Carbon $created_at
 */
class OrdenEstado extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'orden_estados';

    protected $fillable = [
        'orden_servicio_id',
        'estado_anterior',
        'estado_nuevo',
        'user_id',
        'comentario',
    ];

    protected function casts(): array
    {
        return [
            'estado_anterior' => EstadoOrden::class,
            'estado_nuevo' => EstadoOrden::class,
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => false);
        static::deleting(fn () => false);
    }

    /** @return BelongsTo<OrdenServicio, $this> */
    public function orden(): BelongsTo
    {
        return $this->belongsTo(OrdenServicio::class, 'orden_servicio_id');
    }

    /** @return BelongsTo<User, $this> */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
